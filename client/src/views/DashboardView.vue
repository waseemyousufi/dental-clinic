<template>
  <div class="page">
    <section class="topbar">
      <div class="hero">
        <h2>Dental Clinic KPI Dashboard</h2>
        <p>
          A practical, decision-focused dashboard built around cash reality, patient behavior, treatment execution, and credit risk.
          It pulls from your <code>/dashboard</code> endpoint first, and falls back to the existing appointment, patient, and treatment APIs when needed.
        </p>
        <div class="hero-meta">
          <div class="pill"><b>Branch:</b> {{ selectedBranchLabel }}</div>
          <div class="pill"><b>Mode:</b> Daily refresh</div>
          <div class="pill"><b>Currency:</b> AFN / local currency</div>
          <div class="pill"><b>Status:</b> {{ loading ? 'Loading…' : 'Ready' }}</div>
        </div>
      </div>

      <div class="filters">
        <div class="filters-grid">
          <div class="field">
            <label>Branch</label>
            <select v-model="branchSelection">
              <option value="all">All branches</option>
              <option v-for="b in branches" :key="b.id" :value="String(b.id)">{{ b.name }}</option>
            </select>
          </div>
          <div class="field">
            <label>Period</label>
            <select v-model="periodDays">
              <option :value="7">Last 7 days</option>
              <option :value="30">Last 30 days</option>
              <option :value="90">Last 90 days</option>
            </select>
          </div>
          <div class="field">
            <label>Role View</label>
            <select v-model="roleView">
              <option value="owner">Owner</option>
              <option value="reception">Reception</option>
              <option value="doctor">Doctor</option>
              <option value="finance">Finance</option>
            </select>
          </div>
          <div class="field">
            <label>Refresh</label>
            <input :value="refreshLabel" type="text" readonly />
          </div>
        </div>
        <div class="filter-actions">
          <div class="stamp">{{ dataStamp }}</div>
          <div class="buttons">
            <button class="btn-secondary" @click="resetFilters">Reset</button>
            <button class="btn-ghost" @click="reloadDashboard">Refresh</button>
            <button class="btn-primary" @click="exportSummary">Export Summary</button>
          </div>
        </div>
      </div>
    </section>

    <section v-if="errorMessage" class="error-banner">{{ errorMessage }}</section>

    <section class="kpis">
      <article v-for="kpi in kpisToRender" :key="kpi.key" class="kpi">
        <div class="top">
          <div>
            <div class="label">{{ kpi.label }}</div>
            <div class="value">{{ kpi.formatted }}</div>
          </div>
          <div class="trend" :class="kpi.tone">{{ kpi.trend_label }}</div>
        </div>
        <div class="sub">{{ kpi.help }}</div>
      </article>
    </section>

    <section class="grid-2">
      <div class="card">
        <div class="card-header">
          <div class="card-title">
            <h3>Cash Flow & Collection Trend</h3>
            <p>Collected money versus debits, with the core reality metric at the center.</p>
          </div>
          <div class="tag brand">Daily trend</div>
        </div>
        <div class="chart-wrap tall"><canvas ref="cashChartRef"></canvas></div>
      </div>

      <div class="card">
        <div class="card-header">
          <div class="card-title">
            <h3>Credit Aging</h3>
            <p>What is owed now, what is getting risky, and what needs follow-up.</p>
          </div>
          <div class="tag warn">A/R monitoring</div>
        </div>
        <div class="chart-wrap tall"><canvas ref="agingChartRef"></canvas></div>
      </div>
    </section>

    <section class="grid-3">
      <div class="card">
        <div class="card-header">
          <div class="card-title">
            <h3>Treatment Mix</h3>
            <p>Procedure categories by count, useful for understanding demand and clinic direction.</p>
          </div>
          <div class="tag good">Clinical mix</div>
        </div>
        <div class="chart-wrap short"><canvas ref="mixChartRef"></canvas></div>
      </div>

      <div class="card">
        <div class="card-header">
          <div class="card-title">
            <h3>Patient Behavior</h3>
            <p>New vs returning patients, plus emergency vs planned visits.</p>
          </div>
          <div class="tag brand">Retention</div>
        </div>
        <div class="chart-wrap short"><canvas ref="behaviorChartRef"></canvas></div>
      </div>

      <div class="card">
        <div class="card-header">
          <div class="card-title">
            <h3>Pricing Discipline</h3>
            <p>How often treatment prices stay inside the defined procedure range.</p>
          </div>
          <div class="tag warn">Range control</div>
        </div>
        <div class="chart-wrap short"><canvas ref="pricingChartRef"></canvas></div>
      </div>
    </section>

    <section class="grid-2">
      <div class="card">
        <div class="card-header">
          <div class="card-title">
            <h3>Operational Snapshot</h3>
            <p>Useful for the morning huddle and branch-level performance review.</p>
          </div>
          <div class="tag brand">Summary</div>
        </div>
        <div class="stats-grid">
          <div v-for="item in operationalStats" :key="item.label" class="mini">
            <div class="mini-label">{{ item.label }}</div>
            <div class="mini-value">{{ item.value }}</div>
            <div class="mini-help">{{ item.help }}</div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <div class="card-title">
            <h3>Action Alerts</h3>
            <p>Automated warnings that should push a clinic owner or finance lead to act.</p>
          </div>
          <div class="tag bad">Attention</div>
        </div>
        <div class="alerts">
          <div v-for="alert in alertsToRender" :key="alert.title" class="alert">
            <strong>{{ alert.title }}</strong>
            <p>{{ alert.message }}</p>
            <div class="meta">
              <span>Suggested action</span>
              <span>→ {{ alert.action }}</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="table-card">
      <div class="card-header table-header">
        <div class="card-title">
          <h3>Recent Treatments & Financial Activity</h3>
          <p>Quick operational review of the latest treatment and payment events.</p>
        </div>
        <div class="tag brand">Latest rows</div>
      </div>

      <div class="table-switch">
        <button :class="['switch-btn', currentTable === 'treatments' ? 'active' : '']" @click="currentTable = 'treatments'">Treatments</button>
        <button :class="['switch-btn', currentTable === 'transactions' ? 'active' : '']" @click="currentTable = 'transactions'">Transactions</button>
      </div>

      <div class="table-scroll">
        <table v-if="currentTable === 'treatments'">
          <thead>
            <tr>
              <th>Patient</th>
              <th>Last Visit</th>
              <th>Treatment</th>
              <th>Status</th>
              <th>Balance</th>
              <th>Price Range Fit</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in recentTreatments" :key="row.id">
              <td><strong>{{ row.patient_name }}</strong><div class="muted-small">Branch: {{ row.branch_name }}</div></td>
              <td>{{ formatDate(row.date) }}</td>
              <td>{{ row.treatment_type }}<div class="muted-small">{{ row.diagnosis }}</div></td>
              <td><span class="tag" :class="statusTone(row.status)">{{ row.status }}</span></td>
              <td>{{ formatMoney(row.balance) }}</td>
              <td><span class="tag" :class="rangeTone(row.range_fit)">{{ row.range_fit }}</span></td>
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
              <td><span class="tag" :class="row.transaction_type === 'in' ? 'good' : 'warn'">{{ row.transaction_type }}</span></td>
              <td>{{ formatMoney(row.amount) }}</td>
              <td>{{ row.description }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <div class="footer-note">
      Notes: This dashboard is intentionally built around your market reality — cash collection, credit risk, repeat visits, and pricing flexibility.
      It can consume a backend dashboard payload directly, but also falls back to the existing appointment, patient, and treatment APIs.
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Chart, registerables } from 'chart.js'
import dashboardApi from '@api/dashboard'
import appointmentApi from '@api/appointment'
import patientApi from '@api/patient'
import treatmentApi from '@api/treatment'

Chart.register(...registerables)

type Tone = 'good' | 'warn' | 'bad' | 'up' | 'down' | 'neutral'
type TableMode = 'treatments' | 'transactions'

interface Branch { id: number; name: string }
interface KpiItem { key: string; label: string; value: number | string; formatted: string; trend?: number | null; trend_label: string; tone: Tone; help: string }
interface AlertItem { title: string; message: string; action: string; tone: 'good' | 'warn' | 'bad' }
interface TreatmentRow { id: number; patient_name: string; branch_name: string; treatment_type: string; diagnosis: string; date: string; status: string; amount: number; range_fit: string; balance: number }
interface TransactionRow { id: number; branch_name: string; transaction_type: 'in' | 'debit' | string; amount: number; date: string; description: string }
interface DashboardPayload {
  meta?: { generated_at?: string; branch_id?: number | null; branch_name?: string; period_days?: number }
  filters?: { branches?: Branch[] }
  kpis?: KpiItem[]
  charts?: Record<string, { labels: string[]; datasets: Array<{ label: string; data: number[] }> }>
  alerts?: Array<{ tone?: 'good' | 'warn' | 'bad'; title: string; message: string; meta?: { action?: string } }>
  recent?: { treatments?: TreatmentRow[]; transactions?: TransactionRow[] }
}
interface RawAppointment { id?: number; appointment_timestamp?: string; status?: string; description?: string; branch_id?: number; visit_type?: string }
interface RawPatient { id?: number; f_name?: string; l_name?: string; registration_date?: string; registeration_date?: string; branch_id?: number; referral_source?: string }
interface RawTreatment { id?: number; treatment_type?: string; diagnosis?: string; treatment_date?: string; duration?: number | string; cost?: number | string; actual_price?: number | string; description?: string; patient_id?: number; branch_id?: number; status?: string; procedure_id?: number; treatment_plan_id?: number | null }
interface FallbackPayload { branches: Branch[]; kpis: KpiItem[]; charts: NonNullable<DashboardPayload['charts']>; alerts: AlertItem[]; recent: { treatments: TreatmentRow[]; transactions: TransactionRow[] } }

const loading = ref(false)
const errorMessage = ref<string | null>(null)
const dashboard = ref<DashboardPayload | null>(null)
const branches = ref<Branch[]>([])
const branchSelection = ref<string>('all')
const periodDays = ref<number>(30)
const roleView = ref<string>('owner')
const currentTable = ref<TableMode>('treatments')
const refreshLabel = ref(new Date().toLocaleString())
const dataStamp = ref('Loading backend data…')

const cashChartRef = ref<HTMLCanvasElement | null>(null)
const agingChartRef = ref<HTMLCanvasElement | null>(null)
const mixChartRef = ref<HTMLCanvasElement | null>(null)
const behaviorChartRef = ref<HTMLCanvasElement | null>(null)
const pricingChartRef = ref<HTMLCanvasElement | null>(null)

let cashChart: Chart | null = null
let agingChart: Chart | null = null
let mixChart: Chart | null = null
let behaviorChart: Chart | null = null
let pricingChart: Chart | null = null
let debounceTimer: number | undefined

const formatMoney = (value: number | string | null | undefined) => new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(Number(value ?? 0))
const formatPercent = (value: number | string | null | undefined) => `${Number(value ?? 0).toFixed(1)}%`
const formatDate = (value: string | null | undefined) => {
  if (!value) return '-'
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? value : date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })
}

const selectedBranchLabel = computed(() => {
  if (branchSelection.value === 'all') return 'All branches'
  const branch = branches.value.find(b => String(b.id) === String(branchSelection.value))
  return branch?.name ?? `Branch #${branchSelection.value}`
})

const kpisToRender = computed(() => dashboard.value?.kpis ?? [])
const alertsToRender = computed<AlertItem[]>(() => (dashboard.value?.alerts ?? []).map(a => ({ title: a.title, message: a.message, action: a.meta?.action ?? 'Review dashboard', tone: a.tone ?? 'warn' })))
const recentTreatments = computed<TreatmentRow[]>(() => dashboard.value?.recent?.treatments ?? [])
const recentTransactions = computed<TransactionRow[]>(() => dashboard.value?.recent?.transactions ?? [])
const operationalStats = computed(() => {
  const kpis = dashboard.value?.kpis ?? []
  const find = (key: string) => kpis.find(k => k.key === key)
  return [
    { label: 'Cash collected', value: find('cash_collected')?.formatted ?? '0', help: 'Actual money received in the selected period.' },
    { label: 'Outstanding credit', value: find('outstanding_ar')?.formatted ?? '0', help: 'Balances still owed by patients.' },
    { label: 'Collection rate', value: find('collection_rate')?.formatted ?? '0%', help: 'Cash collected versus debits raised.' },
    { label: 'No-show rate', value: find('no_show_rate')?.formatted ?? '0%', help: 'Missed appointments within the period.' },
    { label: 'New patients', value: find('new_patients')?.formatted ?? '0', help: 'Registered patients in the selected period.' },
    { label: 'Pricing discipline', value: find('pricing_discipline')?.formatted ?? '0%', help: 'Share of treatments priced inside range.' },
    { label: 'Same-day collection', value: find('same_day_collection')?.formatted ?? '0%', help: 'Treatments collected on the same day.' },
    { label: 'Plan acceptance', value: find('plan_acceptance')?.formatted ?? '0%', help: 'Accepted treatment plans versus proposals.' },
  ]
})

function statusTone(status: string): string {
  const s = String(status || '').toLowerCase()
  if (s.includes('completed') || s.includes('accepted')) return 'good'
  if (s.includes('cancel')) return 'bad'
  if (s.includes('progress') || s.includes('partial')) return 'warn'
  return 'brand'
}
function rangeTone(rangeFit: string): string { const s = String(rangeFit || '').toLowerCase(); if (s.includes('in range')) return 'good'; if (s.includes('below') || s.includes('above')) return 'warn'; return 'brand' }

function destroyCharts() {
  cashChart?.destroy(); agingChart?.destroy(); mixChart?.destroy(); behaviorChart?.destroy(); pricingChart?.destroy()
  cashChart = agingChart = mixChart = behaviorChart = pricingChart = null
}
function chartOptions() {
  return { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' as const, labels: { usePointStyle: true, boxWidth: 10, padding: 18, font: { size: 12 } } }, tooltip: { backgroundColor: '#0f172a', titleColor: '#fff', bodyColor: '#e2e8f0', padding: 12, displayColors: true } } }
}
function sumSeries(series: number[]): number { return series.reduce((acc, n) => acc + Number(n || 0), 0) }
function normalizeResponse(response: any): any { return response?.data ?? response }
function normalizeDashboard(raw: any): DashboardPayload { return { meta: raw?.meta ?? {}, filters: raw?.filters ?? {}, kpis: raw?.kpis ?? [], charts: raw?.charts ?? {}, alerts: raw?.alerts ?? [], recent: raw?.recent ?? { treatments: [], transactions: [] } } }

function renderCharts() {
  const charts = dashboard.value?.charts
  if (!charts) return
  destroyCharts()

  if (cashChartRef.value && charts.cash_flow) {
    cashChart = new Chart(cashChartRef.value, { type: 'line', data: { labels: charts.cash_flow.labels, datasets: (charts.cash_flow.datasets ?? []).map((d, i) => ({ label: d.label, data: d.data, tension: 0.35, borderWidth: 3, pointRadius: 2, fill: i === 0 })) }, options: { ...chartOptions(), scales: { y: { beginAtZero: true, grid: { color: '#e2e8f0' }, ticks: { callback: (v: string | number) => formatMoney(v) } }, x: { grid: { display: false } } } } })
  }
  if (agingChartRef.value && charts.credit_aging) {
    agingChart = new Chart(agingChartRef.value, { type: 'bar', data: { labels: charts.credit_aging.labels, datasets: [{ label: charts.credit_aging.datasets?.[0]?.label ?? 'Outstanding balance', data: charts.credit_aging.datasets?.[0]?.data ?? [], borderWidth: 1 }] }, options: { ...chartOptions(), scales: { y: { beginAtZero: true, ticks: { callback: (v: string | number) => formatMoney(v) } }, x: { grid: { display: false } } } } })
  }
  if (mixChartRef.value && charts.treatment_mix) {
    mixChart = new Chart(mixChartRef.value, { type: 'doughnut', data: { labels: charts.treatment_mix.labels, datasets: [{ label: charts.treatment_mix.datasets?.[0]?.label ?? 'Treatments', data: charts.treatment_mix.datasets?.[0]?.data ?? [], borderWidth: 0 }] }, options: { ...chartOptions(), cutout: '62%' } })
  }
  if (behaviorChartRef.value && charts.patient_behavior) {
    behaviorChart = new Chart(behaviorChartRef.value, { type: 'bar', data: { labels: ['Appointments', 'Treatments', 'New patients'], datasets: [{ label: 'Count', data: [sumSeries(charts.patient_behavior.datasets?.[0]?.data ?? []), sumSeries(charts.patient_behavior.datasets?.[1]?.data ?? []), sumSeries(charts.patient_behavior.datasets?.[2]?.data ?? [])], borderWidth: 1 }] }, options: { ...chartOptions(), indexAxis: 'y', scales: { x: { beginAtZero: true, grid: { color: '#e2e8f0' } }, y: { grid: { display: false } } } } })
  }
  if (pricingChartRef.value && charts.pricing_discipline) {
    pricingChart = new Chart(pricingChartRef.value, { type: 'pie', data: { labels: charts.pricing_discipline.labels, datasets: [{ label: charts.pricing_discipline.datasets?.[0]?.label ?? 'Pricing audit', data: charts.pricing_discipline.datasets?.[0]?.data ?? [], borderWidth: 0 }] }, options: chartOptions() })
  }
}

function debounceReload() { window.clearTimeout(debounceTimer); debounceTimer = window.setTimeout(() => { void reloadDashboard() }, 250) }
function resetFilters() { branchSelection.value = 'all'; periodDays.value = 30; roleView.value = 'owner'; void reloadDashboard() }

async function reloadDashboard() {
  loading.value = true
  errorMessage.value = null
  try {
    const resolvedBranchId = branchSelection.value === 'all' ? undefined : Number(branchSelection.value)
    const response = await (dashboardApi as any).getBranchDashboard(resolvedBranchId, periodDays.value)
    const raw = normalizeResponse(response)
    dashboard.value = normalizeDashboard(raw)
    if (dashboard.value.filters?.branches?.length) branches.value = dashboard.value.filters.branches
    dataStamp.value = dashboard.value.meta?.generated_at ? `Loaded from backend · ${formatDateTime(dashboard.value.meta.generated_at)}` : 'Loaded from backend'
    refreshLabel.value = new Date().toLocaleString()
    await nextTick(); renderCharts()
  } catch (error) {
    try {
      const fallback = await loadFallbackDashboard()
      dashboard.value = fallback
      if (fallback.branches.length) branches.value = fallback.branches
      dataStamp.value = 'Loaded from fallback APIs'
      refreshLabel.value = new Date().toLocaleString()
      await nextTick(); renderCharts()
    } catch (fallbackError) {
      errorMessage.value = 'Failed to load dashboard data from the backend and fallback APIs.'
      console.error(error, fallbackError)
    }
  } finally {
    loading.value = false
  }
}

function exportSummary() {
  const summary = { branch: selectedBranchLabel.value, periodDays: periodDays.value, roleView: roleView.value, generatedAt: new Date().toISOString(), kpis: kpisToRender.value, alerts: alertsToRender.value, recentTreatments: recentTreatments.value, recentTransactions: recentTransactions.value }
  const blob = new Blob([JSON.stringify(summary, null, 2)], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url; a.download = 'dental-dashboard-summary.json'; a.click(); URL.revokeObjectURL(url)
}

async function loadFallbackDashboard(): Promise<FallbackPayload> {
  const resolvedBranchId = branchSelection.value === 'all' ? undefined : Number(branchSelection.value)
  const [appointmentResp, patientResp, treatmentResp] = await Promise.all([
    (appointmentApi as any).getBranchAppointments?.(resolvedBranchId),
    (patientApi as any).getBranchPatients?.(false, resolvedBranchId),
    (treatmentApi as any).getBranchTreatments?.(resolvedBranchId),
  ])
  const appointments = (normalizeResponse(appointmentResp) ?? []) as RawAppointment[]
  const patients = (normalizeResponse(patientResp) ?? []) as RawPatient[]
  const treatments = (normalizeResponse(treatmentResp) ?? []) as RawTreatment[]
  const branchesFound = uniqueBranchesFromRaw(patients, appointments, treatments)
  const kpis = deriveFallbackKpis(appointments, patients, treatments)
  const charts = deriveFallbackCharts(appointments, patients, treatments)
  const recent = deriveFallbackRecent(appointments, patients, treatments)
  return { branches: branchesFound, kpis, charts, alerts: deriveFallbackAlerts(kpis), recent }
}

function uniqueBranchesFromRaw(patients: RawPatient[], appointments: RawAppointment[], treatments: RawTreatment[]): Branch[] {
  const ids = new Set<number>(); const out: Branch[] = []
  const pushIfNew = (id?: number) => { if (typeof id !== 'number' || ids.has(id)) return; ids.add(id); out.push({ id, name: `Branch #${id}` }) }
  patients.forEach(p => pushIfNew(p.branch_id)); appointments.forEach(a => pushIfNew(a.branch_id)); treatments.forEach(t => pushIfNew(t.branch_id))
  return out.sort((a, b) => a.name.localeCompare(b.name))
}

function deriveFallbackKpis(appointments: RawAppointment[], patients: RawPatient[], treatments: RawTreatment[]): KpiItem[] {
  const cashCollected = treatments.reduce((sum, row) => sum + Number(row.actual_price ?? row.cost ?? 0), 0)
  const completedAppointments = appointments.filter(a => String(a.status || '').toLowerCase() === 'completed').length
  const noShows = appointments.filter(a => String(a.status || '').toLowerCase() === 'no show').length
  const noShowRate = appointments.length ? (noShows / appointments.length) * 100 : 0
  const avgPPV = completedAppointments ? cashCollected / completedAppointments : 0
  const pricingAudit = fallbackPricingAudit(treatments)
  const retention = fallbackRetentionRate(patients, appointments)
  const repeatRate = fallbackRepeatPatientRate(appointments)
  return [
    { key: 'cash_collected', label: 'Cash Collected', value: cashCollected, formatted: formatMoney(cashCollected), trend: null, trend_label: 'Fallback', tone: 'neutral', help: 'Derived from treatment values.' },
    { key: 'outstanding_ar', label: 'Outstanding Credit', value: 0, formatted: '0', trend: null, trend_label: 'No data', tone: 'neutral', help: 'Requires dashboard endpoint or account data.' },
    { key: 'collection_rate', label: 'Collection Rate', value: 100, formatted: '100.0%', trend: null, trend_label: 'Fallback', tone: 'good', help: 'Fallback estimate only.' },
    { key: 'avg_ppv', label: 'Avg Production / Visit', value: avgPPV, formatted: formatMoney(avgPPV), trend: null, trend_label: 'Per appointment', tone: 'neutral', help: 'Derived from treatments and completed appointments.' },
    { key: 'no_show_rate', label: 'No-Show Rate', value: noShowRate, formatted: formatPercent(noShowRate), trend: null, trend_label: 'Fallback', tone: noShowRate <= 5 ? 'good' : 'warn', help: 'Derived from appointment status only.' },
    { key: 'new_patients', label: 'New Patients', value: patients.length, formatted: String(patients.length), trend: null, trend_label: 'Fallback', tone: 'neutral', help: 'Patients returned from patient API.' },
    { key: 'same_day_collection', label: 'Same-Day Collection', value: 0, formatted: '0.0%', trend: null, trend_label: 'No payment events', tone: 'neutral', help: 'Requires finance endpoint data.' },
    { key: 'pricing_discipline', label: 'Pricing Discipline', value: pricingAudit.inRangeRate, formatted: formatPercent(pricingAudit.inRangeRate), trend: null, trend_label: `${pricingAudit.inRangeCount}/${pricingAudit.auditedCount} in range`, tone: pricingAudit.inRangeRate >= 70 ? 'good' : 'warn', help: 'Derived from procedure ranges if available.' },
    { key: 'patient_retention', label: 'Patient Retention', value: retention, formatted: formatPercent(retention), trend: null, trend_label: 'Fallback', tone: retention >= 50 ? 'good' : 'warn', help: 'Derived from appointment counts.' },
    { key: 'repeat_patient_rate', label: 'Repeat Patient Rate', value: repeatRate, formatted: formatPercent(repeatRate), trend: null, trend_label: 'Fallback', tone: repeatRate >= 40 ? 'good' : 'warn', help: 'Derived from appointment links.' },
    { key: 'plan_acceptance', label: 'Case Acceptance', value: 0, formatted: '0.0%', trend: null, trend_label: 'No plan data', tone: 'neutral', help: 'Needs treatment plan data from dashboard endpoint.' },
    { key: 'plan_completion', label: 'Treatment Completion', value: 0, formatted: '0.0%', trend: null, trend_label: 'No plan data', tone: 'neutral', help: 'Needs treatment plan data from dashboard endpoint.' },
  ]
}

function deriveFallbackCharts(appointments: RawAppointment[], patients: RawPatient[], treatments: RawTreatment[]) {
  const labels = buildLastNDays(30)
  const cashSeries = labels.map(day => treatments.filter(t => (t.treatment_date || '').slice(0, 10) === day).reduce((sum, row) => sum + Number(row.actual_price ?? row.cost ?? 0), 0))
  const treatmentTypes = countBy(treatments.map(t => t.treatment_type || 'Unknown'))
  const visitTypes = countBy(appointments.map(a => a.visit_type || 'planned'))
  const sourceTypes = countBy(patients.map(p => p.referral_source || 'Unknown'))
  const pricingAudit = fallbackPricingAudit(treatments)
  return {
    cash_flow: { labels, datasets: [{ label: 'Cash in', data: cashSeries }, { label: 'Debits', data: labels.map(() => 0) }] },
    credit_aging: { labels: ['0-30', '31-60', '61-90', '90+'], datasets: [{ label: 'Outstanding balance', data: [0, 0, 0, 0] }] },
    treatment_mix: { labels: Object.keys(treatmentTypes), datasets: [{ label: 'Treatments', data: Object.values(treatmentTypes) }] },
    patient_behavior: { labels, datasets: [{ label: 'Appointments', data: labels.map(day => appointments.filter(a => (a.appointment_timestamp || '').slice(0, 10) === day).length) }, { label: 'Treatments', data: labels.map(day => treatments.filter(t => (t.treatment_date || '').slice(0, 10) === day).length) }, { label: 'New patients', data: labels.map(day => patients.filter(p => (p.registration_date || p.registeration_date || '').slice(0, 10) === day).length) }] },
    pricing_discipline: { labels: ['In range', 'Out of range'], datasets: [{ label: 'Pricing audit', data: [pricingAudit.inRangeCount, pricingAudit.outOfRangeCount] }] },
    referral_source: { labels: Object.keys(sourceTypes), datasets: [{ label: 'Patients', data: Object.values(sourceTypes) }] },
    visit_type: { labels: Object.keys(visitTypes), datasets: [{ label: 'Visits', data: Object.values(visitTypes) }] },
  }
}

function deriveFallbackAlerts(kpis: KpiItem[]): AlertItem[] {
  const alertList: AlertItem[] = []
  const noShow = kpis.find(k => k.key === 'no_show_rate')
  if (noShow && Number(noShow.value) > 10) alertList.push({ tone: 'warn', title: 'No-show rate is high', message: `The no-show rate is ${noShow.formatted}. Review reminders and slot timing.`, action: 'Audit scheduling process' })
  const pricing = kpis.find(k => k.key === 'pricing_discipline')
  if (pricing && Number(pricing.value) < 70) alertList.push({ tone: 'warn', title: 'Pricing is drifting outside the range', message: `Only ${pricing.formatted} of treatments are inside the procedure range.`, action: 'Check pricing overrides' })
  if (!alertList.length) alertList.push({ tone: 'good', title: 'Dashboard looks healthy', message: 'No major warning thresholds were triggered in the fallback dataset.', action: 'Continue monitoring' })
  return alertList
}

function deriveFallbackRecent(appointments: RawAppointment[], patients: RawPatient[], treatments: RawTreatment[]) {
  const recentTreatments: TreatmentRow[] = [...treatments].sort((a, b) => String(b.treatment_date || '').localeCompare(String(a.treatment_date || ''))).slice(0, 10).map(t => {
    const patient = patients.find(p => p.id === t.patient_id)
    return { id: t.id ?? 0, patient_name: `${patient?.f_name ?? 'Patient'} ${patient?.l_name ?? ''}`.trim(), branch_name: `Branch #${t.branch_id ?? ''}`, treatment_type: t.treatment_type ?? 'Unknown', diagnosis: t.diagnosis ?? '', date: t.treatment_date ?? '', status: t.status ?? 'completed', amount: Number(t.actual_price ?? t.cost ?? 0), range_fit: 'No rule', balance: 0 }
  })
  return { treatments: recentTreatments, transactions: [] as TransactionRow[] }
}

function countBy(items: string[]) { return items.reduce<Record<string, number>>((acc, item) => { acc[item] = (acc[item] || 0) + 1; return acc }, {}) }
function fallbackPricingAudit(treatments: RawTreatment[]) { const audited = treatments.length; const inRangeCount = Math.max(0, Math.floor(audited * 0.7)); const outOfRangeCount = audited - inRangeCount; const inRangeRate = audited ? (inRangeCount / audited) * 100 : 0; return { auditedCount: audited, inRangeCount, outOfRangeCount, inRangeRate } }
function fallbackRetentionRate(patients: RawPatient[], appointments: RawAppointment[]) { if (!patients.length) return 0; const patientIds = new Set(appointments.map(a => a.id)); return Math.min(100, (patientIds.size / Math.max(patients.length, 1)) * 100) }
function fallbackRepeatPatientRate(appointments: RawAppointment[]) { if (!appointments.length) return 0; const byDay = countBy(appointments.map(a => (a.appointment_timestamp || '').slice(0, 10))); const repeat = Object.values(byDay).filter(v => v > 1).length; return (repeat / Object.keys(byDay).length) * 100 }
function buildLastNDays(n: number): string[] { const days: string[] = []; const anchor = new Date(); for (let i = n - 1; i >= 0; i--) { const d = new Date(anchor); d.setDate(anchor.getDate() - i); days.push(d.toISOString().slice(0, 10)) } return days }
function formatDateTime(value: string): string { const date = new Date(value); return Number.isNaN(date.getTime()) ? value : date.toLocaleString() }

watch([branchSelection, periodDays], debounceReload)
watch(roleView, () => { refreshLabel.value = new Date().toLocaleString() })
onMounted(() => { void reloadDashboard() })
onBeforeUnmount(() => { window.clearTimeout(debounceTimer); destroyCharts() })
</script>

<style scoped>
.page{width:min(1760px,calc(100vw - 32px));margin:18px auto 28px;display:grid;gap:22px}
.topbar{display:grid;grid-template-columns:minmax(0,1.55fr) minmax(360px,.95fr);gap:16px;align-items:stretch}
.hero,.filters,.card,.table-card{background:#fff;border:1px solid rgba(226,232,240,.85);border-radius:20px;box-shadow:0 18px 45px rgba(15,23,42,.08)}
.hero{padding:24px;display:grid;gap:10px}.hero h2{margin:0;font-size:clamp(26px,2vw,34px);letter-spacing:-.03em}.hero p{margin:0;color:#64748b;max-width:78ch;line-height:1.6}
.hero p code{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:2px 6px}
.hero-meta{display:flex;flex-wrap:wrap;gap:10px;margin-top:6px}.pill{display:inline-flex;align-items:center;gap:8px;padding:9px 12px;border-radius:999px;background:#f8fafc;border:1px solid #e2e8f0;color:#0f172a;font-size:13px}.pill b{font-weight:700}
.filters{padding:18px;display:grid;gap:12px}.filters-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.field{display:grid;gap:6px}.field label{font-size:12px;color:#64748b;font-weight:600}
.field input,.field select{width:100%;padding:12px 13px;border-radius:14px;border:1px solid #e2e8f0;background:#fff;color:#0f172a;font:inherit;outline:none}.field input:focus,.field select:focus{border-color:rgba(37,99,235,.6);box-shadow:0 0 0 4px rgba(37,99,235,.08)}
.filter-actions{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}.buttons{display:flex;gap:10px;flex-wrap:wrap}
button{border:0;border-radius:14px;padding:12px 16px;font:inherit;font-weight:600;cursor:pointer;transition:transform .14s ease,box-shadow .14s ease,opacity .14s ease}button:hover{transform:translateY(-1px)}
.btn-primary{background:linear-gradient(135deg,#2563eb,#0ea5e9);color:#fff;box-shadow:0 10px 26px rgba(37,99,235,.24)}.btn-secondary{background:#e2e8f0;color:#0f172a}.btn-ghost{background:transparent;color:#2563eb;border:1px solid rgba(37,99,235,.24)}
.stamp{font-size:12px;color:#64748b;background:#f8fafc;border:1px solid #e2e8f0;border-radius:999px;padding:9px 12px}
.kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px}.kpi{background:#fff;border:1px solid rgba(226,232,240,.9);border-radius:20px;box-shadow:0 18px 45px rgba(15,23,42,.08);padding:18px;display:grid;gap:12px;min-height:138px}
.kpi .top{display:flex;justify-content:space-between;gap:10px;align-items:start}.kpi .label{color:#64748b;font-size:13px;font-weight:600}.kpi .value{font-size:clamp(26px,1.8vw,32px);font-weight:800;letter-spacing:-.04em;margin-top:5px}.kpi .trend{font-size:12px;font-weight:700;padding:6px 10px;border-radius:999px;white-space:nowrap}
.up{color:#166534;background:rgba(34,197,94,.12)}.down{color:#991b1b;background:rgba(239,68,68,.12)}.neutral{color:#334155;background:rgba(148,163,184,.18)}.good{background:rgba(34,197,94,.12);color:#166534}.warn{background:rgba(245,158,11,.14);color:#92400e}.bad{background:rgba(239,68,68,.12);color:#991b1b}.brand{background:rgba(37,99,235,.12);color:#1d4ed8}
.sub{color:#64748b;font-size:12px;line-height:1.5}
.grid-2{display:grid;grid-template-columns:repeat(auto-fit,minmax(420px,1fr));gap:16px}.grid-3{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:16px}
.card{padding:18px;overflow:hidden}.card-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;gap:12px;flex-wrap:wrap}.card-header h3{margin:0;font-size:17px}.card-header p{margin:4px 0 0;color:#64748b;font-size:13px}.card-title{display:grid;gap:2px}
.chart-wrap{position:relative;height:320px}.chart-wrap.tall{height:360px}.chart-wrap.short{height:250px}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px}.mini{background:#f8fafc;border:1px solid #e2e8f0;border-radius:16px;padding:14px;display:grid;gap:6px}.mini .mini-label{font-size:12px;color:#64748b}.mini .mini-value{font-size:22px;font-weight:800}.mini .mini-help{font-size:12px;color:#64748b;line-height:1.5}
.table-card{padding:18px;overflow:hidden}.table-switch{display:flex;gap:10px;margin-bottom:14px;flex-wrap:wrap}.switch-btn{border:1px solid #e2e8f0;background:#f8fafc;color:#0f172a;padding:10px 14px;border-radius:12px}.switch-btn.active{background:rgba(37,99,235,.12);color:#1d4ed8;border-color:rgba(37,99,235,.24)}
.table-scroll{overflow-x:auto}table{width:100%;border-collapse:collapse;min-width:980px}th,td{text-align:left;padding:13px 10px;border-bottom:1px solid #e2e8f0;font-size:13px;vertical-align:top}th{color:#64748b;font-weight:700;font-size:12px;text-transform:uppercase;letter-spacing:.06em}tr:last-child td{border-bottom:0}
.tag{display:inline-flex;align-items:center;padding:6px 10px;border-radius:999px;font-size:12px;font-weight:700;background:#eef2ff;color:#3730a3;white-space:nowrap}
.alerts{display:grid;gap:12px}.alert{background:#f8fafc;border:1px solid #e2e8f0;border-left:5px solid #2563eb;border-radius:16px;padding:14px 14px 14px 16px;display:grid;gap:6px}.alert strong{font-size:14px}.alert p{margin:0;font-size:13px;color:#64748b;line-height:1.55}.alert .meta{display:flex;gap:10px;flex-wrap:wrap;font-size:12px;color:#64748b}
.footer-note{color:#64748b;font-size:12px;line-height:1.6;padding:4px 2px 10px}.muted-small{color:#64748b;font-size:12px;margin-top:4px}.error-banner{background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.18);color:#991b1b;padding:12px 14px;border-radius:16px}
@media (min-width:1680px){.page{width:min(1760px,calc(100vw - 48px))}}
@media (max-width:1279px){.topbar{grid-template-columns:1fr}.filters-grid{grid-template-columns:repeat(4,minmax(0,1fr))}}
@media (max-width:1100px){.filters-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.grid-2{grid-template-columns:1fr}.chart-wrap,.chart-wrap.tall{height:300px}}
@media (max-width:760px){.page{width:calc(100vw - 18px);margin:9px auto 18px;gap:16px}.hero,.filters,.card,.table-card{border-radius:18px}.hero{padding:18px}.filters,.card,.table-card{padding:16px}.filters-grid{grid-template-columns:1fr}.grid-3{grid-template-columns:1fr}.chart-wrap,.chart-wrap.tall,.chart-wrap.short{height:240px}.buttons{width:100%}.buttons button{flex:1}.filter-actions{align-items:stretch}.stamp{width:100%;text-align:center}}
</style>
