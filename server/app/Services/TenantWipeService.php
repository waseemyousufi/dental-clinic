<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;

class TenantWipeService
{
    public function wipe(int $tenantId): void
    {
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
            \App\Models\Shelf::class,
            \App\Models\Supplier::class,
            \App\Models\Item::class,
            \App\Models\AccountTransaction::class,
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

        // 4. Hard delete all other models
        DB::transaction(function () use ($models, $tenantId, $withTrashedIfApplicable) {
            foreach ($models as $model) {
                $query = $withTrashedIfApplicable($model::query(), $model);
                $query->where('branch_id', $tenantId)->forceDelete();
            }
        });
    }
}
