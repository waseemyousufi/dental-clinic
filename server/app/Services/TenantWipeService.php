<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;

class TenantWipeService
{
    public function wipe(int $tenantId): void
    {
        // Models to hard-delete by branch (branch_id)
        $models = [
            \App\Models\Order::class,
            \App\Models\Appointment::class,
            \App\Models\Patient::class,
            \App\Models\PatientFile::class,
            \App\Models\EmployeeSalary::class,
            \App\Models\ClinicExpense::class,
            \App\Models\Allergy::class,
            \App\Models\DentalXray::class,
            \App\Models\ClinicAsset::class,
            \App\Models\TreatmentPlan::class,
            \App\Models\Setting::class,
            \App\Models\Prescription::class,
            \App\Models\Procedure::class,
            \App\Models\Treatment::class,
            \App\Models\Shelf::class,
            \App\Models\Supplier::class,
            \App\Models\Item::class,
            \App\Models\AccountTransaction::class,
            \App\Models\Account::class,
        ];

        // Pivot tables (no models) to delete by branch
        $pivotTables = [
            'appointment_patient',
            'appointment_employee',
            'appointment_procedure',
            'treatment_plan_procedure',
            'account_transaction_clinic_material',
        ];

        // 1. Get user IDs from employees BEFORE deleting them
        // We use DB::table to bypass any soft deletes or global scopes
        $userIds = DB::table('employees')
            ->where('branch_id', $tenantId)
            ->pluck('user_id')
            ->filter()
            ->unique()
            ->values();

        // Helper closure to safely apply withTrashed() only if the model uses SoftDeletes
        $withTrashedIfApplicable = function ($query, $modelClass) {
            if (in_array(SoftDeletes::class, class_uses_recursive($modelClass))) {
                return $query->withTrashed();
            }
            return $query;
        };

        // 2. Hard delete employees
        $empQuery = $withTrashedIfApplicable(\App\Models\Employee::query(), \App\Models\Employee::class);
        $empQuery->where('branch_id', $tenantId)->forceDelete();

        // 3. Hard delete associated users
        if ($userIds->isNotEmpty()) {
            $userQuery = $withTrashedIfApplicable(\App\Models\User::query(), \App\Models\User::class);
            $userQuery->whereIn('id', $userIds)->forceDelete();
        }

        // 4. Hard delete all other models and pivot tables
        DB::transaction(function () use ($models, $pivotTables, $tenantId, $withTrashedIfApplicable) {
            // delete model records
            foreach ($models as $model) {
                $query = $withTrashedIfApplicable($model::query(), $model);
                // some tables may not have branch_id; guard with try/catch to avoid breaking the wipe
                try {
                    $query->where('branch_id', $tenantId)->forceDelete();
                } catch (\Exception $e) {
                    // fallback: try deleting via DB table if available
                    try {
                        DB::table((new $model)->getTable())->where('branch_id', $tenantId)->delete();
                    } catch (\Exception $_) {
                        // ignore and continue
                    }
                }
            }

            // delete pivot rows directly
            foreach ($pivotTables as $table) {
                try {
                    DB::table($table)->where('branch_id', $tenantId)->delete();
                } catch (\Exception $e) {
                    // ignore missing tables or errors and continue
                }
            }
        });
    }
}
