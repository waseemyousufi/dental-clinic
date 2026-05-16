<template>
  <div class="dashboard-shell">
    <section class="hero-card">
      <div class="hero-copy">
        <div class="eyebrow">Dental Clinic</div>
        <h1>Dashboard.</h1>
        <p>
          View overall Clinic performance
        </p>
      </div>

      <div class="hero-meta">
        <div class="meta-pill">
          <span class="meta-label">Branch</span>
          <span class="meta-value">{{ selectedBranchLabel }}</span>
        </div>
        <div class="meta-pill">
          <span class="meta-label">Period</span>
          <span class="meta-value">{{ periodLabel }}</span>
        </div>
        <div class="meta-pill">
          <span class="meta-label">Updated</span>
          <span class="meta-value">{{ updatedLabel }}</span>
        </div>
        <div class="meta-pill" :class="loading ? 'tone-warn' : 'tone-good'">
          <span class="meta-label">Status</span>
          <span class="meta-value">{{ loading ? 'Loading…' : 'Ready' }}</span>
        </div>
      </div>
    </section>

<!--
    <section class="surface-panel scope-panel">
      <div class="panel-header">
        <div>
          <h2>Data scope</h2>
          <p>Branch filter metadata returned by the backend.</p>
        </div>
        <div class="badge tone-brand">{{ branchCountLabel }}</div>
      </div>

      <div class="branch-grid">
        <div
          v-for="branch in branchChips"
          :key="branch.id"
          class="branch-chip"
          :class="branch.id === selectedBranchId ? 'active' : ''"
        >
          <span class="branch-name">{{ branch.name }}</span>
          <span class="branch-id">#{{ branch.id }}</span>
        </div>
      </div>
    </section> -->

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
            <h2>Cash flow</h2>
            <p>Collected money versus patient dues across the selected period.</p>
          </div>
          <div class="badge tone-brand">Daily trend</div>
        </div>
        <div class="chart-box chart-tall">
          <canvas v-if="hasChartData('cash_flow')" ref="cashChartRef"></canvas>
          <div v-else class="empty-state">No cash flow data returned.</div>
        </div>
      </div>

      <div class="surface-panel chart-card">
        <div class="panel-header">
          <div>
            <h2>Credit aging</h2>
            <p>Outstanding balances grouped by age bucket.</p>
          </div>
          <div class="badge tone-warn">A/R monitoring</div>
        </div>
        <div class="chart-box chart-tall">
          <canvas v-if="hasChartData('credit_aging')" ref="agingChartRef"></canvas>
          <div v-else class="empty-state">No credit aging data returned.</div>
        </div>
      </div>
    </section>

    <section class="chart-grid chart-grid-three">
      <div class="surface-panel chart-card">
        <div class="panel-header">
          <div>
            <h2>Treatment mix</h2>
            <p>Procedure categories by count.</p>
          </div>
          <div class="badge tone-good">Clinical mix</div>
        </div>
        <div class="chart-box chart-short">
          <canvas v-if="hasChartData('treatment_mix')" ref="mixChartRef"></canvas>
          <div v-else class="empty-state">No treatment mix data returned.</div>
        </div>
      </div>

      <div class="surface-panel chart-card">
        <div class="panel-header">
          <div>
            <h2>Patient behavior</h2>
            <p>Appointments, treatments, and new patients over time.</p>
          </div>
          <div class="badge tone-brand">Retention</div>
        </div>
        <div class="chart-box chart-short">
          <canvas v-if="hasChartData('patient_behavior')" ref="behaviorChartRef"></canvas>
          <div v-else class="empty-state">No patient behavior data returned.</div>
        </div>
      </div>

      <div class="surface-panel chart-card">
        <div class="panel-header">
          <div>
            <h2>Pricing discipline</h2>
            <p>How often prices stay inside the procedure range.</p>
          </div>
          <div class="badge tone-warn">Range control</div>
        </div>
        <div class="chart-box chart-short">
          <canvas v-if="hasChartData('pricing_discipline')" ref="pricingChartRef"></canvas>
          <div v-else class="empty-state">No pricing audit data returned.</div>
        </div>
      </div>
    </section>

    <!-- <section class="chart-grid chart-grid-three">
      <div class="surface-panel chart-card">
        <div class="panel-header">
          <div>
            <h2>Referral source</h2>
            <p>Where new patients are coming from.</p>
          </div>
          <div class="badge tone-brand">Source mix</div>
        </div>
        <div class="chart-box chart-short">
          <canvas v-if="hasChartData('referral_source')" ref="referralChartRef"></canvas>
          <div v-else class="empty-state">No referral source data returned.</div>
        </div>
      </div>

      <div class="surface-panel chart-card">
        <div class="panel-header">
          <div>
            <h2>Visit type</h2>
            <p>How visits are distributed.</p>
          </div>
          <div class="badge tone-brand">Visit mix</div>
        </div>
        <div class="chart-box chart-short">
          <canvas v-if="hasChartData('visit_type')" ref="visitChartRef"></canvas>
          <div v-else class="empty-state">No visit type data returned.</div>
        </div>
      </div>

      <div class="surface-panel insights-card">
        <div class="panel-header">
          <div>
            <h2>Operational snapshot</h2>
            <p>Fast summary for branch leadership.</p>
          </div>
          <div class="badge tone-brand">Summary</div>
        </div>
        <div class="mini-grid">
          <div v-for="item in operationalStats" :key="item.label" class="mini-card">
            <span class="mini-label">{{ item.label }}</span>
            <span class="mini-value">{{ item.value }}</span>
            <span class="mini-help">{{ item.help }}</span>
          </div>
        </div>
      </div>
    </section> -->

    <!-- <section class="chart-grid chart-grid-two">
      <div class="surface-panel alerts-card">
        <div class="panel-header">
          <div>
            <h2>Action alerts</h2>
            <p>Warnings surfaced from the backend payload.</p>
          </div>
          <div class="badge tone-bad">Attention</div>
        </div>

        <div class="alert-list">
          <article v-for="alert in alertsToRender" :key="alert.title" class="alert-item" :class="alert.tone">
            <div class="alert-title-row">
              <strong>{{ alert.title }}</strong>
              <span class="alert-tone">{{ alert.tone }}</span>
            </div>
            <p>{{ alert.message }}</p>
            <div class="alert-action">Suggested action: {{ alert.action }}</div>
          </article>
        </div>
      </div>

      <div class="surface-panel recent-card">
        <div class="panel-header">
          <div>
            <h2>Recent activity</h2>
            <p>Latest treatments and transactions from the backend.</p>
          </div>
          <div class="badge tone-brand">Latest records</div>
        </div>

        <div class="table-switch">
          <button :class="['switch-btn', currentTable === 'treatments' ? 'active' : '']" @click="currentTable = 'treatments'">Treatments</button>
          <button :class="['switch-btn', currentTable === 'transactions' ? 'active' : '']" @click="currentTable = 'transactions'">Transactions</button>
        </div>

        <div class="table-wrap">
          <table v-if="currentTable === 'treatments'">
            <thead>
              <tr>
                <th>Patient</th>
                <th>Date</th>
                <th>Treatment</th>
                <th>Status</th>
                <th>Balance</th>
                <th>Price fit</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in recentTreatments" :key="row.id">
                <td>
                  <strong>{{ row.patient_name }}</strong>
                  <div class="muted-small">{{ row.branch_name }}</div>
                </td>
                <td>{{ formatDate(row.date) }}</td>
                <td>
                  {{ row.treatment_type }}
                  <div class="muted-small">{{ row.diagnosis }}</div>
                </td>
                <td><span class="row-tag" :class="statusTone(row.status)">{{ row.status }}</span></td>
                <td>{{ formatMoney(row.balance) }}</td>
                <td><span class="row-tag" :class="rangeTone(row.range_fit)">{{ row.range_fit }}</span></td>
              </tr>
              <tr v-if="!recentTreatments.length">
                <td colspan="6" class="empty-table">No recent treatments returned.</td>
              </tr>
            </tbody>
          </table>

          <table v-else>
            <thead>
              <tr>
                <th>Branch</th>
                <th>Date</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Description</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in recentTransactions" :key="row.id">
                <td>{{ row.branch_name }}</td>
                <td>{{ formatDate(row.date) }}</td>
                <td><span class="row-tag" :class="row.transaction_type === 'in' ? 'good' : 'warn'">{{ row.transaction_type }}</span></td>
                <td>{{ formatMoney(row.amount) }}</td>
                <td>{{ row.description }}</td>
              </tr>
              <tr v-if="!recentTransactions.length">
                <td colspan="5" class="empty-table">No recent transactions returned.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section> -->
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { Chart, registerables } from 'chart.js'
import dashboardApi from '@api/dashboard'

Chart.register(...registerables)

type Tone = 'good' | 'warn' | 'bad' | 'up' | 'down' | 'neutral'
type TableMode = 'treatments' | 'transactions'

type ChartKey =
  | 'cash_flow'
  | 'credit_aging'
  | 'treatment_mix'
  | 'patient_behavior'
  | 'pricing_discipline'
  | 'referral_source'
  | 'visit_type'

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

interface AlertItem {
  title: string
  message: string
  action: string
  tone: 'good' | 'warn' | 'bad'
}

interface TreatmentRow {
  id: number
  patient_name: string
  branch_name: string
  treatment_type: string
  diagnosis: string
  date: string
  status: string
  amount: number
  range_fit: string
  balance: number
}

interface TransactionRow {
  id: number
  branch_name: string
  transaction_type: 'in' | 'debit' | string
  amount: number
  date: string
  description: string
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
  }
  filters?: { branches?: Array<{ id: number; branch_name: string | null }> }
  kpis?: KpiItem[]
  charts?: Partial<Record<ChartKey, ChartPayload>>
  alerts?: Array<{ tone?: 'good' | 'warn' | 'bad'; title: string; message: string; meta?: { action?: string } }>
  recent?: { treatments?: TreatmentRow[]; transactions?: TransactionRow[] }
}

const loading = ref(false)
const errorMessage = ref<string | null>(null)
const dashboard = ref<DashboardPayload | null>(null)
const currentTable = ref<TableMode>('treatments')

const cashChartRef = ref<HTMLCanvasElement | null>(null)
const agingChartRef = ref<HTMLCanvasElement | null>(null)
const mixChartRef = ref<HTMLCanvasElement | null>(null)
const behaviorChartRef = ref<HTMLCanvasElement | null>(null)
const pricingChartRef = ref<HTMLCanvasElement | null>(null)
const referralChartRef = ref<HTMLCanvasElement | null>(null)
const visitChartRef = ref<HTMLCanvasElement | null>(null)

let cashChart: Chart | null = null
let agingChart: Chart | null = null
let mixChart: Chart | null = null
let behaviorChart: Chart | null = null
let pricingChart: Chart | null = null
let referralChart: Chart | null = null
let visitChart: Chart | null = null

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
  if (branchId.value == null) return 'All branches'
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

const branchCountLabel = computed(() => {
  const total = branchChips.value.length
  return total === 1 ? '1 branch' : `${total} branches`
})

const kpisToRender = computed(() => dashboard.value?.kpis ?? [])
const recentTreatments = computed<TreatmentRow[]>(() => dashboard.value?.recent?.treatments ?? [])
const recentTransactions = computed<TransactionRow[]>(() => dashboard.value?.recent?.transactions ?? [])

const alertsToRender = computed<AlertItem[]>(() =>
  (dashboard.value?.alerts ?? []).map(alert => ({
    title: alert.title,
    message: alert.message,
    action: alert.meta?.action ?? 'Review dashboard',
    tone: alert.tone ?? 'warn',
  })),
)

const operationalStats = computed(() => {
  const items = dashboard.value?.kpis ?? []
  const find = (key: string) => items.find(k => k.key === key)

  return [
    { label: 'Cash collected', value: find('cash_collected')?.formatted ?? '0', help: 'Actual money received in the selected period.' },
    { label: 'Outstanding credit', value: find('outstanding_ar')?.formatted ?? '0', help: 'Balances still owed by patients.' },
    { label: 'Collection rate', value: find('collection_rate')?.formatted ?? '0%', help: 'Cash collected versus total patient dues.' },
    { label: 'No-show rate', value: find('no_show_rate')?.formatted ?? '0%', help: 'Missed appointments within the period.' },
    { label: 'New patients', value: find('new_patients')?.formatted ?? '0', help: 'Registered patients in the selected period.' },
    { label: 'Pricing discipline', value: find('pricing_discipline')?.formatted ?? '0%', help: 'Share of treatments priced inside range.' },
    { label: 'Same-day collection', value: find('same_day_collection')?.formatted ?? '0%', help: 'Treatments collected on the same day.' },
    // { label: 'Case acceptance', value: find('plan_acceptance')?.formatted ?? '0%', help: 'Accepted treatment plans versus proposals.' },
  ]
})

function toneClass(tone: Tone): string {
  return tone
}

function statusTone(status: string): string {
  const s = String(status || '').toLowerCase()
  if (s.includes('completed') || s.includes('accepted')) return 'good'
  if (s.includes('cancel')) return 'bad'
  if (s.includes('progress') || s.includes('partial')) return 'warn'
  return 'brand'
}

function rangeTone(rangeFit: string): string {
  const s = String(rangeFit || '').toLowerCase()
  if (s.includes('in range')) return 'good'
  if (s.includes('below') || s.includes('above')) return 'warn'
  return 'brand'
}

function normalizeResponse(response: any): any {
  return response?.data ?? response
}

function normalizeDashboard(raw: any): DashboardPayload {
  return {
    meta: raw?.meta ?? {},
    filters: {
      branches: (raw?.filters?.branches ?? []).map((branch: any) => ({
        id: branch.id,
        branch_name: branch.branch_name ?? null,
      })),
    },
    kpis: raw?.kpis ?? [],
    charts: raw?.charts ?? {},
    alerts: raw?.alerts ?? [],
    recent: raw?.recent ?? { treatments: [], transactions: [] },
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
  agingChart?.destroy()
  mixChart?.destroy()
  behaviorChart?.destroy()
  pricingChart?.destroy()
  referralChart?.destroy()
  visitChart?.destroy()

  cashChart = null
  agingChart = null
  mixChart = null
  behaviorChart = null
  pricingChart = null
  referralChart = null
  visitChart = null
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
          label: dataset.label,
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

  if (agingChartRef.value && charts.credit_aging?.labels?.length) {
    agingChart = new Chart(agingChartRef.value, {
      type: 'bar',
      data: {
        labels: charts.credit_aging.labels,
        datasets: [
          {
            label: charts.credit_aging.datasets?.[0]?.label ?? 'Outstanding balance',
            data: charts.credit_aging.datasets?.[0]?.data ?? [],
            borderWidth: 1,
            ...buildDatasetStyle(1),
          },
        ],
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
            label: charts.treatment_mix.datasets?.[0]?.label ?? 'Treatments',
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
          label: dataset.label,
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
            label: charts.pricing_discipline.datasets?.[0]?.label ?? 'Pricing audit',
            data: charts.pricing_discipline.datasets?.[0]?.data ?? [],
            borderWidth: 0,
            backgroundColor: charts.pricing_discipline.labels.map((_, index) => palette[index % palette.length]),
          },
        ],
      },
      options: chartOptions(),
    })
  }

  if (referralChartRef.value && charts.referral_source?.labels?.length) {
    referralChart = new Chart(referralChartRef.value, {
      type: 'doughnut',
      data: {
        labels: charts.referral_source.labels,
        datasets: [
          {
            label: charts.referral_source.datasets?.[0]?.label ?? 'Patients',
            data: charts.referral_source.datasets?.[0]?.data ?? [],
            borderWidth: 0,
            backgroundColor: charts.referral_source.labels.map((_, index) => palette[index % palette.length]),
          },
        ],
      },
      options: {
        ...chartOptions(),
        cutout: '64%',
      },
    })
  }

  if (visitChartRef.value && charts.visit_type?.labels?.length) {
    visitChart = new Chart(visitChartRef.value, {
      type: 'doughnut',
      data: {
        labels: charts.visit_type.labels,
        datasets: [
          {
            label: charts.visit_type.datasets?.[0]?.label ?? 'Visits',
            data: charts.visit_type.datasets?.[0]?.data ?? [],
            borderWidth: 0,
            backgroundColor: charts.visit_type.labels.map((_, index) => palette[index % palette.length]),
          },
        ],
      },
      options: {
        ...chartOptions(),
        cutout: '64%',
      },
    })
  }
}

async function loadDashboard() {
  loading.value = true
  errorMessage.value = null

  try {
    const api = dashboardApi as any
    const response =
      (await api.getBranchDashboard?.(undefined, 30)) ??
      (await api.index?.()) ??
      (await api.get?.('/dashboard'))

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
  --shell-offset: clamp(260px, 30vw, 380px);
  width: 100%;
  box-sizing: border-box;
  padding: 18px 22px 28px var(--shell-offset);
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

.scope-panel,
.chart-card,
.insights-card,
.alerts-card,
.recent-card,
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

.chart-card,
.insights-card,
.alerts-card,
.recent-card {
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

.mini-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
  gap: 12px;
}

.mini-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 18px;
  padding: 14px;
  display: grid;
  gap: 5px;
}

.mini-label {
  font-size: 12px;
  color: #64748b;
  font-weight: 700;
}

.mini-value {
  font-size: 22px;
  font-weight: 900;
  color: #0f172a;
}

.mini-help {
  font-size: 12px;
  color: #64748b;
  line-height: 1.55;
}

.alert-list {
  display: grid;
  gap: 12px;
}

.alert-item {
  border-radius: 18px;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  padding: 15px 16px;
  display: grid;
  gap: 8px;
}

.alert-item.good {
  border-left: 5px solid #22c55e;
}

.alert-item.warn {
  border-left: 5px solid #f59e0b;
}

.alert-item.bad {
  border-left: 5px solid #ef4444;
}

.alert-title-row {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  align-items: center;
}

.alert-item strong {
  font-size: 14px;
  color: #0f172a;
}

.alert-tone {
  font-size: 11px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #64748b;
}

.alert-item p {
  margin: 0;
  color: #64748b;
  font-size: 13px;
  line-height: 1.6;
}

.alert-action {
  font-size: 12px;
  font-weight: 700;
  color: #334155;
}

.table-switch {
  display: flex;
  gap: 10px;
  margin-bottom: 14px;
  flex-wrap: wrap;
}

.switch-btn {
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  color: #0f172a;
  padding: 10px 14px;
  border-radius: 12px;
  font: inherit;
  font-weight: 800;
}

.switch-btn.active {
  background: rgba(37, 99, 235, 0.12);
  color: #1d4ed8;
  border-color: rgba(37, 99, 235, 0.24);
}

.table-wrap {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
  min-width: 900px;
}

th,
td {
  text-align: left;
  padding: 13px 10px;
  border-bottom: 1px solid #e2e8f0;
  font-size: 13px;
  vertical-align: top;
}

th {
  color: #64748b;
  font-weight: 800;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

tr:last-child td {
  border-bottom: 0;
}

.empty-table {
  color: #64748b;
  text-align: center;
  padding: 24px 10px;
}

.row-tag {
  display: inline-flex;
  align-items: center;
  padding: 6px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 800;
  white-space: nowrap;
  background: #eef2ff;
  color: #3730a3;
}

.row-tag.good {
  background: rgba(34, 197, 94, 0.12);
  color: #166534;
}

.row-tag.warn {
  background: rgba(245, 158, 11, 0.14);
  color: #92400e;
}

.row-tag.bad {
  background: rgba(239, 68, 68, 0.12);
  color: #991b1b;
}

.row-tag.brand {
  background: rgba(37, 99, 235, 0.12);
  color: #1d4ed8;
}

.muted-small {
  margin-top: 4px;
  color: #64748b;
  font-size: 12px;
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
    --shell-offset: 18px;
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
    --shell-offset: 14px;
    padding: 14px;
    gap: 14px;
  }

  .hero-card,
  .surface-panel {
    border-radius: 20px;
  }

  .hero-card,
  .scope-panel,
  .chart-card,
  .insights-card,
  .alerts-card,
  .recent-card,
  .footer-card {
    padding: 16px;
  }

  .chart-tall,
  .chart-short {
    height: 240px;
  }

  table {
    min-width: 820px;
  }
}
</style>
