<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountTransaction;
use App\Models\Appointment;
use App\Models\AppointmentPatient;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\TreatmentPlan;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Log::info('Reports index called', [
            'branch_id' => $request->input('branch_id'),
            'branchId' => $request->input('branchId'),
            'days' => $request->input('days', 30),
            'startDate' => $request->input('startDate'),
            'endDate' => $request->input('endDate'),
        ]);

        $branchId = $this->effectiveBranchId($request);
        $daysInput = (int) $request->input('days', 30);
        $days = in_array($daysInput, [1, 7, 30, 90], true) ? $daysInput : 30;

        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        if ($startDate && $endDate) {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
            $days = max(1, $start->diffInDays($end) + 1);
        } else {
            $end = Carbon::today()->endOfDay();
            $start = (clone $end)->subDays($days - 1)->startOfDay();
        }

        $payload = [
            'meta' => [
                'generated_at' => now()->toDateTimeString(),
                'branch_id' => $branchId,
                'branch_name' => $this->resolveBranchName($branchId),
                'period_days' => $days,
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
            ],
            'filters' => [
                'branches' => $this->branchOptions($branchId),
                'selected_branch_id' => $branchId,
                'selected_period_days' => $days,
            ],
            'financial_summary' => $this->buildFinancialSummary($branchId, $start, $end),
            'provider_productivity' => $this->buildProviderProductivity($branchId, $start, $end),
            'assistant_ledger' => $this->buildAssistantLedger($branchId, $start, $end),
            'operational_summary' => $this->buildOperationalSummary($branchId, $start, $end),
        ];

        return response()->json($payload);
    }

    private function buildFinancialSummary(?int $branchId, Carbon $start, Carbon $end): array
    {
        $appointmentQuery = $this->appointmentQuery($branchId, $start, $end);
        $transactionQuery = $this->transactionQuery($branchId, $start, $end);
        $treatments = $this->treatmentPlanQuery($branchId, $start, $end);

        $grossRevenue = (float) (clone $appointmentQuery)->sum('appointment_cost') + (float) (clone $treatments)->sum('total_estimated_cost');
        $netCollected = (float) (clone $transactionQuery)->where('transaction_type', 'in')->sum('amount');
        $netSpent = (float) (clone $transactionQuery)->where('transaction_type', 'out')->sum('amount');
        $outstandingAR = round($this->creditBalances($branchId)->sum('balance'), 2);

        $netProfit = $netCollected - $netSpent;

        $appointmentsForMix = (clone $appointmentQuery)->get(['id', 'procedure_id', 'appointment_cost']);
        $treatmentPlansForMix = (clone $treatments)->get(['id', 'procedure_id', 'total_estimated_cost']);
        $treatmentYield = $this->treatmentYieldFromAppointments($appointmentsForMix, $treatmentPlansForMix);

        return [
            'grossRevenue' => $grossRevenue,
            'netCollected' => $netCollected,
            'netSpent' => $netSpent,
            'netProfit' => $netProfit,
            'accountsReceivable' => $outstandingAR,
            'treatmentYield' => $treatmentYield,
            // Placeholder for trends - real implementation would need previous period data
            'grossRevenueTrend' => '+12% vs last month',
            'netCollectedTrend' => '+8% vs last month',
            'accountsReceivableTrend' => 'Action required',
        ];
    }

    private function treatmentYieldFromAppointments(Collection $appointments, Collection $treatmentPlans): array
    {
        $procedureIds = $appointments->pluck('procedure_id')
            ->merge($treatmentPlans->pluck('procedure_id'))
            ->filter()
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values()
            ->all();

        if (empty($procedureIds)) {
            return [];
        }

        $procedureMap = class_exists(Procedure::class)
            ? Procedure::query()->whereIn('id', $procedureIds)->get(['id', 'name'])->keyBy('id')
            : DB::table('procedures')->whereIn('id', $procedureIds)->get(['id', 'name'])->keyBy('id');

        $procedureAmounts = [];

        foreach ($appointments as $appointment) {
            $procedure = $procedureMap->get((int) ($appointment->procedure_id ?? 0));
            if (!$procedure) {
                continue;
            }

            $name = trim((string) ($procedure->name ?? ''));
            if ($name === '') {
                continue;
            }

            $cost = (float) ($appointment->appointment_cost ?? 0);
            $procedureAmounts[$name] = ($procedureAmounts[$name] ?? 0) + $cost;
        }

        foreach ($treatmentPlans as $plan) {
            $procedure = $procedureMap->get((int) $plan->procedure_id);
            if (!$procedure) {
                continue;
            }

            $name = trim((string) ($procedure->name ?? ''));
            if ($name === '') {
                continue;
            }

            $cost = (float) ($plan->total_estimated_cost ?? 0);
            $procedureAmounts[$name] = ($procedureAmounts[$name] ?? 0) + $cost;
        }

        $totalYield = array_sum($procedureAmounts);
        $formattedYield = [];

        foreach ($procedureAmounts as $name => $amount) {
            $percentage = $totalYield > 0 ? round(($amount / $totalYield) * 100, 2) : 0;
            $formattedYield[] = [
                'name' => $name,
                'amount' => round($amount, 2),
                'percentage' => $percentage,
            ];
        }

        // Sort by amount in descending order
        usort($formattedYield, fn($a, $b) => $b['amount'] <=> $a['amount']);

        return array_slice($formattedYield, 0, 5); // Top 5 treatments
    }

    private function buildProviderProductivity(?int $branchId, Carbon $start, Carbon $end): array
    {
        // Placeholder implementation for Provider Productivity
        // This would require queries joining Appointments, AppointmentEmployee, Employees, AccountTransactions

        // Example:
        $providers = Employee::query()
            ->when(!is_null($branchId), fn($q) => $q->where('branch_id', $branchId))
            ->whereIn('position_id', function ($query) {
                $query->select('id')->from('positions')->where('position_title', 'doctor');
            })
            ->get(['id', 'f_name', 'l_name']);

        $productivityData = [];

        foreach ($providers as $provider) {
            $appointments = Appointment::query()
                ->whereBetween('appointment_timestamp', [$start->toDateTimeString(), $end->copy()->endOfDay()->toDateTimeString()])
                ->where('branch_id', $branchId) // Assuming employee is linked to a branch and appointment also has branch_id
                ->whereHas('employees', fn($q) => $q->where('employee_id', $provider->id))
                ->get(['id', 'appointment_cost']);

            $appointmentPatientIds = AppointmentPatient::query()
                ->whereIn('appointment_id', $appointments->pluck('id'))
                ->pluck('patient_id')
                ->filter()
                ->unique();

            $appointmentValue = (float) $appointments->sum('appointment_cost');

            $treatmentPlans = TreatmentPlan::query()
                ->whereBetween('created_at', [$start->toDateTimeString(), $end->copy()->endOfDay()->toDateTimeString()])
                ->when(! is_null($branchId), fn($q) => $q->where('branch_id', $branchId))
                ->where('createdBy_id', $provider->id)
                ->get(['id', 'patient_id', 'total_estimated_cost']);

            $treatmentPlanPatients = $treatmentPlans
                ->pluck('patient_id')
                ->filter()
                ->unique();

            $treatmentPlanValue = (float) $treatmentPlans->sum('total_estimated_cost');
            $patientsTreated = $appointmentPatientIds
                ->merge($treatmentPlanPatients)
                ->unique()
                ->count();

            $totalValueGenerated = $appointmentValue + $treatmentPlanValue;


            $cashCollected = $totalValueGenerated;

            // Hours Logged is highly dependent on a time tracking system. Mocking for now.
            $hoursLogged = rand(120, 180); // Mock 120-180 hours

            $productivityData[] = [
                'name' => trim($provider->f_name . ' ' . $provider->l_name),
                'patientsTreated' => $patientsTreated,
                'hoursLogged' => $hoursLogged,
                'totalValueGenerated' => round($totalValueGenerated, 2),
                'cashCollected' => round($cashCollected, 2),
            ];
        }

        return $productivityData;
    }

    private function buildAssistantLedger(?int $branchId, Carbon $start, Carbon $end): array
    {
        // Placeholder implementation for Assistant Ledger
        // This data is highly specific and would require dedicated tracking in the database.
        // Mocking data for now.
        return [
            ['name' => 'Assistant A', 'task' => 'Sterilization', 'count' => rand(100, 150), 'speed' => '15 min/cycle'],
            ['name' => 'Assistant B', 'task' => 'Inventory Distribution', 'count' => rand(200, 300), 'itemsProcessed' => rand(1000, 2000)],
            ['name' => 'Assistant C', 'task' => 'Patient Prep', 'count' => rand(80, 120)],
        ];
    }

    private function branchOptions(?int $branchId): array
    {
        $q = Branch::query()->orderBy('branch_name');
        if (!is_null($branchId)) $q->where('id', $branchId);
        return $q->get(['id', 'branch_name'])->map(fn($b) => ['id' => $b->id, 'branch_name' => $b->branch_name])->values()->all();
    }

    private function resolveBranchName(?int $branchId): string
    {
        if (is_null($branchId)) return 'All branches';
        return Branch::query()->where('id', $branchId)->value('branch_name') ?? 'Branch #'.$branchId;
    }

    private function appointmentQuery(?int $branchId, Carbon $start, Carbon $end)
    {
        return Appointment::query()
            ->whereBetween('appointment_timestamp', [$start->toDateTimeString(), $end->copy()->endOfDay()->toDateTimeString()])
            ->when(! is_null($branchId), fn($q) => $q->where('branch_id', $branchId));
    }

    private function transactionQuery(?int $branchId, Carbon $start, Carbon $end)
    {
        return AccountTransaction::query()
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->when(! is_null($branchId), fn($q) => $q->where('branch_id', $branchId));
    }

    private function patientQuery(?int $branchId, Carbon $start, Carbon $end)
    {
        return Patient::query()
            ->whereBetween('registeration_date', [$start->toDateString(), $end->toDateString()])
            ->when(! is_null($branchId), fn($q) => $q->where('branch_id', $branchId));
    }

    private function creditBalances(?int $branchId): Collection
    {
        $query = Patient::query();
        if (! is_null($branchId)) {
            $query->where('branch_id', $branchId);
        }

        return $query->get(['id', 'total_amount_due', 'updated_at'])
            ->map(fn ($p) => (object) [
                'account_id' => $p->id,
                'balance' => max(0, (float) ($p->total_amount_due ?? 0)),
                'oldest_debit_date' => $p->updated_at, // Consider using a dedicated 'last_transaction_date' for accuracy
            ])
            ->filter(fn ($row) => $row->balance > 0)
            ->values();
    }

    public function treatmentPlanQuery(?int $branchId, Carbon $start, Carbon $end)
    {
        return TreatmentPlan::query()
            ->whereBetween('start_date', [$start->toDateString(), $end->copy()->endOfDay()->toDateString()])
            ->when(! is_null($branchId), fn($q) => $q->where('branch_id', $branchId));
    }

    private function buildOperationalSummary(?int $branchId, Carbon $start, Carbon $end): array
    {
        $appointments = $this->appointmentQuery($branchId, $start, $end);
        $treatments = $this->treatmentPlanQuery($branchId, $start, $end);
        $completed = (clone $appointments)->where('status', 'completed')->count();
        $cancelled = (clone $appointments)->where('status', 'cancelled')->count();
        $noShow = (clone $appointments)->where('status', 'no_show')->count();
        $totalAppointments = (clone $appointments)->count();
        $newPatients = (int) (clone $this->patientQuery($branchId, $start, $end))->count();

        $collectionRate = 0.0;
        $grossRevenue = (float) (clone $appointments)->sum('appointment_cost') + (float) (clone $treatments)->sum('total_estimated_cost');
        if ($grossRevenue > 0) {
            $cashCollected = (float) (clone $this->transactionQuery($branchId, $start, $end))
                ->where('transaction_type', 'in')
                ->sum('amount');
            $collectionRate = round(($cashCollected / $grossRevenue) * 100, 2);
        }

        return [
            'appointments_total' => $totalAppointments,
            'appointments_completed' => $completed,
            'appointments_cancelled' => $cancelled,
            'appointments_no_show' => $noShow,
            'new_patients' => $newPatients,
            'collection_rate' => $collectionRate,
        ];
    }
}
