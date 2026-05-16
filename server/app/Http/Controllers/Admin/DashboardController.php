<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountTransaction;
use App\Models\Appointment;
use App\Models\AppointmentPatient;
use App\Models\Branch;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\TreatmentPlan;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Log::info('Dashboard index called', ['branch_id' => $request->input('branch_id'), 'days' => $request->input('days', 30)]);
        
        $branchId = $this->effectiveBranchId($request);
        $days = (int) $request->input('days', 30);
        $days = in_array($days, [7, 30, 90], true) ? $days : 30;

        $end = Carbon::today();
        $start = (clone $end)->subDays($days - 1)->startOfDay();
        $previousEnd = (clone $start)->subDay()->endOfDay();
        $previousStart = (clone $previousEnd)->subDays($days - 1)->startOfDay();

        $branches = $this->branchOptions($branchId);

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
                'branches' => $branches,
                'selected_branch_id' => $branchId,
                'selected_period_days' => $days,
            ],
            'kpis' => $this->buildKpis($branchId, $start, $end, $previousStart, $previousEnd),
            'charts' => $this->buildCharts($branchId, $start, $end),
            'alerts' => $this->buildAlerts($branchId, $start, $end),
            'recent' => $this->buildRecentTables($branchId, $start, $end),
        ];

        return response()->json($payload);
    }

    private function buildKpis(?int $branchId, Carbon $start, Carbon $end, Carbon $previousStart, Carbon $previousEnd): array
    {
        $appointmentQuery = $this->appointmentQuery($branchId, $start, $end);
        $previousAppointmentQuery = $this->appointmentQuery($branchId, $previousStart, $previousEnd);

        $transactionQuery = $this->transactionQuery($branchId, $start, $end);
        $previousTransactionQuery = $this->transactionQuery($branchId, $previousStart, $previousEnd);

        $patientQuery = $this->patientQuery($branchId, $start, $end);
        $previousPatientQuery = $this->patientQuery($branchId, $previousStart, $previousEnd);

        // FIXED: Fetch plans created in this period. Only need id & status for KPIs.
        $plans = $this->planQuery($branchId, $start, $end)->get(['id', 'status']);

        $cashCollected = (float) (clone $transactionQuery)->where('transaction_type', 'in')->sum('amount');
        $appointmentCharges = (float) (clone $appointmentQuery)->sum('appointment_cost');

        $previousCashCollected = (float) (clone $previousTransactionQuery)->where('transaction_type', 'in')->sum('amount');
        $previousAppointmentCharges = (float) (clone $previousAppointmentQuery)->sum('appointment_cost');

        $completedAppointments = (clone $appointmentQuery)->where('status', 'Completed')->count();
        $noShowAppointments = (clone $appointmentQuery)->where('status', 'No Show')->count();
        $totalAppointments = (clone $appointmentQuery)->count();

        $newPatients = (clone $patientQuery)->count();
        $previousNewPatients = (clone $previousPatientQuery)->count();

        $averageProductionPerVisit = $totalAppointments > 0
            ? round($appointmentCharges / $totalAppointments, 2)
            : 0.0;

        $collectionRate = $appointmentCharges > 0
            ? round(($cashCollected / $appointmentCharges) * 100, 2)
            : 100.0;

        $pricingAudit = $this->pricingAudit((clone $appointmentQuery)->get(['id', 'appointment_cost']));

        $creditBalances = $this->creditBalances($branchId);
        $outstandingAR = round($creditBalances->sum('balance'), 2);

        $patientRetention = $this->patientRetentionRate($branchId, $start, $end);
        $repeatPatientRate = $this->repeatPatientRate($branchId, $start, $end);

        $sameDayAppointments = (clone $appointmentQuery)->get(['id', 'branch_id', 'patient_id', 'appointment_timestamp']);
        $sameDayTransactions = (clone $transactionQuery)->where('transaction_type', 'in')->get(['transaction_date', 'branch_id', 'patient_id', 'transaction_type']);
        $sameDayCollectionRate = $this->sameDayCollectionRate($sameDayAppointments, $sameDayTransactions);

        $newPatientGrowth = $this->growthRate($newPatients, $previousNewPatients);
        $cashGrowth = $this->growthRate($cashCollected, $previousCashCollected);

        // FIXED: Both rates now use the EXACT SAME $plans collection
        $treatmentPlanAcceptance = $this->planAcceptanceRate($plans);
        $treatmentCompletionRate = $this->treatmentCompletionRate($plans);

        return [
            [
                'key' => 'cash_collected',
                'label' => 'Cash Collected',
                'value' => $cashCollected,
                'formatted' => $this->money($cashCollected),
                'trend' => $cashGrowth,
                'trend_label' => $this->trendLabel($cashGrowth),
                'tone' => $this->trendTone($cashGrowth),
                'help' => 'Actual money received in the selected period.',
            ],
            [
                'key' => 'outstanding_ar',
                'label' => 'Outstanding Credit',
                'value' => $outstandingAR,
                'formatted' => $this->money($outstandingAR),
                'trend' => null,
                'trend_label' => $outstandingAR > 0 ? 'Watch closely' : 'Clean',
                'tone' => $outstandingAR > 0 ? 'warn' : 'good',
                'help' => 'Balances still owed by patients.',
            ],
            [
                'key' => 'collection_rate',
                'label' => 'Collection Rate',
                'value' => $collectionRate,
                'formatted' => $this->percent($collectionRate),
                'trend' => null,
                'trend_label' => $collectionRate >= 85 ? 'Healthy' : 'Needs action',
                'tone' => $collectionRate >= 85 ? 'good' : 'warn',
                'help' => 'Cash collected versus appointment charges.',
            ],
            [
                'key' => 'avg_ppv',
                'label' => 'Avg Production / Visit',
                'value' => $averageProductionPerVisit,
                'formatted' => $this->money($averageProductionPerVisit),
                'trend' => null,
                'trend_label' => 'Per appointment',
                'tone' => 'neutral',
                'help' => 'Useful for judging chair-time value.',
            ],
            [
                'key' => 'no_show_rate',
                'label' => 'No-Show Rate',
                'value' => $totalAppointments > 0 ? round(($noShowAppointments / $totalAppointments) * 100, 2) : 0.0,
                'formatted' => $this->percent($totalAppointments > 0 ? ($noShowAppointments / $totalAppointments) * 100 : 0),
                'trend' => null,
                'trend_label' => ($totalAppointments > 0 ? ($noShowAppointments / $totalAppointments) * 100 : 0) <= 5 ? 'Good' : 'Reduce',
                'tone' => ($totalAppointments > 0 ? ($noShowAppointments / $totalAppointments) * 100 : 0) <= 5 ? 'good' : 'warn',
                'help' => 'Missed appointments within the period.',
            ],
            [
                'key' => 'new_patients',
                'label' => 'New Patients',
                'value' => $newPatients,
                'formatted' => number_format($newPatients),
                'trend' => $newPatientGrowth,
                'trend_label' => $this->trendLabel($newPatientGrowth),
                'tone' => $this->trendTone($newPatientGrowth),
                'help' => 'Registered patients in the selected period.',
            ],
            [
                'key' => 'same_day_collection',
                'label' => 'Same-Day Collection',
                'value' => $sameDayCollectionRate,
                'formatted' => $this->percent($sameDayCollectionRate),
                'trend' => null,
                'trend_label' => 'Local strength',
                'tone' => 'good',
                'help' => 'Appointments that were collected on the same day.',
            ],
            [
                'key' => 'pricing_discipline',
                'label' => 'Pricing Discipline',
                'value' => $pricingAudit['in_range_rate'],
                'formatted' => $this->percent($pricingAudit['in_range_rate']),
                'trend' => null,
                'trend_label' => $pricingAudit['in_range_count'].'/'.$pricingAudit['audited_count'].' matched',
                'tone' => $pricingAudit['in_range_rate'] >= 70 ? 'good' : 'warn',
                'help' => 'Share of appointments priced exactly at procedure base.',
            ],
            [
                'key' => 'patient_retention',
                'label' => 'Patient Retention',
                'value' => $patientRetention,
                'formatted' => $this->percent($patientRetention),
                'trend' => null,
                'trend_label' => 'Return behavior',
                'tone' => $patientRetention >= 50 ? 'good' : 'warn',
                'help' => 'Repeat patients over active patients.',
            ],
            [
                'key' => 'repeat_patient_rate',
                'label' => 'Repeat Patient Rate',
                'value' => $repeatPatientRate,
                'formatted' => $this->percent($repeatPatientRate),
                'trend' => null,
                'trend_label' => 'Repeat behavior',
                'tone' => $repeatPatientRate >= 40 ? 'good' : 'warn',
                'help' => 'Patients with more than one appointment.',
            ],
            [
                'key' => 'plan_acceptance',
                'label' => 'Case Acceptance',
                'value' => $treatmentPlanAcceptance,
                'formatted' => $this->percent($treatmentPlanAcceptance),
                'trend' => null,
                'trend_label' => 'Plans accepted',
                'tone' => $treatmentPlanAcceptance >= 70 ? 'good' : 'warn',
                'help' => 'Accepted treatment plans versus all created plans.',
            ],
            [
                'key' => 'plan_completion',
                'label' => 'Treatment Completion',
                'value' => $treatmentCompletionRate,
                'formatted' => $this->percent($treatmentCompletionRate),
                'trend' => null,
                'trend_label' => 'Plans completed',
                'tone' => $treatmentCompletionRate >= 65 ? 'good' : 'warn',
                'help' => 'Completed work against accepted treatment plans.',
            ],
        ];
    }

    private function buildCharts(?int $branchId, Carbon $start, Carbon $end): array
    {
        $days = collect();
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $days->push($date->copy()->toDateString());
        }

        $cashData = $this->transactionQuery($branchId, $start, $end)
            ->where('transaction_type', 'in')
            ->selectRaw('transaction_date, SUM(amount) as total')
            ->groupBy('transaction_date')
            ->pluck('total', 'transaction_date');

        $chargeData = $this->appointmentQuery($branchId, $start, $end)
            ->selectRaw('DATE(appointment_timestamp) as date, SUM(appointment_cost) as total')
            ->groupBy(DB::raw('DATE(appointment_timestamp)'))
            ->pluck('total', 'date');

        $appointmentCounts = $this->appointmentQuery($branchId, $start, $end)
            ->selectRaw('DATE(appointment_timestamp) as date, COUNT(*) as count')
            ->groupBy(DB::raw('DATE(appointment_timestamp)'))
            ->pluck('count', 'date');

        // FIXED: Direct query for chart counts. Uses created_at to match KPI logic.
        $treatmentCounts = TreatmentPlan::query()
            ->whereBetween('created_at', [$start->toDateTimeString(), $end->copy()->endOfDay()->toDateTimeString()])
            ->when(!is_null($branchId), fn($q) => $q->where('branch_id', $branchId))
            ->whereIn('status', ['accepted', 'partially_accepted', 'completed'])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('count', 'date');

        $patientCounts = $this->patientQuery($branchId, $start, $end)
            ->selectRaw('registeration_date, COUNT(*) as count')
            ->groupBy('registeration_date')
            ->pluck('count', 'registeration_date');

        $cashSeries = $days->map(fn ($day) => (float) ($cashData[$day] ?? 0.0))->values();
        $chargeSeries = $days->map(fn ($day) => (float) ($chargeData[$day] ?? 0.0))->values();
        $appointmentSeries = $days->map(fn ($day) => (int) ($appointmentCounts[$day] ?? 0))->values();
        $treatmentSeries = $days->map(fn ($day) => (int) ($treatmentCounts[$day] ?? 0))->values();
        $patientSeries = $days->map(fn ($day) => (int) ($patientCounts[$day] ?? 0))->values();

        $appointmentsForMix = $this->appointmentQuery($branchId, $start, $end)->get(['id', 'visit_type']);
        $procedureMix = $this->procedureMixFromAppointments($appointmentsForMix);
        $visitMix = $appointmentsForMix->groupBy(fn ($row) => $row->visit_type ?? 'planned')->map->count()->sortDesc();

        $patientSource = $this->patientQuery($branchId, $start, $end)
            ->get(['referral_source'])
            ->groupBy(fn ($row) => $row->referral_source ?? 'Unknown')
            ->map->count()
            ->sortDesc();

        $pricingAudit = $this->pricingAudit($this->appointmentQuery($branchId, $start, $end)->get(['id', 'appointment_cost']));
        $aging = $this->creditAgingBuckets($branchId);

        return [
            'cash_flow' => [
                'labels' => $days->values()->all(),
                'datasets' => [
                    ['label' => 'Cash in', 'data' => $cashSeries->all()],
                    ['label' => 'Appointment charges', 'data' => $chargeSeries->all()],
                ],
            ],
            'credit_aging' => [
                'labels' => array_keys($aging),
                'datasets' => [
                    ['label' => 'Outstanding balance', 'data' => array_values($aging)],
                ],
            ],
            'treatment_mix' => [
                'labels' => $procedureMix->keys()->all(),
                'datasets' => [
                    ['label' => 'Procedures', 'data' => $procedureMix->values()->all()],
                ],
            ],
            'patient_behavior' => [
                'labels' => $days->values()->all(),
                'datasets' => [
                    ['label' => 'Appointments', 'data' => $appointmentSeries->all()],
                    ['label' => 'Treatments Accepted', 'data' => $treatmentSeries->all()],
                    ['label' => 'New patients', 'data' => $patientSeries->all()],
                ],
            ],
            'pricing_discipline' => [
                'labels' => ['In Range', 'Above Base', 'Below Base'],
                'datasets' => [
                    ['label' => 'Pricing audit', 'data' => [
                        $pricingAudit['in_range_count'],
                        $pricingAudit['above_count'],
                        $pricingAudit['below_count'],
                    ]],
                ],
            ],
            'referral_source' => [
                'labels' => $patientSource->keys()->all(),
                'datasets' => [
                    ['label' => 'Patients', 'data' => $patientSource->values()->all()],
                ],
            ],
            'visit_type' => [
                'labels' => $visitMix->keys()->all(),
                'datasets' => [
                    ['label' => 'Visits', 'data' => $visitMix->values()->all()],
                ],
            ],
        ];
    }

    private function buildAlerts(?int $branchId, Carbon $start, Carbon $end): array
    {
        $appointmentQuery = $this->appointmentQuery($branchId, $start, $end);
        $totalAppointments = (clone $appointmentQuery)->count();
        $noShowCount = (clone $appointmentQuery)->where('status', 'No Show')->count();
        $noShowRate = $totalAppointments > 0 ? ($noShowCount / $totalAppointments) * 100 : 0;

        $pricingAudit = $this->pricingAudit((clone $appointmentQuery)->get(['id', 'appointment_cost']));
        $creditCollected = (float) $this->transactionQuery($branchId, $start, $end)->where('transaction_type', 'in')->sum('amount');
        $appointmentCharges = (float) (clone $appointmentQuery)->sum('appointment_cost');

        $collectionRate = $appointmentCharges > 0 ? ($creditCollected / $appointmentCharges) * 100 : 100;
        $outstandingAR = $this->creditBalances($branchId)->sum('balance');

        $alerts = [];

        if ($outstandingAR > 0) {
            $alerts[] = [
                'tone' => 'warn',
                'title' => 'Credit balances need attention',
                'message' => 'There is '.$this->money($outstandingAR).' still outstanding across accounts. Review the oldest balances first.',
                'meta' => ['action' => 'Review overdue accounts'],
            ];
        }

        if ($noShowRate > 10) {
            $alerts[] = [
                'tone' => 'warn',
                'title' => 'No-show rate is high',
                'message' => 'The no-show rate is '.$this->percent($noShowRate).'. Review reminder timing, slot length, and booking lead time.',
                'meta' => ['action' => 'Audit scheduling process'],
            ];
        }

        if ($pricingAudit['in_range_rate'] < 70) {
            $alerts[] = [
                'tone' => 'warn',
                'title' => 'Pricing is drifting from base',
                'message' => 'Only '.$this->percent($pricingAudit['in_range_rate']).' of audited appointments match procedure base price.',
                'meta' => ['action' => 'Check appointment charges'],
            ];
        }

        if ($collectionRate < 85) {
            $alerts[] = [
                'tone' => 'bad',
                'title' => 'Collection rate is below target',
                'message' => 'The collection rate is '.$this->percent($collectionRate).'. Tighten credit policy and increase upfront payment.',
                'meta' => ['action' => 'Review payment rules'],
            ];
        }

        if (empty($alerts)) {
            $alerts[] = [
                'tone' => 'good',
                'title' => 'Dashboard looks healthy',
                'message' => 'No major warning thresholds were triggered in the current period.',
                'meta' => ['action' => 'Continue monitoring'],
            ];
        }

        return $alerts;
    }

    private function buildRecentTables(?int $branchId, Carbon $start, Carbon $end): array
    {
        $recentAppointments = $this->appointmentQuery($branchId, $start, $end)
            ->orderByDesc('appointment_timestamp')
            ->limit(10)
            ->get();

        // FIXED: Fetch actual TreatmentPlan records. Eager load procedure for names.
        $recentTreatments = TreatmentPlan::query()
            ->with('procedure:id,name')
            ->whereBetween('created_at', [$start->toDateTimeString(), $end->copy()->endOfDay()->toDateTimeString()])
            ->when(!is_null($branchId), fn($q) => $q->where('branch_id', $branchId))
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $recentTransactions = $this->transactionQuery($branchId, $start, $end)
            ->orderByDesc('transaction_date')
            ->limit(10)
            ->get();

        $summaries = $this->appointmentPricingSummaries($recentAppointments);

        $mappedAppointments = $recentAppointments->map(function ($row) use ($summaries, $branchId) {
            $summary = $summaries[(int) $row->id] ?? [
                'base_total' => 0.0,
                'procedure_names' => [],
                'procedure_count' => 0,
            ];

            $actual = (float) ($row->appointment_cost ?? 0);
            $base = (float) ($summary['base_total'] ?? 0);
            $procedureNames = $summary['procedure_names'] ?? [];
            $procedureLabel = ! empty($procedureNames) ? implode(', ', $procedureNames) : 'Unlinked procedure';

            return [
                'id' => $row->id,
                'patient_name' => $this->resolvePatientName($row->patient_id ?? null),
                'branch_name' => $this->resolveBranchName($row->branch_id ?? $branchId),
                'procedure_name' => $procedureLabel,
                'treatment_type' => $procedureLabel,
                'diagnosis' => $row->notes ?? $row->description ?? $row->reason ?? $row->visit_type ?? '',
                'date' => $row->appointment_timestamp ?? $row->appointment_date ?? $row->created_at,
                'status' => $row->status ?? 'scheduled',
                'amount' => $actual,
                'appointment_cost' => $actual,
                'base_price' => $base,
                'variance' => round($actual - $base, 2),
                'range_fit' => $this->appointmentPricingStatusLabel($actual, $base),
                'balance' => $this->accountBalanceForPatient($row->patient_id ?? null),
                'procedure_count' => (int) ($summary['procedure_count'] ?? 0),
                'procedure_names' => $procedureNames,
            ];
        })->values()->all();

        // FIXED: Correctly map TreatmentPlan schema columns
        $mappedTreatments = $recentTreatments->map(function ($row) use ($branchId) {
            return [
                'id' => $row->id,
                'patient_name' => $this->resolvePatientName($row->patient_id ?? null),
                'branch_name' => $this->resolveBranchName($row->branch_id ?? $branchId),
                'procedure_name' => $row->procedure?->name ?? 'Unknown Procedure',
                'treatment_type' => $row->procedure?->name ?? 'Clinical Procedure',
                'diagnosis' => 'Treatment Plan Entry', // No notes column in schema
                'date' => $row->start_date ?? $row->created_at,
                'status' => $row->status ?? 'proposed',
                'amount' => (float) ($row->total_estimated_cost ?? 0),
            ];
        })->values()->all();

        return [
            'appointments' => $mappedAppointments,
            'treatments' => $mappedTreatments,
            'transactions' => $recentTransactions->map(fn ($row) => [
                'id' => $row->id,
                'branch_name' => $this->resolveBranchName($row->branch_id ?? $branchId),
                'transaction_type' => $row->transaction_type,
                'amount' => (float) $row->amount,
                'date' => $row->transaction_date,
                'description' => $row->description,
            ])->values()->all(),
        ];
    }

    private function appointmentQuery(?int $branchId, Carbon $start, Carbon $end)
    {
        return Appointment::query()
            ->whereBetween('appointment_timestamp', [$start->toDateTimeString(), $end->copy()->endOfDay()->toDateTimeString()])
            ->when(! is_null($branchId), fn($q) => $q->where('branch_id', $branchId));
    }

    private function treatmentQuery(?int $branchId, Carbon $start, Carbon $end)
    {
        return TreatmentPlan::query()
            ->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
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

    private function planQuery(?int $branchId, Carbon $start, Carbon $end)
    {
        return TreatmentPlan::query()
            ->whereBetween('created_at', [$start->toDateTimeString(), $end->copy()->endOfDay()->toDateTimeString()])
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
                'oldest_debit_date' => $p->updated_at,
            ])
            ->filter(fn ($row) => $row->balance > 0)
            ->values();
    }

    private function creditAgingBuckets(?int $branchId): array
    {
        $buckets = ['0-30' => 0.0, '31-60' => 0.0, '61-90' => 0.0, '90+' => 0.0];
        $balances = $this->creditBalances($branchId);

        foreach ($balances as $row) {
            $days = $row->oldest_debit_date ? Carbon::parse($row->oldest_debit_date)->diffInDays(now()) : 0;
            if ($days <= 30) $buckets['0-30'] += $row->balance;
            elseif ($days <= 60) $buckets['31-60'] += $row->balance;
            elseif ($days <= 90) $buckets['61-90'] += $row->balance;
            else $buckets['90+'] += $row->balance;
        }

        return array_map(fn ($v) => round($v, 2), $buckets);
    }

    private function pricingAudit(Collection $appointments): array
    {
        $audited = 0; $matched = 0; $above = 0; $below = 0; $totalDeviation = 0.0;
        $summaries = $this->appointmentPricingSummaries($appointments);

        foreach ($appointments as $appointment) {
            $summary = $summaries[(int) $appointment->id] ?? null;
            $base = (float) ($summary['base_total'] ?? 0);
            if ($base <= 0) continue;

            $audited++;
            $actual = (float) ($appointment->appointment_cost ?? 0);
            $diff = round($actual - $base, 2);

            if (abs($diff) < 0.01) $matched++;
            elseif ($diff > 0) { $above++; $totalDeviation += abs($diff); }
            else { $below++; $totalDeviation += abs($diff); }
        }

        $outOfRange = $above + $below;
        return [
            'audited_count' => $audited, 'in_range_count' => $matched,
            'above_count' => $above, 'below_count' => $below, 'out_of_range_count' => $outOfRange,
            'in_range_rate' => $audited > 0 ? round(($matched / $audited) * 100, 2) : 0.0,
            'out_of_range_rate' => $audited > 0 ? round(($outOfRange / $audited) * 100, 2) : 0.0,
            'total_deviation' => round($totalDeviation, 2),
            'average_deviation' => $outOfRange > 0 ? round($totalDeviation / $outOfRange, 2) : 0.0,
        ];
    }

    private function appointmentPricingSummaries(Collection $appointments): array
    {
        $appointmentIds = $appointments->pluck('id')->filter()->map(fn ($v) => (int) $v)->values()->all();
        if (empty($appointmentIds)) return [];

        $pivotRows = DB::table('appointment_procedure')->whereIn('appointment_id', $appointmentIds)->get(['appointment_id', 'procedure_id']);
        if ($pivotRows->isEmpty()) return [];

        $procedureIds = $pivotRows->pluck('procedure_id')->filter()->map(fn ($v) => (int) $v)->unique()->values()->all();
        if (empty($procedureIds)) return [];

        $procedureMap = class_exists(Procedure::class)
            ? Procedure::query()->whereIn('id', $procedureIds)->get(['id', 'name', 'base_price'])->keyBy('id')
            : DB::table('procedures')->whereIn('id', $procedureIds)->get(['id', 'name', 'base_price'])->keyBy('id');

        $summary = [];
        foreach ($pivotRows->groupBy('appointment_id') as $appId => $rows) {
            $baseTotal = 0.0; $names = []; $unique = [];
            foreach ($rows as $row) {
                $proc = $procedureMap->get($row->procedure_id);
                if (!$proc) continue;
                $unique[(int) $row->procedure_id] = true;
                $baseTotal += (float) ($proc->base_price ?? 0);
                $name = trim((string) ($proc->name ?? ''));
                if ($name !== '') $names[] = $name;
            }
            $summary[(int) $appId] = [
                'base_total' => round($baseTotal, 2),
                'procedure_names' => array_values(array_unique($names)),
                'procedure_count' => count($unique),
            ];
        }
        return $summary;
    }

    private function procedureMixFromAppointments(Collection $appointments): Collection
    {
        $summaries = $this->appointmentPricingSummaries($appointments);
        $counts = [];
        foreach ($summaries as $summary) {
            foreach (($summary['procedure_names'] ?? []) as $name) {
                $counts[$name] = ($counts[$name] ?? 0) + 1;
            }
        }
        return collect($counts)->sortDesc();
    }

    private function appointmentPricingStatusLabel(float $actual, float $base): string
    {
        if ($base <= 0.0) return 'No Procedure';
        if (abs($actual - $base) < 0.01) return 'In Range';
        return $actual > $base ? 'Above Base' : 'Below Base';
    }

    // FIXED: Status list matches migration comment: proposed, accepted, partially_accepted, rejected
    private function planAcceptanceRate(Collection $plans): float
    {
        if ($plans->isEmpty()) return 0.0;
        $accepted = $plans->filter(fn ($row) => in_array($row->status, ['accepted', 'partially_accepted', 'completed'], true))->count();
        return round(($accepted / $plans->count()) * 100, 2);
    }

    // FIXED: Uses same $plans collection. Checks for 'completed' status.
    private function treatmentCompletionRate(Collection $plans): float
    {
        $accepted = $plans->filter(fn ($row) => in_array($row->status, ['accepted', 'partially_accepted', 'completed'], true));
        if ($accepted->isEmpty()) return 0.0;
        
        $completed = $accepted->filter(fn ($row) => $row->status === 'completed')->count();
        return round(($completed / $accepted->count()) * 100, 2);
    }

    private function patientRetentionRate(?int $branchId, Carbon $start, Carbon $end): float
    {
        $patients = $this->patientQuery($branchId, $start, $end)->get(['id']);
        if ($patients->isEmpty()) return 0.0;

        $patientIds = $patients->pluck('id')->all();
        $counts = AppointmentPatient::query()
            ->whereIn('patient_id', $patientIds)
            ->when(!is_null($branchId), fn($q) => $q->whereHas('appointment', fn($aq) => $aq->where('branch_id', $branchId)))
            ->select('patient_id', DB::raw('count(*) as visits'))
            ->groupBy('patient_id')
            ->get();

        $returning = $counts->filter(fn ($row) => (int) $row->visits > 1)->count();
        return round(($returning / max($patients->count(), 1)) * 100, 2);
    }

    private function repeatPatientRate(?int $branchId, Carbon $start, Carbon $end): float
    {
        $linked = AppointmentPatient::query()
            ->whereHas('appointment', function ($q) use ($branchId, $start, $end) {
                $q->whereBetween('appointment_timestamp', [$start->toDateTimeString(), $end->copy()->endOfDay()->toDateTimeString()]);
                if (!is_null($branchId)) $q->where('branch_id', $branchId);
            })
            ->get(['patient_id', 'appointment_id']);

        if ($linked->isEmpty()) return 0.0;
        $byPatient = $linked->groupBy('patient_id')->map->count();
        $repeat = $byPatient->filter(fn ($c) => $c > 1)->count();
        return round(($repeat / max($byPatient->count(), 1)) * 100, 2);
    }

    private function sameDayCollectionRate(Collection $appointments, Collection $transactions): float
    {
        if ($appointments->isEmpty()) return 0.0;
        $collected = 0;
        foreach ($appointments as $apt) {
            $aptDate = Carbon::parse($apt->appointment_timestamp)->toDateString();
            if ($transactions->contains(fn($t) => 
                $t->transaction_type === 'in' &&
                (string) $t->transaction_date === $aptDate &&
                (int) $t->branch_id === (int) $apt->branch_id &&
                (int) ($t->patient_id ?? 0) === (int) ($apt->patient_id ?? 0)
            )) {
                $collected++;
            }
        }
        return round(($collected / $appointments->count()) * 100, 2);
    }

    private function growthRate(float|int $current, float|int $previous): float
    {
        $current = (float) $current; $previous = (float) $previous;
        if ($previous == 0.0) return $current > 0 ? 100.0 : 0.0;
        return round((($current - $previous) / abs($previous)) * 100, 2);
    }

    private function trendLabel(?float $growth): string
    {
        if ($growth === null) return '-';
        if ($growth > 0) return '+' . number_format($growth, 1) . '%';
        if ($growth < 0) return number_format($growth, 1) . '%';
        return 'Flat';
    }

    private function trendTone(?float $growth): string
    {
        if ($growth === null) return 'neutral';
        return $growth > 0 ? 'up' : ($growth < 0 ? 'down' : 'neutral');
    }

    private function money(float|int $value): string { return number_format((float) $value, 0); }
    private function percent(float|int $value): string { return number_format((float) $value, 1) . '%'; }

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

    private function resolvePatientName(?int $patientId): string
    {
        if (is_null($patientId)) return 'Unknown patient';
        $p = Patient::query()->where('id', $patientId)->first(['f_name', 'l_name']);
        return $p ? trim($p->f_name.' '.$p->l_name) : 'Patient #'.$patientId;
    }

    private function accountBalanceForPatient(?int $patientId): float
    {
        if (is_null($patientId)) return 0.0;
        return (float) (Patient::query()->where('id', $patientId)->value('total_amount_due') ?? 0);
    }
}