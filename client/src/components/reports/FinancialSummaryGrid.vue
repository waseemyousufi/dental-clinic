<script setup lang="ts">
interface TreatmentYieldItem {
  name: string
  amount: number
  percentage: number
}

interface FinancialSummary {
  grossRevenue: number
  netCollected: number
  accountsReceivable: number
  treatmentYield: TreatmentYieldItem[]
  grossRevenueTrend?: string
  netCollectedTrend?: string
  accountsReceivableTrend?: string
}

interface OperationalSummary {
  appointments_total: number
  appointments_completed: number
  appointments_cancelled: number
  appointments_no_show: number
  new_patients: number
  collection_rate: number
}

defineProps<{
  summary: FinancialSummary
  operationalSummary: OperationalSummary
}>()

const formatCurrency = (value: number) => {
  return `AFN ${value.toLocaleString()}`
}

const formatPercentage = (value: number) => {
  return `${value}%`
}
</script>

<template>
  <div class="financial-summary-grid">
    <div class="card">
      <h3>Gross Invoiced</h3>
      <p class="value">{{ formatCurrency(summary.grossRevenue) }}</p>
      <p class="hint">{{ summary.grossRevenueTrend || 'Current period total' }}</p>
    </div>

    <div class="card">
      <h3>Net Cash Revenue</h3>
      <p class="value">{{ formatCurrency(summary.netCollected) }}</p>
      <p class="hint">{{ summary.netCollectedTrend || 'Payments received in-range' }}</p>
    </div>

    <div class="card">
      <h3>Total Outstanding (A/R)</h3>
      <p class="value">{{ formatCurrency(summary.accountsReceivable) }}</p>
      <p class="hint">{{ summary.accountsReceivableTrend || 'Follow-up balance queue' }}</p>
    </div>

    <div class="card mix-card">
      <h3>Top Treatment Yields</h3>
      <ul class="space-y-2">
        <li v-for="treatment in summary.treatmentYield" :key="treatment.name" class="mix-row">
          <span>{{ treatment.name }}:</span>
          <span>{{ formatCurrency(treatment.amount) }} ({{ formatPercentage(treatment.percentage) }})</span>
        </li>
      </ul>
    </div>

    <div class="card compact">
      <h3>Appointments</h3>
      <p class="value">{{ operationalSummary.appointments_total }}</p>
      <p class="hint">Completed: {{ operationalSummary.appointments_completed }}</p>
    </div>

    <div class="card compact">
      <h3>No-show / Cancelled</h3>
      <p class="value">{{ operationalSummary.appointments_no_show }}/{{ operationalSummary.appointments_cancelled }}</p>
      <p class="hint">No-show / Cancelled</p>
    </div>

    <div class="card compact">
      <h3>New Patients</h3>
      <p class="value">{{ operationalSummary.new_patients }}</p>
      <p class="hint">First registrations in range</p>
    </div>

    <div class="card compact">
      <h3>Collection Rate</h3>
      <p class="value">{{ formatPercentage(operationalSummary.collection_rate) }}</p>
      <p class="hint">Cash vs billed value</p>
    </div>
  </div>
</template>

<style scoped>
.financial-summary-grid {
  display: grid;
  grid-template-columns: repeat(12, minmax(0, 1fr));
  gap: 0.75rem;
}

.card {
  grid-column: span 3;
  background: #ffffff;
  border-radius: 0.9rem;
  padding: 1rem;
}

.card h3 {
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #6b7280;
  margin: 0;
}

.value {
  margin: 0.55rem 0 0;
  font-size: 1.5rem;
  font-weight: 800;
  color: #111827;
}

.hint {
  margin: 0.35rem 0 0;
  font-size: 0.75rem;
  color: #6b7280;
}

.mix-card {
  grid-column: span 6;
}

.mix-row {
  display: flex;
  justify-content: space-between;
  font-size: 0.85rem;
  color: #1f2937;
}

.compact .value {
  font-size: 1.3rem;
}

@media (max-width: 1200px) {
  .card,
  .mix-card {
    grid-column: span 6;
  }
}

@media (max-width: 700px) {
  .card,
  .mix-card {
    grid-column: span 12;
  }
}
</style>
