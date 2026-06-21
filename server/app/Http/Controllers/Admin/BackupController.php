<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Allergy;
use App\Models\Appointment;
use App\Models\AppointmentPatient;
use App\Models\Branch;
use App\Models\ClinicAsset;
use App\Models\ClinicExpense;
use App\Models\ClinicOwner;
use App\Models\DentalXray;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\ProcedureAppointment;
use App\Models\Setting;
use App\Models\Treatment;
use App\Models\TreatmentPlan;
use App\Models\User;
use App\Services\TenantWipeService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class BackupController extends Controller
{
    public function backup($branchId, Request $request)
    {
        $user = auth()->user();

        $clinicOwner = ClinicOwner::findOrFail($user->clinic_owner_id);
        $branch = $clinicOwner->branches()->findOrFail($branchId);

        if ($user->clinic_owner_id !== $branch->clinic_owner_id) {
            return response()->json([
                'message' => 'Unauthorized - user does not belong to this branch'
            ], 403);
        }

        $branchId = $branch->id;

        $patients = $this->getFromBranch('patients', $branchId);
        $appointments = $this->getFromBranch('appointments', $branchId);
        $employees = $this->getFromBranch('employees', $branchId);
        $treatments = $this->getFromBranch('treatments', $branchId);
        $allergies = $this->getFromBranch('allergies', $branchId);
        $dentalXrays = $this->getFromBranch('dental_xrays', $branchId);
        $transactions = $this->getFromBranch('transactions', $branchId);
        $accounts = $this->getFromBranch('accounts', $branchId);
        $clinicAssets = $this->getFromBranch('clinicAssets', $branchId);
        $clinicExpenses = $this->getFromBranch('clinicExpenses', $branchId);
        $employeeSalaries = $this->getFromBranch('employee_salaries', $branchId);
        $procedures = $this->getFromBranch('procedures', $branchId);
        $settings = $this->getFromBranch('settings', $branchId);
        $treatmentPlans = $this->getFromBranch('treatment_plans', $branchId);

        $userIds = $employees->pluck('user_id')->filter()->unique()->values();
        $users = $userIds->isNotEmpty()
            ? DB::table('users')->whereIn('id', $userIds)->get()->map(function ($u) {
                return (array) $u;
            })->keyBy('id')
            : collect();

        $appointmentPatient = DB::table('appointment_patient')->where('branch_id', $branchId)->get()->map(function ($row) {
            return (array) $row;
        });
        $appointmentEmployee = DB::table('appointment_employee')->where('branch_id', $branchId)->get()->map(function ($row) {
            return (array) $row;
        });
        $appointmentProcedure = DB::table('appointment_procedure')->where('branch_id', $branchId)->get()->map(function ($row) {
            return (array) $row;
        });
        $treatmentPlan_procedure = DB::table('treatment_plan_procedure')->where('branch_id', $branchId)->get()->map(function ($row) {
            return (array) $row;
        });
        $accountTransaction_clinicMaterial = DB::table('account_transaction_clinic_material')->where('branch_id', $branchId)->get()->map(function ($row) {
            return (array) $row;
        });

        $payload = [
            'branch_id' => $branchId,
            'exported_at' => now()->toISOString(),
            'counts' => [
                'users' => $users->count(),
                'patients' => $patients->count(),
                'appointments' => $appointments->count(),
                'employees' => $employees->count(),
                'treatments' => $treatments->count(),
                'allergies' => $allergies->count(),
                'dental_xrays' => $dentalXrays->count(),
                'transactions' => $transactions->count(),
                'accounts' => $accounts->count(),
                'clinicAssets' => $clinicAssets->count(),
                'clinicExpenses' => $clinicExpenses->count(),
                'employee_salaries' => $employeeSalaries->count(),
                'procedures' => $procedures->count(),
                'settings' => $settings->count(),
                'treatment_plans' => $treatmentPlans->count(),
            ],
            'data' => [
                'users' => $users->toArray(),
                'patients' => $patients->toArray(),
                'appointments' => $appointments->toArray(),
                'employees' => $employees->toArray(),
                'treatments' => $treatments->toArray(),
                'allergies' => $allergies->toArray(),
                'dental_xrays' => $dentalXrays->toArray(),
                'transactions' => $transactions->toArray(),
                'accounts' => $accounts->toArray(),
                'clinicAssets' => $clinicAssets->toArray(),
                'clinicExpenses' => $clinicExpenses->toArray(),
                'employee_salaries' => $employeeSalaries->toArray(),
                'procedures' => $procedures->toArray(),
                'settings' => $settings->toArray(),
                'treatment_plans' => $treatmentPlans->toArray(),
                'appointment_patient' => $appointmentPatient->toArray(),
                'appointment_employee' => $appointmentEmployee->toArray(),
                'appointment_procedure' => $appointmentProcedure->toArray(),
                'treatment_plan_procedure' => $treatmentPlan_procedure->toArray(),
                'account_transaction_clinic_material' => $accountTransaction_clinicMaterial->toArray(),
            ],
        ];

        $json = json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        $filename = sprintf('backup-branch-%s-%s.json', $branchId, date('YmdHis'));

        return response()->streamDownload(function () use ($json) {
            echo $json;
        }, $filename, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function restore(int $branchId, Request $request, TenantWipeService $wipeService)
    {
        $branch = Branch::findOrFail($branchId);

        $content = $request->hasFile('backup_file')
            ? file_get_contents($request->file('backup_file')->getRealPath())
            : $request->getContent();

        $payload = json_decode($content, true);

        if (!$payload || !isset($payload['data'])) {
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        $data = $payload['data'];
        $targetBranchId = $branch->id;
        $mappings = [];

        // $wipeService->wipe($branchId);

        DB::beginTransaction();

        // Fix 1: Disable Foreign Key Checks during mass data manipulation
        Schema::disableForeignKeyConstraints();

        try {
            // 1. Clear out pre-existing tenant branch data safely
            $wipeService->wipe($branchId);

            // 2. CORE TABLES (No FK dependencies)
            $mappings['procedures'] = $this->insertRows(
                $data['procedures'] ?? [],
                \App\Models\Procedure::class,
                $mappings,
                $targetBranchId
            );

            $mappings['accounts'] = $this->insertRows(
                $data['accounts'] ?? [],
                \App\Models\Account::class,
                $mappings,
                $targetBranchId
            );

            $mappings['patients'] = $this->insertRows(
                $data['patients'] ?? [],
                \App\Models\Patient::class,
                $mappings,
                $targetBranchId
            );

            $mappings['users'] = $this->insertUsers(
                $data['users'] ?? [],
                $targetBranchId
            );

            // 3. DEPENDENT TABLES (Requires Users)
            $mappings['employees'] = $this->insertRows(
                $data['employees'] ?? [],
                \App\Models\Employee::class,
                $mappings,
                $targetBranchId,
                [
                    'user_id' => 'users'
                ]
            );

            // 4. FINANCIAL TRANSACTIONS (Requires Accounts & Employees)
            $mappings['transactions'] = $this->insertRows(
                $data['transactions'] ?? [],
                \App\Models\AccountTransaction::class,
                $mappings,
                $targetBranchId,
                [
                    'account_id' => 'accounts',
                    'recorded_by_employee_id' => 'employees'
                ]
            );

            // 5. APPOINTMENTS (Requires Patients & Employees)
            $mappings['appointments'] = $this->insertRows(
                $data['appointments'] ?? [],
                \App\Models\Appointment::class,
                $mappings,
                $targetBranchId,
                [
                    'patient_id' => 'patients',
                    'employee_id' => 'employees'
                ]
            );

            // Fix 2: Removed the redundant 'procedure_appointment' mapping call that was causing bugs
            // since it is handled correctly below via restorePivotTable()

            // 6. TREATMENT PLANS & TREATMENTS
            $mappings['treatment_plans'] = $this->insertRows(
                $data['treatment_plans'] ?? $data['treamtment_plans'] ?? [],
                \App\Models\TreatmentPlan::class,
                $mappings,
                $targetBranchId,
                [
                    'patient_id' => 'patients',
                    'procedure_id' => 'procedures'
                ]
            );

            $mappings['treatments'] = $this->insertRows(
                $data['treatments'] ?? [],
                \App\Models\Treatment::class,
                $mappings,
                $targetBranchId,
                [
                    'treatment_plan_id' => 'treatment_plans'
                ]
            );

            // 7. MEDICAL ASSETS
            $mappings['dental_xrays'] = $this->insertRows(
                $data['dental_xrays'] ?? [],
                \App\Models\DentalXray::class,
                $mappings,
                $targetBranchId,
                [
                    'patient_id' => 'patients'
                ]
            );

            $mappings['allergies'] = $this->insertRows(
                $data['allergies'] ?? [],
                \App\Models\Allergy::class,
                $mappings,
                $targetBranchId,
                [
                    'patient_id' => 'patients'
                ]
            );

            // 8. OPERATIONS & OPERATIONAL EXPENSES
            $mappings['clinicAssets'] = $this->insertRows(
                $data['clinicAssets'] ?? [],
                \App\Models\ClinicAsset::class,
                $mappings,
                $targetBranchId
            );

            $mappings['clinicExpenses'] = $this->insertRows(
                $data['clinicExpenses'] ?? [],
                \App\Models\ClinicExpense::class,
                $mappings,
                $targetBranchId,
                [
                    'paidByEmployee_id' => 'employees'
                ]
            );

            $mappings['employee_salaries'] = $this->insertRows(
                $data['employee_salaries'] ?? [],
                \App\Models\EmployeeSalary::class,
                $mappings,
                $targetBranchId,
                [
                    'employee_id' => 'employees',
                    'paidByAccountTransaction_id' => 'transactions'
                ]
            );

            $mappings['settings'] = $this->insertRows(
                $data['settings'] ?? [],
                \App\Models\Setting::class,
                $mappings,
                $targetBranchId
            );

            // 9. PIVOT RELATIONSHIPS RESTORATION
            $this->restorePivotTable(
                'appointment_patient',
                $data['appointment_patient'] ?? [],
                $targetBranchId,
                $mappings,
                [
                    'appointment_id' => 'appointments',
                    'patient_id' => 'patients',
                ]
            );

            $this->restorePivotTable(
                'appointment_employee',
                $data['appointment_employee'] ?? [],
                $targetBranchId,
                $mappings,
                [
                    'appointment_id' => 'appointments',
                    'employee_id' => 'employees',
                ]
            );

            $this->restorePivotTable(
                'appointment_procedure',
                $data['appointment_procedure'] ?? [],
                $targetBranchId,
                $mappings,
                [
                    'appointment_id' => 'appointments',
                    'procedure_id' => 'procedures',
                ]
            );

            $this->restorePivotTable(
                'treatment_plan_procedure',
                $data['treatment_plan_procedure'] ?? [],
                $targetBranchId,
                $mappings,
                [
                    'treatment_plan_id' => 'treatment_plans',
                    'procedure_id' => 'procedures',
                ]
            );

            $this->restorePivotTable(
                'account_transaction_clinic_material',
                $data['account_transaction_clinic_material'] ?? [],
                $targetBranchId,
                $mappings,
                [
                    'account_transaction_id' => 'transactions',
                ]
            );

            DB::commit();
            Schema::enableForeignKeyConstraints();

            return response()->json([
                'message' => 'Restore completed',
                'mappings' => $mappings
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Schema::enableForeignKeyConstraints();

            return response()->json([
                'message' => 'Restore failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function restorePivotTable(
        string $table,
        array $rows,
        int $branchId,
        array $mappings,
        array $remapKeys
    ): void {
        foreach ($rows as $row) {
            unset($row['id']);

            if (array_key_exists('branch_id', $row)) {
                $row['branch_id'] = $branchId;
            }

            foreach ($remapKeys as $column => $mappingName) {
                if (!isset($row[$column])) {
                    continue;
                }

                if (!isset($mappings[$mappingName][$row[$column]])) {
                    Log::warning(
                        "Skipping pivot row in {$table}. Missing mapping for {$mappingName}: {$row[$column]}"
                    );
                    continue 2;
                }

                $row[$column] = $mappings[$mappingName][$row[$column]];
            }

            DB::table($table)->insert($row);
        }
    }

    private function insertUsers(array $users, int $branchId): array
    {
        $map = [];

        foreach ($users as $user) {
            $oldId = $user['id'] ?? null;

            if (!$oldId) {
                continue;
            }

            foreach (['created_at', 'updated_at', 'deleted_at'] as $field) {
                if (!empty($user[$field])) {
                    $user[$field] = Carbon::parse($user[$field])->format('Y-m-d H:i:s');
                }
            }

            unset($user['id']);
            if (Schema::hasColumn('users', 'branch_id')) {
                $user['branch_id'] = $branchId;
            }

            $email = $user['email'] ?? null;
            if (!empty($email)) {
                $existing = DB::table('users')->where('email', $email)->first();
                if ($existing) {
                    if (Schema::hasColumn('users', 'branch_id') && empty($existing->branch_id)) {
                        try {
                            DB::table('users')->where('id', $existing->id)->update(['branch_id' => $branchId]);
                        } catch (\Exception $_) {
                            // ignore update failures
                        }
                    }

                    $map[$oldId] = $existing->id;
                    continue;
                }
            }

            $newId = DB::table('users')->insertGetId($user);
            $map[$oldId] = $newId;
        }

        return $map;
    }

    private function insertRows(
        array $rows,
        string $modelClass,
        array &$mappings,
        int $branchId,
        array $remapKeys = []
    ): array {
        if (empty($rows)) {
            return [];
        }

        $model = new $modelClass();
        $table = $model->getTable();
        $localMap = [];

        foreach ($rows as $row) {
            $oldId = $row['id'] ?? null;

            foreach (['created_at', 'updated_at', 'deleted_at'] as $field) {
                if (!empty($row[$field])) {
                    $row[$field] = Carbon::parse($row[$field])->format('Y-m-d H:i:s');
                }
            }

            foreach ($remapKeys as $key => $mapName) {
                if (!array_key_exists($key, $row) || $row[$key] === null || $row[$key] === '') {
                    continue;
                }

                if (!isset($mappings[$mapName][$row[$key]])) {
                    Log::warning("Restore skipped a row in {$table} because it references a missing {$mapName} ID: {$row[$key]}");
                    continue 2;
                }

                $row[$key] = $mappings[$mapName][$row[$key]];
            }

            if (array_key_exists('branch_id', $row)) {
                $row['branch_id'] = $branchId;
            }

            unset($row['id']);

            foreach ($row as $k => $v) {
                if (is_array($v) || is_object($v)) {
                    $row[$k] = json_encode($v);
                }
            }

            $newId = DB::table($table)->insertGetId($row);

            if ($oldId !== null) {
                $localMap[$oldId] = $newId;
            }
        }

        return $localMap;
    }

    public function getFromBranch(string $item, $branchId)
    {
        $modelClass = $this->getModelClassForItem($item);

        if (!$modelClass) {
            return collect();
        }

        $table = (new $modelClass)->getTable();

        return DB::table($table)->where('branch_id', $branchId)->get()->map(function ($row) {
            return (array) $row;
        });
    }

    public function getModelClassForItem(string $item)
    {
        $mapping = [
            'patients' => Patient::class,
            'appointments' => Appointment::class,
            'appointment_patient' => AppointmentPatient::class,
            'employees' => Employee::class,
            'treatments' => Treatment::class,
            'allergies' => Allergy::class,
            'dental_xrays' => DentalXray::class,
            'transactions' => AccountTransaction::class,
            'accounts' => Account::class,
            'clinicAssets' => ClinicAsset::class,
            'clinicExpenses' => ClinicExpense::class,
            'employee_salaries' => EmployeeSalary::class,
            'procedures' => Procedure::class,
            'procedure_appointment' => ProcedureAppointment::class,
            'settings' => Setting::class,
            'treatment_plans' => TreatmentPlan::class,
        ];

        return $mapping[$item] ?? null;
    }
}
