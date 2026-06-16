<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;

class TenantWipeService
{
    public function wipe(int $tenantId): void
    {
        // Fix 3: Removed Order, PatientFile, Prescription, Shelf, Supplier, Item
        // to prevent permanent data loss. If you wish to wipe these, you MUST also add
        // them to the BackupController so they are exported in the JSON file.
        $models = [
            \App\Models\Appointment::class,
            \App\Models\Patient::class,
            \App\Models\EmployeeSalary::class,
            \App\Models\ClinicExpense::class,
            \App\Models\Allergy::class,
            \App\Models\DentalXray::class,
            \App\Models\ClinicAsset::class,
            \App\Models\TreatmentPlan::class,
            \App\Models\Setting::class,
            \App\Models\Procedure::class,
            \App\Models\Treatment::class,
            \App\Models\AccountTransaction::class,
            \App\Models\Account::class,
        ];

        $pivotTables = [
            'appointment_patient',
            'appointment_employee',
            'appointment_procedure',
            'treatment_plan_procedure',
            'account_transaction_clinic_material',
        ];

        // 1. Capture User IDs linked to target employees before deleting
        $userIds = DB::table('employees')
            ->where('branch_id', $tenantId)
            ->pluck('user_id')
            ->filter()
            ->unique()
            ->values();

        $withTrashedIfApplicable = function ($query, $modelClass) {
            if (in_array(SoftDeletes::class, class_uses_recursive($modelClass))) {
                return $query->withTrashed();
            }
            return $query;
        };

        // Fix 4: Reordered deletion. Pivots -> Models -> Employees -> Users
        // 2. Atomic transaction execution for dependent schemas FIRST
        DB::transaction(function () use ($models, $pivotTables, $tenantId, $withTrashedIfApplicable) {
            // Delete pivot tables first to clear lower-level references
            foreach ($pivotTables as $table) {
                try {
                    DB::table($table)->where('branch_id', $tenantId)->delete();
                } catch (\Exception $e) {
                    // Fail silently and keep system wiping
                }
            }

            // Delete standard models next
            foreach ($models as $model) {
                if (!class_exists($model)) {
                    continue;
                }

                try {
                    $query = $withTrashedIfApplicable($model::query(), $model);
                    $query->where('branch_id', $tenantId)->forceDelete();
                } catch (\Exception $e) {
                    try {
                        $tableName = (new $model)->getTable();
                        DB::table($tableName)->where('branch_id', $tenantId)->delete();
                    } catch (\Exception $_) {
                        // Keep loop running if column or table fails validation context
                    }
                }
            }
        });

        // 3. Hard delete employees NOW that their dependencies (appointments, transactions) are gone
        try {
            $empQuery = $withTrashedIfApplicable(\App\Models\Employee::query(), \App\Models\Employee::class);
            $empQuery->where('branch_id', $tenantId)->forceDelete();
        } catch (\Exception $e) {
            DB::table('employees')->where('branch_id', $tenantId)->delete();
        }

        // 4. Hard delete unique associated users LAST
        if ($userIds->isNotEmpty()) {
            try {
                $userQuery = $withTrashedIfApplicable(\App\Models\User::query(), \App\Models\User::class);
                $userQuery->whereIn('id', $userIds)->forceDelete();
            } catch (\Exception $e) {
                DB::table('users')->whereIn('id', $userIds)->delete();
            }
        }
    }
}
