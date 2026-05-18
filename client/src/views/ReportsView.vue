<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { fetchReports } from '@/api/reports'
import { resolveBranchId } from '@/api/utils/branchParams'
import ReportControlHeader from '@/components/reports/ReportControlHeader.vue'
import ProviderProductivityTable from '@/components/reports/ProviderProductivityTable.vue'

type PeriodKey = '1D' | '7D' | '30D' | '90D' | 'CUSTOM'

interface ReportResponse {
  meta: {
    generated_at: string
    branch_name: string
    period_start?: string
    period_end?: string
  }
  financial_summary: {
    grossRevenue: number
    netCollected: number
    accountsReceivable: number
    treatmentYield: Array<{ name: string; amount: number; percentage: number }>
    grossRevenueTrend?: string
    netCollectedTrend?: string
    accountsReceivableTrend?: string
  }
  provider_productivity: Array<{
    name: string
    patientsTreated: number
    hoursLogged: number
    revenueInvoiced: number
    cashCollected: number
  }>
  operational_summary: {
    appointments_total: number
    appointments_completed: number
    appointments_cancelled: number
    appointments_no_show: number
    new_patients: number
    collection_rate: number
  }
}

const loading = ref(false)
const errorMessage = ref('')
const { t } = useI18n()
const selectedPeriod = ref<PeriodKey>('30D')
const customDateRange = ref<{ start: string | null; end: string | null }>({
  start: null,
  end: null,
})
const selectedBranchId = ref<number | null>(resolveBranchId() ?? null)
const reportData = ref<ReportResponse | null>(null)

const periodToDays: Record<Exclude<PeriodKey, 'CUSTOM'>, number> = {
  '1D': 1,
  '7D': 7,
  '30D': 30,
  '90D': 90,
}

const periodLabels: Record<PeriodKey, string> = {
  '1D': t('reportsView.periodLabels.today'),
  '7D': t('reportsView.periodLabels.last7Days'),
  '30D': t('reportsView.periodLabels.last30Days'),
  '90D': t('reportsView.periodLabels.last90Days'),
  CUSTOM: t('reportsView.periodLabels.customRange'),
}

const financialSummary = computed(
  () =>
    reportData.value?.financial_summary ?? {
      grossRevenue: 0,
      netCollected: 0,
      netProfit: 0,
      accountsReceivable: 0,
      treatmentYield: [],
      grossRevenueTrend: undefined,
      netCollectedTrend: undefined,
      accountsReceivableTrend: undefined,
    },
)

const providerProductivity = computed(() => reportData.value?.provider_productivity ?? [])

const operationalSummary = computed(
  () =>
    reportData.value?.operational_summary ?? {
      appointments_total: 0,
      appointments_completed: 0,
      appointments_cancelled: 0,
      appointments_no_show: 0,
      new_patients: 0,
      collection_rate: 0,
    },
)

const scopeLabel = computed(() => reportData.value?.meta.branch_name || t('reportsView.scopeLabel'))

const generatedAtLabel = computed(() => {
  const raw = reportData.value?.meta.generated_at
  if (!raw) return t('reportsView.notAvailable')
  return new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(raw))
})

const periodLabel = computed(() => periodLabels[selectedPeriod.value])

const periodRangeLabel = computed(() => {
  const start = reportData.value?.meta.period_start
  const end = reportData.value?.meta.period_end
  if (!start || !end) return t('reportsView.rangeNotAvailable')
  const fmt = new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
  })
  return `${fmt.format(new Date(start))} — ${fmt.format(new Date(end))}`
})

const customRangeLabel = computed(() => {
  const { start, end } = customDateRange.value
  if (!start || !end) return t('reportsView.selectDates')
  return `${start} → ${end}`
})

const currency = (value: number) =>
  new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: 'AFN',
    maximumFractionDigits: 0,
  }).format(value)

const percent = (value: number) => `${Math.round(value)}%`

const signedTrendClass = (trend?: string) => {
  const t = (trend ?? '').trim()
  if (!t) return 'trend-neutral'
  const first = t[0]
  if (first === '+') return 'trend-up'
  if (first === '-') return 'trend-down'
  return 'trend-neutral'
}

const loadReports = async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    const payload = await fetchReports({
      branchId: selectedBranchId.value,
      ...(selectedPeriod.value === 'CUSTOM'
        ? {
            startDate: customDateRange.value.start,
            endDate: customDateRange.value.end,
          }
        : {
            days: periodToDays[selectedPeriod.value as Exclude<PeriodKey, 'CUSTOM'>],
          }),
    })

    reportData.value = payload
  } catch {
    errorMessage.value = t('reportsView.loadError')
  } finally {
    loading.value = false
  }
}

const onPeriodChange = async (period: PeriodKey) => {
  selectedPeriod.value = period

  if (period !== 'CUSTOM') {
    customDateRange.value = { start: null, end: null }
    await loadReports()
  }
}

const onCustomRangeChange = async (range: { start: string; end: string }) => {
  customDateRange.value = { start: range.start, end: range.end }

  if (selectedPeriod.value === 'CUSTOM' && range.start && range.end) {
    await loadReports()
  }
}

onMounted(loadReports)
</script>

<template>
  <div class="reports-shell">
    <div class="reports-workspace">
      <!-- <section class="hero-card">
        <div class="hero-copy">
          <div class="hero-eyebrow">Clinic intelligence</div>

          <h1>Reports Dashboard</h1>

          <p>
            Financial performance, operational activity, treatment mix and provider productivity
            presented in a cleaner layout tuned for your sidebar-driven workspace.
          </p>

          <div class="hero-badges">
            <div class="hero-badge">{{ scopeLabel }}</div>
            <div class="hero-badge">{{ selectedPeriod === 'CUSTOM' ? customRangeLabel : periodLabel }}</div>
            <div class="hero-badge">{{ periodRangeLabel }}</div>
            <div class="hero-badge" :class="loading ? 'hero-badge-loading' : 'hero-badge-ready'">
              {{ loading ? t('reportsView.syncing') : t('reportsView.liveData') }}
            </div>
          </div>
        </div>

        <div class="hero-side">
          <div class="hero-stat-card stat-revenue">
            <span>Gross Revenue</span>
            <strong>{{ currency(financialSummary.grossRevenue) }}</strong>
          </div>

          <div class="hero-stat-card stat-collected">
            <span>Net Collected</span>
            <strong>{{ currency(financialSummary.netCollected) }}</strong>
          </div>

          <div class="hero-stat-card stat-ar">
            <span>Accounts Receivable</span>
            <strong>{{ currency(financialSummary.accountsReceivable) }}</strong>
          </div>

          <div class="hero-stat-card stat-rate">
            <span>Collection Rate</span>
            <strong>{{ percent(operationalSummary.collection_rate) }}</strong>
          </div>
        </div>
      </section> -->

      <section class="controls-wrapper">
        <ReportControlHeader
          :selected-period="selectedPeriod"
          :custom-date-range="customDateRange"
          :scope-label="scopeLabel"
          :loading="loading"
          @period-change="onPeriodChange"
          @custom-date-range-change="onCustomRangeChange"
        />
      </section>

      <div v-if="errorMessage" class="error-banner">
        {{ errorMessage }}
      </div>

      <section class="section-block">
        <div class="section-heading">
          <div>
            <h2>{{ t('reportsView.financialOverview.title') }}</h2>
            <p>{{ t('reportsView.financialOverview.description') }}</p>
          </div>
        </div>

        <div class="financial-grid">
          <div class="financial-card revenue-card">
            <div class="card-top">
              <span class="card-label">{{ t('reportsView.cards.netProfit') }}</span>
              <div class="card-indicator positive" />
            </div>
            <h3>{{ currency(financialSummary.netProfit) }}</h3>
            <p>{{ t('reportsView.cards.netProfitDescription') }}</p>
            <!-- <div class="trend-chip" :class="signedTrendClass(financialSummary.grossRevenueTrend)">
              <span>Trend</span>
              <strong>{{ financialSummary.grossRevenueTrend || '—' }}</strong>
            </div> -->
          </div>

          <div class="financial-card collected-card">
            <div class="card-top">
              <span class="card-label">{{ t('reportsView.cards.income') }}</span>
              <div class="card-indicator success" />
            </div>
            <h3>{{ currency(financialSummary.netCollected) }}</h3>
            <p>{{ t('reportsView.cards.incomeDescription') }}</p>
            <!-- <div class="trend-chip" :class="signedTrendClass(financialSummary.netCollectedTrend)">
              <span>Trend</span>
              <strong>{{ financialSummary.netCollectedTrend || '—' }}</strong>
            </div> -->
          </div>

          <div class="financial-card ar-card">
            <div class="card-top">
              <span class="card-label">{{ t('reportsView.cards.receivableAmount') }}</span>
              <div class="card-indicator warning" />
            </div>
            <h3>{{ currency(financialSummary.accountsReceivable) }}</h3>
            <p>{{ t('reportsView.cards.receivableAmountDescription') }}</p>
            <!-- <div class="trend-chip" :class="signedTrendClass(financialSummary.accountsReceivableTrend)">
              <span>Trend</span>
              <strong>{{ financialSummary.accountsReceivableTrend || '—' }}</strong>
            </div> -->
          </div>

          <div class="financial-card operations-card">
            <div class="card-top">
              <span class="card-label">{{ t('reportsView.cards.collectionRate') }}</span>
              <div class="card-indicator info" />
            </div>
            <h3>{{ percent(operationalSummary.collection_rate) }}</h3>
            <p>{{ t('reportsView.cards.collectionRateDescription') }}</p>
            <!-- <div class="trend-chip trend-neutral">
              <span>Volume</span>
              <strong>{{ operationalSummary.appointments_completed }} completed</strong>
            </div> -->
          </div>
        </div>

        <div class="yield-panel">
          <div class="yield-panel-heading">
            <div>
                <h3>{{ t('reportsView.treatmentYield.title') }}</h3>
                <p>{{ t('reportsView.treatmentYield.description') }}</p>
        </div>
          </div>
          <div v-if="financialSummary.treatmentYield.length" class="yield-grid">
            <div
              v-for="item in financialSummary.treatmentYield"
              :key="item.name"
              class="yield-card"
            >
              <div class="yield-name">{{ item.name }}</div>
              <div class="yield-amount">{{ currency(item.amount) }}</div>
              <div class="yield-bar">
                <div class="yield-fill" :style="{ width: `${Math.min(item.percentage, 100)}%` }" />
              </div>
              <div class="yield-meta">
                {{ percent(item.percentage) }} {{ t('reportsView.treatmentYield.ofYield') }}
              </div>
            </div>
          </div>

          <div v-else class="empty-state">
            {{ t('reportsView.treatmentYield.emptyState') }}
          </div>
        </div>

        <div class="operational-grid">
          <div class="mini-stat">
            <span>Total Appointments</span>
            <strong>{{ operationalSummary.appointments_total }}</strong>
          </div>

          <div class="mini-stat">
            <span>Completed</span>
            <strong>{{ operationalSummary.appointments_completed }}</strong>
          </div>

          <div class="mini-stat">
            <span>No Shows</span>
            <strong>{{ operationalSummary.appointments_no_show }}</strong>
          </div>

          <div class="mini-stat">
            <span>Cancelled</span>
            <strong>{{ operationalSummary.appointments_cancelled }}</strong>
          </div>

          <div class="mini-stat">
            <span>New Patients</span>
            <strong>{{ operationalSummary.new_patients }}</strong>
          </div>
        </div>
      </section>

      <section class="section-block">
        <div class="section-heading">
          <div>
              <h2>{{ t('reportsView.providerProductivity.title') }}</h2>
              <p>{{ t('reportsView.providerProductivity.description') }}</p>
`         </div>
        </div>
        <div class="table-shell">
          <ProviderProductivityTable :data="providerProductivity" />
        </div>
      </section>
    </div>
  </div>
</template>

<style scoped>
* {
  box-sizing: border-box;
}

.reports-shell {
  min-height: 100%;
  width: 100%;
  background:
    radial-gradient(circle at top left, rgba(20, 184, 166, 0.08), transparent 26%),
    radial-gradient(circle at top right, rgba(59, 130, 246, 0.08), transparent 28%),
    linear-gradient(to bottom, #f8fafc, #f1f5f9);
}

.reports-workspace {
  width: 100%;
  max-width: 1680px;
  margin: 0 auto;
  padding:
    clamp(0.7rem, 1vw, 1rem)
    clamp(0.8rem, 1.4vw, 1.5rem)
    2rem;
}

.hero-card {
  position: relative;
  overflow: hidden;
  display: grid;
  grid-template-columns: minmax(0, 1.35fr) minmax(300px, 430px);
  gap: 1rem;
  padding: clamp(1.15rem, 2vw, 1.7rem);
  border-radius: 30px;
  background: linear-gradient(135deg, #0f172a 0%, #0f766e 52%, #115e59 100%);
  box-shadow: 0 25px 60px rgba(15, 23, 42, 0.16);
}

.hero-card::before {
  content: '';
  position: absolute;
  inset: 0;
  background: radial-gradient(circle at top right, rgba(255, 255, 255, 0.16), transparent 28%);
  pointer-events: none;
}

.hero-copy {
  position: relative;
  z-index: 1;
}

.hero-eyebrow {
  margin-bottom: 0.65rem;
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.16em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.64);
}

.hero-copy h1 {
  margin: 0;
  font-size: clamp(2rem, 3vw, 3.3rem);
  line-height: 0.95;
  font-weight: 800;
  letter-spacing: -0.05em;
  color: white;
}

.hero-copy p {
  margin-top: 1rem;
  max-width: 60ch;
  font-size: 0.96rem;
  line-height: 1.75;
  color: rgba(255, 255, 255, 0.82);
}

.hero-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 0.65rem;
  margin-top: 1.25rem;
}

.hero-badge {
  padding: 0.7rem 0.95rem;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.08);
  backdrop-filter: blur(10px);
  font-size: 0.82rem;
  font-weight: 700;
  color: white;
}

.hero-badge-ready {
  background: rgba(16, 185, 129, 0.16);
}

.hero-badge-loading {
  background: rgba(250, 204, 21, 0.16);
}

.hero-side {
  position: relative;
  z-index: 1;
  display: grid;
  gap: 0.9rem;
}

.hero-stat-card {
  display: flex;
  flex-direction: column;
  justify-content: center;
  min-height: 122px;
  padding: 1rem 1.1rem;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 24px;
  background: rgba(255, 255, 255, 0.08);
  backdrop-filter: blur(14px);
}

.hero-stat-card span {
  margin-bottom: 0.7rem;
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.62);
}

.hero-stat-card strong {
  font-size: clamp(1.35rem, 2vw, 1.95rem);
  line-height: 1;
  font-weight: 800;
  letter-spacing: -0.04em;
  color: white;
}

.stat-revenue {
  background: linear-gradient(135deg, rgba(14, 165, 233, 0.18), rgba(255, 255, 255, 0.08));
}

.stat-collected {
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.18), rgba(255, 255, 255, 0.08));
}

.stat-ar {
  background: linear-gradient(135deg, rgba(245, 158, 11, 0.18), rgba(255, 255, 255, 0.08));
}

.stat-rate {
  background: linear-gradient(135deg, rgba(139, 92, 246, 0.18), rgba(255, 255, 255, 0.08));
}

.controls-wrapper {
  margin-top: 1rem;
}

.section-block {
  margin-top: 1.25rem;
}

.section-heading {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 1rem;
  margin-bottom: 0.9rem;
}

.section-heading h2 {
  margin: 0;
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0.16em;
  text-transform: uppercase;
  color: #64748b;
}

.section-heading p {
  margin-top: 0.45rem;
  max-width: 65ch;
  font-size: 0.92rem;
  line-height: 1.7;
  color: #64748b;
}

.financial-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 1rem;
}

.financial-card {
  position: relative;
  overflow: hidden;
  padding: 1.2rem;
  border-radius: 26px;
  border: 1px solid rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(12px);
  box-shadow: 0 20px 45px rgba(15, 23, 42, 0.07);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.financial-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 24px 60px rgba(15, 23, 42, 0.12);
}

.revenue-card {
  background: linear-gradient(135deg, rgba(14, 165, 233, 0.12), rgba(59, 130, 246, 0.08));
}

.collected-card {
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.14), rgba(5, 150, 105, 0.08));
}

.ar-card {
  background: linear-gradient(135deg, rgba(245, 158, 11, 0.14), rgba(251, 191, 36, 0.08));
}

.operations-card {
  background: linear-gradient(135deg, rgba(139, 92, 246, 0.14), rgba(99, 102, 241, 0.08));
}

.card-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.card-label {
  font-size: 0.74rem;
  font-weight: 800;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: #64748b;
}

.card-indicator {
  width: 11px;
  height: 11px;
  border-radius: 999px;
}

.card-indicator.positive {
  background: #0ea5e9;
}

.card-indicator.success {
  background: #10b981;
}

.card-indicator.warning {
  background: #f59e0b;
}

.card-indicator.info {
  background: #8b5cf6;
}

.financial-card h3 {
  margin: 0;
  font-size: clamp(1.45rem, 2vw, 2rem);
  line-height: 1;
  font-weight: 800;
  letter-spacing: -0.05em;
  color: #0f172a;
}

.financial-card p {
  margin-top: 0.85rem;
  font-size: 0.9rem;
  line-height: 1.65;
  color: #64748b;
}

.trend-chip {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  margin-top: 1rem;
  padding: 0.8rem 0.9rem;
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.54);
  border: 1px solid rgba(148, 163, 184, 0.16);
}

.trend-chip span {
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: #64748b;
}

.trend-chip strong {
  font-size: 0.86rem;
  font-weight: 700;
  color: #0f172a;
}

.trend-up {
  box-shadow: inset 0 0 0 1px rgba(16, 185, 129, 0.12);
}

.trend-down {
  box-shadow: inset 0 0 0 1px rgba(239, 68, 68, 0.12);
}

.trend-neutral {
  box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.12);
}

.yield-panel {
  margin-top: 1rem;
  padding: 1.1rem;
  border-radius: 26px;
  border: 1px solid rgba(255, 255, 255, 0.84);
  background: rgba(255, 255, 255, 0.72);
  backdrop-filter: blur(12px);
  box-shadow: 0 16px 40px rgba(15, 23, 42, 0.06);
}

.yield-panel-heading h3 {
  margin: 0;
  font-size: 0.9rem;
  font-weight: 800;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: #475569;
}

.yield-panel-heading p {
  margin-top: 0.4rem;
  font-size: 0.92rem;
  line-height: 1.65;
  color: #64748b;
}

.yield-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.85rem;
  margin-top: 1rem;
}

.yield-card {
  padding: 1rem;
  border-radius: 20px;
  background: linear-gradient(135deg, rgba(15, 23, 42, 0.03), rgba(15, 118, 110, 0.05));
  border: 1px solid rgba(148, 163, 184, 0.14);
}

.yield-name {
  font-size: 0.82rem;
  font-weight: 800;
  color: #334155;
  margin-bottom: 0.55rem;
}

.yield-amount {
  font-size: 1.15rem;
  font-weight: 800;
  letter-spacing: -0.03em;
  color: #0f172a;
}

.yield-bar {
  height: 10px;
  margin-top: 0.85rem;
  border-radius: 999px;
  background: rgba(148, 163, 184, 0.18);
  overflow: hidden;
}

.yield-fill {
  height: 100%;
  border-radius: inherit;
  background: linear-gradient(90deg, #0ea5e9, #10b981, #8b5cf6);
}

.yield-meta {
  margin-top: 0.6rem;
  font-size: 0.8rem;
  color: #64748b;
}

.empty-state {
  margin-top: 1rem;
  padding: 1rem 1.05rem;
  border-radius: 18px;
  border: 1px dashed rgba(148, 163, 184, 0.3);
  color: #64748b;
  background: rgba(255, 255, 255, 0.5);
}

.operational-grid {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 0.85rem;
  margin-top: 1rem;
}

.mini-stat {
  padding: 1rem;
  border: 1px solid rgba(255, 255, 255, 0.85);
  border-radius: 22px;
  background: rgba(255, 255, 255, 0.74);
  backdrop-filter: blur(10px);
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.05);
}

.mini-stat span {
  display: block;
  margin-bottom: 0.6rem;
  font-size: 0.74rem;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: #64748b;
}

.mini-stat strong {
  font-size: 1.25rem;
  font-weight: 800;
  letter-spacing: -0.03em;
  color: #0f172a;
}

.table-shell {
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.84);
  border-radius: 28px;
  background: rgba(255, 255, 255, 0.76);
  backdrop-filter: blur(12px);
  box-shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.error-banner {
  margin-top: 1rem;
  padding: 1rem 1.1rem;
  border-radius: 18px;
  border: 1px solid #fecaca;
  background: #fef2f2;
  color: #991b1b;
  font-size: 0.92rem;
  font-weight: 600;
}

@media (max-width: 1400px) {
  .financial-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .operational-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }

  .yield-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 1180px) {
  .hero-card {
    grid-template-columns: 1fr;
  }

  .hero-side {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 900px) {
  .reports-workspace {
    padding: 0.75rem 0.75rem 1.5rem;
  }

  .financial-grid {
    grid-template-columns: 1fr;
  }

  .operational-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .yield-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .reports-workspace {
    padding: 0.65rem;
  }

  .hero-card {
    padding: 1rem;
    border-radius: 24px;
  }

  .hero-copy h1 {
    line-height: 1.04;
  }

  .hero-copy p {
    font-size: 0.92rem;
  }

  .hero-side {
    grid-template-columns: 1fr;
  }

  .hero-stat-card {
    min-height: auto;
  }

  .hero-badges {
    gap: 0.55rem;
  }

  .hero-badge {
    width: 100%;
    justify-content: center;
    text-align: center;
  }

  .operational-grid {
    grid-template-columns: 1fr;
  }

  .section-heading {
    flex-direction: column;
    align-items: flex-start;
  }

  .financial-card,
  .mini-stat,
  .table-shell,
  .yield-panel {
    border-radius: 22px;
  }
}
</style>
