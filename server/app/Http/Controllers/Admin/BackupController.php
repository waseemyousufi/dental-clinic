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
        // $appointmentPatients = $this->getFromBranch('appointment_patient', $branchId);
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
        // $procedureAppointments = $this->getFromBranch('procedure_appointment', $branchId);
        $settings = $this->getFromBranch('settings', $branchId);
        $treatmentPlans = $this->getFromBranch('treamtment_plans', $branchId);
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
                // 'appointment_patient' => $appointmentPatients->count(),
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
                // 'procedure_appointment' => $procedureAppointments->count(),
                'settings' => $settings->count(),
                'treatment_plans' => $treatmentPlans->count(),
            ],
            'data' => [
                'users' => $users->toArray(),
                'patients' => $patients->toArray(),
                'appointments' => $appointments->toArray(),
                // 'appointment_patient' => $appointmentPatients->toArray(),
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
                // 'procedure_appointment' => $procedureAppointments->toArray(),
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

                    \Log::warning(
                        "Skipping pivot row in {$table}. Missing mapping for {$mappingName}: {$row[$column]}"
                    );

                    continue 2;
                }

                $row[$column] = $mappings[$mappingName][$row[$column]];
            }

            DB::table($table)->insert($row);
        }
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

        DB::beginTransaction();

        try {

            // 1. WIPE FIRST (important: before inserting anything)
            $wipeService->wipe($branchId);

            /*
        |-----------------------------
        | 1. CORE TABLES (NO FK deps)
        |-----------------------------
        */
            $mappings['procedures'] = $this->insertRows(
                $data['procedures'] ?? [],
                \App\Models\Procedure::class,
                $mappings,
                $targetBranchId
            );

            /*
        |-----------------------------
        | 2. USERS (must exist BEFORE employees)
        |-----------------------------
        */
            $mappings['users'] = $this->insertUsers(
                $data['users'] ?? [],
                $targetBranchId
            );

            /*
        |-----------------------------
        | 3. EMPLOYEES (depends on users)
        |-----------------------------
        */
            $mappings['employees'] = $this->insertRows(
                $data['employees'] ?? [],
                \App\Models\Employee::class,
                $mappings,
                $targetBranchId,
                [
                    'user_id' => 'users'
                ]
            );

            /*
        |-----------------------------
        | 4. PATIENTS
        |-----------------------------
        */
            $mappings['patients'] = $this->insertRows(
                $data['patients'] ?? [],
                \App\Models\Patient::class,
                $mappings,
                $targetBranchId
            );

            /*
        |-----------------------------
        | 5. FINANCIAL
        |-----------------------------
        */
            $mappings['accounts'] = $this->insertRows(
                $data['accounts'] ?? [],
                \App\Models\Account::class,
                $mappings,
                $targetBranchId
            );

            $mappings['transactions'] = $this->insertRows(
                $data['transactions'] ?? [],
                \App\Models\AccountTransaction::class,
                $mappings,
                $targetBranchId
            );

            /*
        |-----------------------------
        | 6. APPOINTMENTS
        |-----------------------------
        */
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

            // $mappings['appointment_patient'] = $this->insertRows(
            //     $data['appointment_patient'] ?? [],
            //     \App\Models\AppointmentPatient::class,
            //     $mappings,
            //     $targetBranchId,
            //     [
            //         'appointment_id' => 'appointments',
            //         'patient_id' => 'patients'
            //     ]
            // );

            $mappings['procedure_appointment'] = $this->insertRows(
                $data['procedure_appointment'] ?? [],
                \App\Models\ProcedureAppointment::class,
                $mappings,
                $targetBranchId,
                [
                    'procedure_id' => 'procedures',
                    'appointment_id' => 'appointments'
                ]
            );

            /*
        |-----------------------------
        | 7. TREATMENTS
        |-----------------------------
        */
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

            /*
        |-----------------------------
        | 8. MEDICAL
        |-----------------------------
        */
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

            /*
        |-----------------------------
        | 9. OPERATIONS
        |-----------------------------
        */
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
                $targetBranchId
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

            /*
        |-----------------------------
        | 10. SETTINGS
        |-----------------------------
        */
            $mappings['settings'] = $this->insertRows(
                $data['settings'] ?? [],
                \App\Models\Setting::class,
                $mappings,
                $targetBranchId
            );

            /*
|--------------------------------------------------------------------------
| PIVOT TABLES
|--------------------------------------------------------------------------
*/

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

            return response()->json([
                'message' => 'Restore completed',
                'mappings' => $mappings
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Restore failed',
                'error' => $e->getMessage()
            ], 500);
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

            // FIX: Format dates to prevent SQL errors
            foreach (['created_at', 'updated_at', 'deleted_at'] as $field) {
                if (!empty($user[$field])) {
                    $user[$field] = Carbon::parse($user[$field])->format('Y-m-d H:i:s');
                }
            }

            unset($user['id']);
            if (Schema::hasColumn('users', 'branch_id')) {
                $user['branch_id'] = $branchId;
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
                    // FIX: Log the issue and skip this row entirely instead of crashing the restore
                    // Log::warning("Restore skipped a row in {$table} because it references a missing {$mapName} ID: {$row[$key]}");
                    continue 2; // 'continue 2' breaks the inner loop and skips to the next $row
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

        // FIX: Use DB::table to bypass soft deletes, global scopes, and mutators
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
            'treamtment_plans' => TreatmentPlan::class,
            'treatment_plans' => TreatmentPlan::class,
        ];

        return $mapping[$item] ?? null;
    }
}
