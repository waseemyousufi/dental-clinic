<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Appointment;
use App\Models\AppointmentPatient;
use App\Models\Branch;
use App\Models\Patient;
use App\Models\Treatment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the KPI dashboard data.
     *
     * Returns JSON for API/AJAX requests and a view payload for server-rendered pages.
     */
    public function index(Request $request): JsonResponse|View
    {
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

        if ($request->wantsJson()) {
            return response()->json($payload);
        }

        return view('dashboard.index', [
            'dashboard' => $payload,
        ]);
    }

    private function buildKpis(?int $branchId, Carbon $start, Carbon $end, Carbon $previousStart, Carbon $previousEnd): array
    {
        $appointments = $this->appointmentQuery($branchId, $start, $end)->get();
        $previousAppointments = $this->appointmentQuery($branchId, $previousStart, $previousEnd)->get();

        $treatments = $this->treatmentQuery($branchId, $start, $end)->get();
        $previousTreatments = $this->treatmentQuery($branchId, $previousStart, $previousEnd)->get();

        $transactions = $this->transactionQuery($branchId, $start, $end)->get();
        $previousTransactions = $this->transactionQuery($branchId, $previousStart, $previousEnd)->get();

        $patients = $this->patientQuery($branchId, $start, $end)->get();
        $previousPatients = $this->patientQuery($branchId, $previousStart, $previousEnd)->get();

        $plans = $this->planQuery($branchId, $start, $end)->get();

        $cashCollected = (float) $transactions->where('transaction_type', 'in')->sum('amount');
        $creditIssued = (float) $transactions->where('transaction_type', 'debit')->sum('amount');

        $previousCashCollected = (float) $previousTransactions->where('transaction_type', 'in')->sum('amount');
        $previousCreditIssued = (float) $previousTransactions->where('transaction_type', 'debit')->sum('amount');

        $activePatients = $this->countActivePatients($branchId, $start, $end);
        $previousActivePatients = $this->countActivePatients($branchId, $previousStart, $previousEnd);

        $completedAppointments = $appointments->where('status', 'Completed')->count();
        $cancelledAppointments = $appointments->where('status', 'Cancelled')->count();
        $noShowAppointments = $appointments->where('status', 'No Show')->count();
        $totalAppointments = $appointments->count();

        $completedTreatments = $treatments->where('status', 'completed')->count();
        $inProgressTreatments = $treatments->where('status', 'in_progress')->count();
        $totalTreatments = $treatments->count();

        $newPatients = $patients->count();
        $previousNewPatients = $previousPatients->count();

        $avgTreatmentValue = $totalTreatments > 0
            ? round((float) $treatments->sum(fn ($row) => (float) ($row->actual_price ?? $row->cost ?? 0)) / $totalTreatments, 2)
            : 0.0;

        $averageProductionPerVisit = $completedAppointments > 0
            ? round($cashCollected / $completedAppointments, 2)
            : 0.0;

        $collectionRate = $creditIssued > 0
            ? round(($cashCollected / $creditIssued) * 100, 2)
            : 100.0;

        $pricingAudit = $this->pricingAudit($treatments);

        $creditBalances = $this->creditBalances($branchId);
        $outstandingAR = round($creditBalances->sum('balance'), 2);

        $patientRetention = $this->patientRetentionRate($branchId, $start, $end);
        $repeatPatientRate = $this->repeatPatientRate($branchId, $start, $end);

        $sameDayCollectionRate = $this->sameDayCollectionRate($treatments, $transactions);

        $newPatientGrowth = $this->growthRate($newPatients, $previousNewPatients);
        $cashGrowth = $this->growthRate($cashCollected, $previousCashCollected);
        $creditGrowth = $this->growthRate($creditIssued, $previousCreditIssued);

        $treatmentPlanAcceptance = $this->planAcceptanceRate($plans);
        $treatmentCompletionRate = $this->treatmentCompletionRate($plans, $treatments);

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
                'help' => 'Cash collected versus debits raised.',
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
                'help' => 'Treatments that were collected on the same day.',
            ],
            [
                'key' => 'pricing_discipline',
                'label' => 'Pricing Discipline',
                'value' => $pricingAudit['in_range_rate'],
                'formatted' => $this->percent($pricingAudit['in_range_rate']),
                'trend' => null,
                'trend_label' => $pricingAudit['in_range_count'].'/'.$pricingAudit['audited_count'].' in range',
                'tone' => $pricingAudit['in_range_rate'] >= 70 ? 'good' : 'warn',
                'help' => 'Share of treatments priced inside procedure ranges.',
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

        $transactions = $this->transactionQuery($branchId, $start, $end)->get();
        $appointments = $this->appointmentQuery($branchId, $start, $end)->get();
        $treatments = $this->treatmentQuery($branchId, $start, $end)->get();
        $patients = $this->patientQuery($branchId, $start, $end)->get();

        $cashSeries = $days->map(fn ($day) => (float) $transactions->where('transaction_date', $day)->where('transaction_type', 'in')->sum('amount'))->values();
        $debitSeries = $days->map(fn ($day) => (float) $transactions->where('transaction_date', $day)->where('transaction_type', 'debit')->sum('amount'))->values();
        $appointmentSeries = $days->map(fn ($day) => $appointments->filter(fn ($row) => Carbon::parse($row->appointment_timestamp)->toDateString() === $day)->count())->values();
        $treatmentSeries = $days->map(fn ($day) => $treatments->where('treatment_date', $day)->count())->values();
        $patientSeries = $days->map(fn ($day) => $patients->where('registeration_date', $day)->count())->values();

        $treatmentMix = $treatments->groupBy('treatment_type')->map->count()->sortDesc();
        $visitMix = $appointments->groupBy(fn ($row) => $row->visit_type ?? 'planned')->map->count()->sortDesc();
        $patientSource = $patients->groupBy(fn ($row) => $row->referral_source ?? 'Unknown')->map->count()->sortDesc();

        $pricingAudit = $this->pricingAudit($treatments);
        $aging = $this->creditAgingBuckets($branchId);

        return [
            'cash_flow' => [
                'labels' => $days->values()->all(),
                'datasets' => [
                    ['label' => 'Cash in', 'data' => $cashSeries->all()],
                    ['label' => 'Debits', 'data' => $debitSeries->all()],
                ],
            ],
            'credit_aging' => [
                'labels' => array_keys($aging),
                'datasets' => [
                    ['label' => 'Outstanding balance', 'data' => array_values($aging)],
                ],
            ],
            'treatment_mix' => [
                'labels' => $treatmentMix->keys()->all(),
                'datasets' => [
                    ['label' => 'Treatments', 'data' => $treatmentMix->values()->all()],
                ],
            ],
            'patient_behavior' => [
                'labels' => $days->values()->all(),
                'datasets' => [
                    ['label' => 'Appointments', 'data' => $appointmentSeries->all()],
                    ['label' => 'Treatments', 'data' => $treatmentSeries->all()],
                    ['label' => 'New patients', 'data' => $patientSeries->all()],
                ],
            ],
            'pricing_discipline' => [
                'labels' => ['In range', 'Out of range'],
                'datasets' => [
                    ['label' => 'Pricing audit', 'data' => [$pricingAudit['in_range_count'], $pricingAudit['out_of_range_count']]],
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
        $appointments = $this->appointmentQuery($branchId, $start, $end)->get();
        $transactions = $this->transactionQuery($branchId, $start, $end)->get();
        $treatments = $this->treatmentQuery($branchId, $start, $end)->get();

        $totalAppointments = $appointments->count();
        $noShowRate = $totalAppointments > 0
            ? ($appointments->where('status', 'No Show')->count() / $totalAppointments) * 100
            : 0;

        $pricingAudit = $this->pricingAudit($treatments);
        $creditCollected = (float) $transactions->where('transaction_type', 'in')->sum('amount');
        $creditIssued = (float) $transactions->where('transaction_type', 'debit')->sum('amount');
        $collectionRate = $creditIssued > 0 ? ($creditCollected / $creditIssued) * 100 : 100;
        $outstandingAR = $this->creditBalances($branchId)->sum('balance');

        $alerts = [];

        if ($outstandingAR > 0) {
            $alerts[] = [
                'tone' => 'warn',
                'title' => 'Credit balances need attention',
                'message' => 'There is '.$this->money($outstandingAR).' still outstanding across accounts. Review the oldest balances first.',
                'meta' => [
                    'action' => 'Review overdue accounts',
                ],
            ];
        }

        if ($noShowRate > 10) {
            $alerts[] = [
                'tone' => 'warn',
                'title' => 'No-show rate is high',
                'message' => 'The no-show rate is '.$this->percent($noShowRate).'. Review reminder timing, slot length, and booking lead time.',
                'meta' => [
                    'action' => 'Audit scheduling process',
                ],
            ];
        }

        if ($pricingAudit['in_range_rate'] < 70) {
            $alerts[] = [
                'tone' => 'warn',
                'title' => 'Pricing is drifting outside the range',
                'message' => 'Only '.$this->percent($pricingAudit['in_range_rate']).' of audited treatments are inside the defined procedure range.',
                'meta' => [
                    'action' => 'Check discounts and overrides',
                ],
            ];
        }

        if ($collectionRate < 85) {
            $alerts[] = [
                'tone' => 'bad',
                'title' => 'Collection rate is below target',
                'message' => 'The collection rate is '.$this->percent($collectionRate).'. Tighten credit policy and increase upfront payment.',
                'meta' => [
                    'action' => 'Review payment rules',
                ],
            ];
        }

        if (empty($alerts)) {
            $alerts[] = [
                'tone' => 'good',
                'title' => 'Dashboard looks healthy',
                'message' => 'No major warning thresholds were triggered in the current period.',
                'meta' => [
                    'action' => 'Continue monitoring',
                ],
            ];
        }

        return $alerts;
    }

    private function buildRecentTables(?int $branchId, Carbon $start, Carbon $end): array
    {
        $recentTreatments = $this->treatmentQuery($branchId, $start, $end)
            ->orderByDesc('treatment_date')
            ->limit(10)
            ->get();

        $recentTransactions = $this->transactionQuery($branchId, $start, $end)
            ->orderByDesc('transaction_date')
            ->limit(10)
            ->get();

        return [
            'treatments' => $recentTreatments->map(fn ($row) => [
                'id' => $row->id,
                'patient_name' => $this->resolvePatientName($row->patient_id ?? null),
                'branch_name' => $this->resolveBranchName($row->branch_id ?? $branchId),
                'treatment_type' => $row->treatment_type,
                'diagnosis' => $row->diagnosis,
                'date' => $row->treatment_date,
                'status' => $row->status ?? 'completed',
                'amount' => (float) ($row->actual_price ?? $row->cost ?? 0),
                'range_fit' => $this->pricingRangeLabel($row),
                'balance' => $this->accountBalanceForPatient($row->patient_id ?? null),
            ])->values()->all(),
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
        $query = Appointment::query()
            ->whereBetween('appointment_timestamp', [$start->toDateTimeString(), $end->copy()->endOfDay()->toDateTimeString()]);

        if (! is_null($branchId)) {
            $query->where('branch_id', $branchId);
        }

        return $query;
    }

    private function treatmentQuery(?int $branchId, Carbon $start, Carbon $end)
    {
        $query = Treatment::query()
            ->whereBetween('treatment_date', [$start->toDateString(), $end->toDateString()]);

        if (! is_null($branchId)) {
            $query->where('branch_id', $branchId);
        }

        return $query;
    }

    private function transactionQuery(?int $branchId, Carbon $start, Carbon $end)
    {
        $query = AccountTransaction::query()
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()]);

        if (! is_null($branchId)) {
            $query->where('branch_id', $branchId);
        }

        return $query;
    }

    private function patientQuery(?int $branchId, Carbon $start, Carbon $end)
    {
        $query = Patient::query()
            ->whereBetween('registeration_date', [$start->toDateString(), $end->toDateString()]);

        if (! is_null($branchId)) {
            $query->where('branch_id', $branchId);
        }

        return $query;
    }

    private function planQuery(?int $branchId, Carbon $start, Carbon $end)
    {
        $query = Treatment::query()
            ->whereBetween('created_at', [$start->toDateTimeString(), $end->copy()->endOfDay()->toDateTimeString()]);

        if (! is_null($branchId)) {
            $query->where('branch_id', $branchId);
        }

        return $query;
    }

    private function creditBalances(?int $branchId): Collection
    {
        // Prefer an accounts table if your schema stores patient balances there.
        // Fallback: derive balances from account transactions grouped by account_id.
        $transactions = AccountTransaction::query();

        if (! is_null($branchId)) {
            $transactions->where('branch_id', $branchId);
        }

        $rows = $transactions->get(['account_id', 'transaction_type', 'amount', 'transaction_date']);

        return $rows
            ->groupBy(fn ($row) => $row->account_id ?? 'unknown')
            ->map(function (Collection $group) {
                $debits = (float) $group->where('transaction_type', 'debit')->sum('amount');
                $credits = (float) $group->where('transaction_type', 'in')->sum('amount');
                $balance = max(0, $debits - $credits);
                $oldestDebit = $group->where('transaction_type', 'debit')->min('transaction_date');

                return (object) [
                    'account_id' => $group->first()->account_id ?? null,
                    'balance' => $balance,
                    'oldest_debit_date' => $oldestDebit,
                ];
            })
            ->filter(fn ($row) => $row->balance > 0)
            ->values();
    }

    private function creditAgingBuckets(?int $branchId): array
    {
        $buckets = [
            '0-30' => 0.0,
            '31-60' => 0.0,
            '61-90' => 0.0,
            '90+' => 0.0,
        ];

        $balances = $this->creditBalances($branchId);

        foreach ($balances as $row) {
            $days = $row->oldest_debit_date
                ? Carbon::parse($row->oldest_debit_date)->diffInDays(now())
                : 0;

            if ($days <= 30) {
                $buckets['0-30'] += $row->balance;
            } elseif ($days <= 60) {
                $buckets['31-60'] += $row->balance;
            } elseif ($days <= 90) {
                $buckets['61-90'] += $row->balance;
            } else {
                $buckets['90+'] += $row->balance;
            }
        }

        return array_map(fn ($value) => round($value, 2), $buckets);
    }

    private function pricingAudit(Collection $treatments): array
    {
        $audited = 0;
        $inRange = 0;
        $outOfRange = 0;
        $totalDeviation = 0.0;

        foreach ($treatments as $treatment) {
            $procedure = $this->resolveProcedure($treatment->treatment_type ?? null);
            if (! $procedure) {
                continue;
            }

            $audited++;
            $price = (float) ($treatment->actual_price ?? $treatment->cost ?? 0);
            $min = (float) ($procedure->min_price ?? $procedure->min ?? 0);
            $max = (float) ($procedure->max_price ?? $procedure->max ?? 0);

            if ($price >= $min && $price <= $max) {
                $inRange++;
            } else {
                $outOfRange++;
                $reference = $price < $min ? $min : $max;
                $totalDeviation += abs($price - $reference);
            }
        }

        return [
            'audited_count' => $audited,
            'in_range_count' => $inRange,
            'out_of_range_count' => $outOfRange,
            'in_range_rate' => $audited > 0 ? round(($inRange / $audited) * 100, 2) : 0.0,
            'out_of_range_rate' => $audited > 0 ? round(($outOfRange / $audited) * 100, 2) : 0.0,
            'total_deviation' => round($totalDeviation, 2),
        ];
    }

    private function planAcceptanceRate(Collection $plans): float
    {
        if ($plans->isEmpty()) {
            return 0.0;
        }

        $accepted = $plans->filter(fn ($row) => in_array($row->status, ['accepted', 'partially_accepted'], true))->count();

        return round(($accepted / $plans->count()) * 100, 2);
    }

    private function treatmentCompletionRate(Collection $plans, Collection $treatments): float
    {
        $acceptedPlans = $plans->filter(fn ($row) => in_array($row->status, ['accepted', 'partially_accepted'], true));
        if ($acceptedPlans->isEmpty()) {
            return 0.0;
        }

        $acceptedPlanIds = $acceptedPlans->pluck('id')->all();
        $completed = $treatments->filter(fn ($row) => in_array($row->treatment_plan_id, $acceptedPlanIds, true) && ($row->status ?? 'completed') === 'completed')->count();

        return round(($completed / $acceptedPlans->count()) * 100, 2);
    }

    private function patientRetentionRate(?int $branchId, Carbon $start, Carbon $end): float
    {
        $patients = $this->patientQuery($branchId, $start, $end)->get();
        if ($patients->isEmpty()) {
            return 0.0;
        }

        $patientIds = $patients->pluck('id')->all();

        $appointmentCounts = AppointmentPatient::query()
            ->whereIn('patient_id', $patientIds)
            ->when(! is_null($branchId), function ($query) use ($branchId) {
                $query->whereHas('appointment', fn ($q) => $q->where('branch_id', $branchId));
            })
            ->select('patient_id', DB::raw('count(*) as visits'))
            ->groupBy('patient_id')
            ->get();

        $returning = $appointmentCounts->filter(fn ($row) => (int) $row->visits > 1)->count();

        return round(($returning / max($patients->count(), 1)) * 100, 2);
    }

    private function repeatPatientRate(?int $branchId, Carbon $start, Carbon $end): float
    {
        $linkedAppointments = AppointmentPatient::query()
            ->whereHas('appointment', function ($query) use ($branchId, $start, $end) {
                $query->whereBetween('appointment_timestamp', [$start->toDateTimeString(), $end->copy()->endOfDay()->toDateTimeString()]);

                if (! is_null($branchId)) {
                    $query->where('branch_id', $branchId);
                }
            })
            ->get(['patient_id', 'appointment_id']);

        if ($linkedAppointments->isEmpty()) {
            return 0.0;
        }

        $byPatient = $linkedAppointments->groupBy('patient_id')->map->count();
        $repeat = $byPatient->filter(fn ($count) => $count > 1)->count();

        return round(($repeat / max($byPatient->count(), 1)) * 100, 2);
    }

    private function sameDayCollectionRate(Collection $treatments, Collection $transactions): float
    {
        if ($treatments->isEmpty()) {
            return 0.0;
        }

        $collectedSameDay = 0;

        foreach ($treatments as $treatment) {
            $hasCashOnSameDay = $transactions->contains(function ($transaction) use ($treatment) {
                return $transaction->transaction_type === 'in'
                    && (string) $transaction->transaction_date === (string) $treatment->treatment_date
                    && (int) $transaction->branch_id === (int) $treatment->branch_id;
            });

            if ($hasCashOnSameDay) {
                $collectedSameDay++;
            }
        }

        return round(($collectedSameDay / $treatments->count()) * 100, 2);
    }

    private function countActivePatients(?int $branchId, Carbon $start, Carbon $end): int
    {
        return AppointmentPatient::query()
            ->whereHas('appointment', function ($query) use ($branchId, $start, $end) {
                $query->whereBetween('appointment_timestamp', [$start->toDateTimeString(), $end->copy()->endOfDay()->toDateTimeString()]);
                if (! is_null($branchId)) {
                    $query->where('branch_id', $branchId);
                }
            })
            ->distinct('patient_id')
            ->count('patient_id');
    }

    private function growthRate(float|int $current, float|int $previous): float
    {
        $current = (float) $current;
        $previous = (float) $previous;

        if ($previous == 0.0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / abs($previous)) * 100, 2);
    }

    private function trendLabel(?float $growth): string
    {
        if ($growth === null) {
            return '-';
        }

        if ($growth > 0) {
            return '+' . number_format($growth, 1) . '%';
        }

        if ($growth < 0) {
            return number_format($growth, 1) . '%';
        }

        return 'Flat';
    }

    private function trendTone(?float $growth): string
    {
        if ($growth === null) {
            return 'neutral';
        }

        if ($growth > 0) {
            return 'up';
        }

        if ($growth < 0) {
            return 'down';
        }

        return 'neutral';
    }

    private function money(float|int $value): string
    {
        return number_format((float) $value, 0);
    }

    private function percent(float|int $value): string
    {
        return number_format((float) $value, 1).'%';
    }

    private function branchOptions(?int $branchId): array
    {
        $query = Branch::query()->orderBy('name');

        if (! is_null($branchId)) {
            $query->where('id', $branchId);
        }

        return $query->get(['id', 'name'])->map(fn ($branch) => [
            'id' => $branch->id,
            'name' => $branch->name,
        ])->values()->all();
    }

    private function resolveBranchName(?int $branchId): string
    {
        if (is_null($branchId)) {
            return 'All branches';
        }

        return Branch::query()->where('id', $branchId)->value('name') ?? 'Branch #'.$branchId;
    }

    private function resolvePatientName(?int $patientId): string
    {
        if (is_null($patientId)) {
            return 'Unknown patient';
        }

        $patient = Patient::query()->where('id', $patientId)->first(['f_name', 'l_name']);

        if (! $patient) {
            return 'Patient #'.$patientId;
        }

        return trim($patient->f_name.' '.$patient->l_name);
    }

    private function resolveProcedure(?string $treatmentType)
    {
        if (blank($treatmentType)) {
            return null;
        }

        $modelClass = '\\App\\Models\\Procedure';
        if (! class_exists($modelClass)) {
            return DB::table('procedures')->whereRaw('LOWER(name) = ?', [strtolower($treatmentType)])->first();
        }

        return $modelClass::query()
            ->whereRaw('LOWER(name) = ?', [strtolower($treatmentType)])
            ->first();
    }

    private function pricingRangeLabel($treatment): string
    {
        $procedure = $this->resolveProcedure($treatment->treatment_type ?? null);
        if (! $procedure) {
            return 'No rule';
        }

        $price = (float) ($treatment->actual_price ?? $treatment->cost ?? 0);
        $min = (float) ($procedure->min_price ?? $procedure->min ?? 0);
        $max = (float) ($procedure->max_price ?? $procedure->max ?? 0);

        if ($price < $min) {
            return 'Below range';
        }

        if ($price > $max) {
            return 'Above range';
        }

        return 'In range';
    }

    private function accountBalanceForPatient(?int $patientId): float
    {
        if (is_null($patientId)) {
            return 0.0;
        }

        $account = Account::query()
            ->where('patient_id', $patientId)
            ->first();

        if ($account && isset($account->balance)) {
            return (float) $account->balance;
        }

        // Fallback to transactions if your accounts table doesn't store balance directly.
        $accountIds = Account::query()
            ->where('patient_id', $patientId)
            ->pluck('id');

        if ($accountIds->isEmpty()) {
            return 0.0;
        }

        $debits = AccountTransaction::query()->whereIn('account_id', $accountIds)->where('transaction_type', 'debit')->sum('amount');
        $credits = AccountTransaction::query()->whereIn('account_id', $accountIds)->where('transaction_type', 'in')->sum('amount');

        return max(0.0, (float) $debits - (float) $credits);
    }

    /**
     * From Controller.php; used in this controller for branch scoping.
     * The method exists in your base controller.
     */
    // protected function effectiveBranchId(): ?int
    // {
    //     return method_exists(get_parent_class($this), 'effectiveBranchId')
    //         ? parent::effectiveBranchId()
    //         : null;
    // }
}
