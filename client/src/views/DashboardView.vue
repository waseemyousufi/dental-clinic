<template>
  <div class="dashboard-shell">
    <section class="hero-card">
      <div class="hero-copy">
        <div class="eyebrow">{{ t('dashboardView.hero.eyebrow') }}</div>
        <h1>{{ t('dashboardView.hero.title') }}</h1>
        <p>
          {{ t('dashboardView.hero.description') }}
        </p>
      </div>

      <div class="hero-meta">
        <div class="meta-pill">
          <span class="meta-label">{{ t('dashboardView.hero.meta.branchLabel') }}</span>
          <span class="meta-value">{{ selectedBranchLabel }}</span>
        </div>
        <div class="meta-pill">
          <span class="meta-label">{{ t('dashboardView.hero.meta.periodLabel') }}</span>
          <span class="meta-value">{{ periodLabel }}</span>
        </div>
        <div class="meta-pill">
          <span class="meta-label">{{ t('dashboardView.hero.meta.updatedLabel') }}</span>
          <span class="meta-value">{{ updatedLabel }}</span>
        </div>
        <div class="meta-pill" :class="loading ? 'tone-warn' : 'tone-good'">
          <span class="meta-label">{{ t('dashboardView.hero.meta.statusLabel') }}</span>
          <span class="meta-value">{{ loading ? t('dashboardView.hero.meta.loading') : t('dashboardView.hero.meta.ready') }}</span>
        </div>
      </div>
    </section>

    <section v-if="errorMessage" class="error-banner">
      {{ errorMessage }}
    </section>

    <section class="kpi-grid">
      <article v-for="kpi in kpisToRender" :key="kpi.key" class="kpi-card">
        <div class="kpi-top">
          <div>
            <div class="kpi-label">{{ kpi.label }}</div>
            <div class="kpi-value">{{ kpi.formatted }}</div>
          </div>
          <div class="trend-pill" :class="toneClass(kpi.tone)">
            {{ kpi.trend_label }}
          </div>
        </div>
        <p class="kpi-help">{{ kpi.help }}</p>
      </article>
    </section>

    <section class="chart-grid chart-grid-large">
      <div class="surface-panel chart-card span-2">
        <div class="panel-header">
          <div>
          <h2>{{ t('dashboardView.charts.cashFlow.title') }}</h2>
            <p>{{ t('dashboardView.charts.cashFlow.description') }}</p>
          </div>
          <div class="badge tone-brand">{{ t('dashboardView.charts.cashFlow.badge') }}</div>
        </div>
        <div class="chart-box chart-tall">
          <canvas v-if="hasChartData('cash_flow')" ref="cashChartRef"></canvas>
          <div v-else class="empty-state">{{ t('dashboardView.charts.cashFlow.emptyState') }}</div>
        </div>
      </div>

      <div class="surface-panel chart-card">
        <div class="panel-header">
          <div>
            <h2>{{ t('dashboardView.charts.pricingDiscipline.title') }}</h2>
            <p>{{ t('dashboardView.charts.pricingDiscipline.description') }}</p>
          </div>
          <div class="badge tone-warn">{{ t('dashboardView.charts.pricingDiscipline.badge') }}</div>
        </div>
        <div class="chart-box chart-tall">
          <canvas v-if="hasChartData('pricing_discipline')" ref="pricingChartRef"></canvas>
          <div v-else class="empty-state">{{ t('dashboardView.charts.pricingDiscipline.emptyState') }}</div>
        </div>
      </div>
    </section>

    <section class="chart-grid chart-grid-two">
      <div class="surface-panel chart-card">
        <div class="panel-header">
          <div>
            <h2>{{ t('dashboardView.charts.treatmentMix.title') }}</h2>
            <p>{{ t('dashboardView.charts.treatmentMix.description') }}</p>
          </div>
          <div class="badge tone-good">{{ t('dashboardView.charts.treatmentMix.badge') }}</div>
        </div>
        <div class="chart-box chart-short">
          <canvas v-if="hasChartData('treatment_mix')" ref="mixChartRef"></canvas>
          <div v-else class="empty-state">{{ t('dashboardView.charts.treatmentMix.emptyState') }}</div>
        </div>
      </div>

      <div class="surface-panel chart-card">
        <div class="panel-header">
          <div>
            <h2>{{ t('dashboardView.charts.patientBehavior.title') }}</h2>
            <p>{{ t('dashboardView.charts.patientBehavior.description') }}</p>
          </div>
          <div class="badge tone-brand">{{ t('dashboardView.charts.patientBehavior.badge') }}</div>
        </div>
        <div class="chart-box chart-short">
          <canvas v-if="hasChartData('patient_behavior')" ref="behaviorChartRef"></canvas>
          <div v-else class="empty-state">{{ t('dashboardView.charts.patientBehavior.emptyState') }}</div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Chart, registerables } from 'chart.js'
import dashboardApi from '@api/dashboard'

Chart.register(...registerables)

type Tone = 'good' | 'warn' | 'bad' | 'up' | 'down' | 'neutral'

type ChartKey =
  | 'cash_flow'
  | 'treatment_mix'
  | 'patient_behavior'
  | 'pricing_discipline'

interface BranchChip {
  id: number
  name: string
}

interface KpiItem {
  key: string
  label: string
  value: number | string
  formatted: string
  trend?: number | null
  trend_label: string
  tone: Tone
  help: string
}

interface ChartPayload {
  labels: string[]
  datasets: Array<{ label: string; data: number[] }>
}

interface DashboardPayload {
  meta?: {
    generated_at?: string
    branch_id?: number | null
    branch_name?: string
    period_days?: number
    period_start?: string
    period_end?: string
    includes_future_days?: number
  }
  filters?: { branches?: Array<{ id: number; branch_name: string | null }> }
  kpis?: KpiItem[]
  charts?: Partial<Record<ChartKey, ChartPayload>>
}

const loading = ref(false)
const errorMessage = ref<string | null>(null)
const dashboard = ref<DashboardPayload | null>(null)

const cashChartRef = ref<HTMLCanvasElement | null>(null)
const mixChartRef = ref<HTMLCanvasElement | null>(null)
const behaviorChartRef = ref<HTMLCanvasElement | null>(null)
const pricingChartRef = ref<HTMLCanvasElement | null>(null)

const { t } = useI18n()

let cashChart: Chart | null = null
let mixChart: Chart | null = null
let behaviorChart: Chart | null = null
let pricingChart: Chart | null = null

const palette = ['#2563eb', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444', '#06b6d4', '#84cc16']

const formatMoney = (value: number | string | null | undefined) =>
  new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(Number(value ?? 0))

const formatDate = (value: string | null | undefined) => {
  if (!value) return '-'
  const date = new Date(value)
  return Number.isNaN(date.getTime())
    ? value
    : date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })
}

const formatDateTime = (value: string | null | undefined) => {
  if (!value) return '-'
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? value : date.toLocaleString()
}

const dashboardMeta = computed(() => dashboard.value?.meta ?? {})
const branchId = computed(() => dashboardMeta.value.branch_id ?? null)

const selectedBranchLabel = computed(() => {
  const branchName = dashboardMeta.value.branch_name?.trim()
  if (branchName) return branchName
  if (branchId.value == null) return t('dashboardView.hero.meta.allBranches')
  const branch = branchChips.value.find(b => b.id === branchId.value)
  return branch?.name ?? `Branch #${branchId.value}`
})

const periodLabel = computed(() => {
  const meta = dashboardMeta.value
  if (meta.period_start && meta.period_end) {
    return `${formatDate(meta.period_start)} → ${formatDate(meta.period_end)}`
  }
  return `${meta.period_days ?? 30} days`
})

const updatedLabel = computed(() => formatDateTime(dashboardMeta.value.generated_at ?? null))

const branchChips = computed<BranchChip[]>(() => {
  const branches = dashboard.value?.filters?.branches ?? []
  return branches.map(branch => ({
    id: branch.id,
    name: branch.branch_name?.trim() || `Branch #${branch.id}`,
  }))
})

const kpisToRender = computed(() =>
  (dashboard.value?.kpis ?? []).map(kpi => ({
    ...kpi,
    label: t(`dashboardView.kpis.${kpi.key}Label`) || kpi.label,
    help:
      kpi.key === 'pricing_discipline'
        ? kpi.help
        : t(`dashboardView.kpis.${kpi.key}Help`) || kpi.help,
    trend_label: kpiTrendLabel(kpi.trend_label),
  })),
)

function kpiTrendLabel(value: string): string {
  const normalized = String(value ?? '').toLowerCase()
  const map: Record<string, string> = {
    flat: t('dashboardView.kpis.cashCollectedTrendFlat'),
    clean: t('dashboardView.kpis.outstandingCreditTrendClean'),
    healthy: t('dashboardView.kpis.collectionRateTrendHealthy'),
    'per appointment': t('dashboardView.kpis.avgProductionPerVisitTrend'),
    good: t('dashboardView.kpis.noShowRateTrendGood'),
    'local strength': t('dashboardView.kpis.sameDayCollectionTrendLocalStrength'),
    '0/0 matched': t('dashboardView.kpis.pricingDisciplineTrend'),
    'return behavior': t('dashboardView.kpis.patientRetentionTrend'),
    'repeat behavior': t('dashboardView.kpis.repeatPatientRateTrend'),
    'plans accepted': t('dashboardView.kpis.planAcceptanceTrend'),
    'plans completed': t('dashboardView.kpis.planCompletionTrend'),
  }
  return map[normalized] ?? value
}

function toneClass(tone: Tone): string {
  return tone
}

type RawRecord = Record<string, unknown>

function isRecord(value: unknown): value is RawRecord {
  return typeof value === 'object' && value !== null
}

function normalizeResponse(response: unknown): unknown {
  return isRecord(response) && 'data' in response ? response.data : response
}

function normalizeDashboard(raw: unknown): DashboardPayload {
  const payload = isRecord(raw) ? raw : {}
  const meta = isRecord(payload.meta) ? payload.meta : {}
  const filters = isRecord(payload.filters) ? payload.filters : {}
  const branches = Array.isArray(filters.branches) ? filters.branches : []
  const kpis = Array.isArray(payload.kpis) ? payload.kpis : []
  const charts = isRecord(payload.charts) ? payload.charts : {}

  return {
    meta,
    filters: {
      branches: branches.filter(isRecord).map(branch => ({
        id: Number(branch.id),
        branch_name: typeof branch.branch_name === 'string' ? branch.branch_name : null,
      })),
    },
    kpis: kpis.filter(isRecord) as unknown as KpiItem[],
    charts: charts as Partial<Record<ChartKey, ChartPayload>>,
  }
}

function hasChartData(key: ChartKey): boolean {
  const chart = dashboard.value?.charts?.[key]
  if (!chart) return false
  return Array.isArray(chart.labels) && chart.labels.length > 0 && Array.isArray(chart.datasets) && chart.datasets.some(dataset => Array.isArray(dataset.data) && dataset.data.length > 0)
}

function chartOptions() {
  return {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'bottom' as const,
        labels: {
          usePointStyle: true,
          boxWidth: 10,
          padding: 18,
          font: { size: 12 },
        },
      },
      tooltip: {
        backgroundColor: '#0f172a',
        titleColor: '#fff',
        bodyColor: '#e2e8f0',
        padding: 12,
        displayColors: true,
      },
    },
  }
}

function destroyCharts() {
  cashChart?.destroy()
  mixChart?.destroy()
  behaviorChart?.destroy()
  pricingChart?.destroy()

  cashChart = null
  mixChart = null
  behaviorChart = null
  pricingChart = null
}

function buildDatasetStyle(index: number) {
  const color = palette[index % palette.length]
  return {
    borderColor: color,
    backgroundColor: `${color}22`,
    pointBackgroundColor: color,
    pointBorderColor: '#ffffff',
    hoverBackgroundColor: color,
  }
}

function renderCharts() {
  const charts = dashboard.value?.charts
  if (!charts) return

  destroyCharts()

  if (cashChartRef.value && charts.cash_flow?.labels?.length) {
    cashChart = new Chart(cashChartRef.value, {
      type: 'line',
      data: {
        labels: charts.cash_flow.labels,
        datasets: (charts.cash_flow.datasets ?? []).map((dataset, index) => ({
          label: dataset.label === 'Cash in' ? t('dashboardView.charts.cashFlow.cashInLabel') : dataset.label === 'Appointment charges' ? t('dashboardView.charts.cashFlow.appointmentChargesLabel') : dataset.label,
          data: dataset.data ?? [],
          tension: 0.35,
          borderWidth: 3,
          pointRadius: 2,
          fill: index === 0,
          ...buildDatasetStyle(index),
        })),
      },
      options: {
        ...chartOptions(),
        scales: {
          y: {
            beginAtZero: true,
            grid: { color: '#e2e8f0' },
            ticks: { callback: (value: string | number) => formatMoney(value) },
          },
          x: { grid: { display: false } },
        },
      },
    })
  }

  if (mixChartRef.value && charts.treatment_mix?.labels?.length) {
    mixChart = new Chart(mixChartRef.value, {
      type: 'doughnut',
      data: {
        labels: charts.treatment_mix.labels,
        datasets: [
          {
            label: t('dashboardView.charts.treatmentMix.treatmentsLabel') || (charts.treatment_mix.datasets?.[0]?.label ?? 'Treatments'),
            data: charts.treatment_mix.datasets?.[0]?.data ?? [],
            borderWidth: 0,
            backgroundColor: charts.treatment_mix.labels.map((_, index) => palette[index % palette.length]),
          },
        ],
      },
      options: {
        ...chartOptions(),
        cutout: '64%',
      },
    })
  }

  if (behaviorChartRef.value && charts.patient_behavior?.labels?.length) {
    behaviorChart = new Chart(behaviorChartRef.value, {
      type: 'line',
      data: {
        labels: charts.patient_behavior.labels,
        datasets: (charts.patient_behavior.datasets ?? []).map((dataset, index) => ({
          label: dataset.label === 'Appointments' ? t('dashboardView.charts.patientBehavior.appointmentsLabel') : dataset.label === 'Treatments Accepted' ? t('dashboardView.charts.patientBehavior.treatmentsAcceptedLabel') : dataset.label === 'New patients' ? t('dashboardView.charts.patientBehavior.newPatientsLabel') : dataset.label,
          data: dataset.data ?? [],
          tension: 0.35,
          borderWidth: 2,
          pointRadius: 1.5,
          fill: index === 0,
          ...buildDatasetStyle(index),
        })),
      },
      options: {
        ...chartOptions(),
        scales: {
          y: {
            beginAtZero: true,
            grid: { color: '#e2e8f0' },
          },
          x: { grid: { display: false } },
        },
      },
    })
  }

  if (pricingChartRef.value && charts.pricing_discipline?.labels?.length) {
    pricingChart = new Chart(pricingChartRef.value, {
      type: 'pie',
      data: {
        labels: charts.pricing_discipline.labels,
        datasets: [
          {
            label: t('dashboardView.charts.pricingDiscipline.pricingAuditLabel') || (charts.pricing_discipline.datasets?.[0]?.label ?? 'Pricing audit'),
            data: charts.pricing_discipline.datasets?.[0]?.data ?? [],
            borderWidth: 0,
            backgroundColor: charts.pricing_discipline.labels.map((_, index) => palette[index % palette.length]),
          },
        ],
      },
      options: chartOptions(),
    })
  }

}

async function loadDashboard() {
  loading.value = true
  errorMessage.value = null

  try {
    const response = await dashboardApi.getBranchDashboard(undefined, 30)

    dashboard.value = normalizeDashboard(normalizeResponse(response))

    await nextTick()
    renderCharts()
  } catch (error) {
    console.error(error)
    errorMessage.value = 'Failed to load dashboard data from the backend.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  void loadDashboard()
})

onBeforeUnmount(() => {
  destroyCharts()
})
</script>

<style scoped>
.dashboard-shell {
  --shell-gutter: clamp(14px, 2vw, 24px);
  --shell-max-width: 1560px;
  width: 100%;
  box-sizing: border-box;
  max-width: calc(var(--shell-max-width) + (var(--shell-gutter) * 2));
  margin: 0 auto;
  padding: 18px var(--shell-gutter) 28px;
  display: grid;
  gap: 18px;
}

.surface-panel,
.hero-card {
  background: rgba(255, 255, 255, 0.94);
  border: 1px solid rgba(226, 232, 240, 0.92);
  border-radius: 24px;
  box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
  backdrop-filter: blur(10px);
}

.hero-card {
  padding: 24px;
  display: grid;
  gap: 18px;
}

.eyebrow {
  color: #2563eb;
  font-size: 12px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.12em;
}

.hero-copy h1 {
  margin: 8px 0 0;
  font-size: clamp(28px, 2.4vw, 44px);
  line-height: 1.05;
  letter-spacing: -0.05em;
  color: #0f172a;
}

.hero-copy p {
  margin: 12px 0 0;
  max-width: 78ch;
  color: #64748b;
  line-height: 1.7;
  font-size: 14px;
}

.hero-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.meta-pill {
  min-width: 160px;
  flex: 1 1 190px;
  border-radius: 18px;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  padding: 12px 14px;
  display: grid;
  gap: 4px;
}

.meta-label {
  font-size: 12px;
  color: #64748b;
  font-weight: 700;
}

.meta-value {
  font-size: 14px;
  color: #0f172a;
  font-weight: 700;
  text-transform: none;
}

.chart-card,
.footer-card {
  padding: 18px;
}

.panel-header {
  display: flex;
  justify-content: space-between;
  gap: 14px;
  align-items: start;
  flex-wrap: wrap;
  margin-bottom: 14px;
}

.panel-header h2 {
  margin: 0;
  font-size: 18px;
  letter-spacing: -0.03em;
  color: #0f172a;
}

.panel-header p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 13px;
  line-height: 1.55;
}

.badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 8px 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 800;
}

.tone-brand {
  background: rgba(37, 99, 235, 0.12);
  color: #1d4ed8;
}

.tone-good {
  background: rgba(34, 197, 94, 0.12);
  color: #166534;
}

.tone-warn {
  background: rgba(245, 158, 11, 0.14);
  color: #92400e;
}

.tone-bad {
  background: rgba(239, 68, 68, 0.12);
  color: #991b1b;
}

.branch-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.branch-chip {
  min-width: 160px;
  flex: 1 1 180px;
  border-radius: 18px;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  padding: 12px 14px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
}

.branch-chip.active {
  background: rgba(37, 99, 235, 0.08);
  border-color: rgba(37, 99, 235, 0.2);
}

.branch-name {
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
  text-transform: capitalize;
}

.branch-id {
  font-size: 12px;
  color: #64748b;
  font-weight: 700;
}

.error-banner {
  background: rgba(239, 68, 68, 0.08);
  border: 1px solid rgba(239, 68, 68, 0.18);
  color: #991b1b;
  padding: 12px 14px;
  border-radius: 18px;
}

.kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
  gap: 14px;
}

.kpi-card {
  background: rgba(255, 255, 255, 0.95);
  border: 1px solid rgba(226, 232, 240, 0.92);
  border-radius: 24px;
  box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
  padding: 18px;
  display: grid;
  gap: 12px;
}

.kpi-top {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  align-items: start;
}

.kpi-label {
  font-size: 13px;
  color: #64748b;
  font-weight: 700;
}

.kpi-value {
  margin-top: 7px;
  font-size: clamp(26px, 2vw, 34px);
  font-weight: 900;
  letter-spacing: -0.05em;
  color: #0f172a;
}

.trend-pill {
  border-radius: 999px;
  padding: 7px 10px;
  font-size: 12px;
  font-weight: 800;
  white-space: nowrap;
}

.kpi-help {
  margin: 0;
  color: #64748b;
  font-size: 12px;
  line-height: 1.6;
}

.chart-grid {
  display: grid;
  gap: 14px;
}

.chart-grid-large {
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.chart-grid-three {
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.chart-grid-two {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.span-2 {
  grid-column: span 2;
}

.chart-card {
  min-width: 0;
}

.chart-box {
  position: relative;
  width: 100%;
}

.chart-tall {
  height: 360px;
}

.chart-short {
  height: 270px;
}

.empty-state {
  position: absolute;
  inset: 0;
  display: grid;
  place-items: center;
  text-align: center;
  padding: 16px;
  border-radius: 18px;
  border: 1px dashed #e2e8f0;
  background: #f8fafc;
  color: #64748b;
  font-size: 13px;
}

.footer-grid {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 14px;
  flex-wrap: wrap;
}

.footer-card h2 {
  margin: 0;
  font-size: 18px;
  letter-spacing: -0.03em;
}

.footer-card p {
  margin: 6px 0 0;
  color: #64748b;
  font-size: 13px;
  line-height: 1.6;
  max-width: 70ch;
}

.summary-pills {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.summary-pill {
  padding: 9px 12px;
  border-radius: 999px;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  color: #334155;
  font-size: 12px;
  font-weight: 800;
}

@media (max-width: 1400px) {
  .dashboard-shell {
    --shell-gutter: 18px;
  }

  .chart-grid-large,
  .chart-grid-three,
  .chart-grid-two {
    grid-template-columns: 1fr;
  }

  .span-2 {
    grid-column: auto;
  }
}

@media (max-width: 760px) {
  .dashboard-shell {
    --shell-gutter: 14px;
    padding: 14px;
    gap: 14px;
  }

  .hero-card,
  .surface-panel {
    border-radius: 20px;
  }

  .hero-card,
  .chart-card,
  .footer-card {
    padding: 16px;
  }

  .chart-tall,
  .chart-short {
    height: 240px;
  }
}
</style>
