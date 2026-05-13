<script setup lang="ts">
import { Icon } from '@iconify/vue';
import { computed } from 'vue';

interface TreatmentItem {
  service: string;
  description?: string;
  cost: number;
}

interface Appointment {
  date: string;
  time: string;
  procedure: string;
}

interface Props {
  paperSize?: 'A4' | 'A3';
  clinicPrimary: string;
  clinicSecondary: string;
  clinicTertiary?: string;
  address: string;
  phone: string;
  patientName: string;
  invoiceDate: string;
  invoiceNumber: string;
  treatmentPlan: TreatmentItem[];
  appointments: Appointment[];
  amountPaid: number;
  notes?: string;
}

const props = withDefaults(defineProps<Props>(), {
  paperSize: 'A4',
  treatmentPlan: () => [],
  appointments: () => [],
  notes: "Please follow all post-operative instructions provided during your visit."
});

const totalAmount = computed(() =>
  props.treatmentPlan.reduce((sum, item) => sum + item.cost, 0)
);

const amountRemaining = computed(() => totalAmount.value - props.amountPaid);

const containerClasses = computed(() => ({
  'bill-sheet': true,
  'size-a4': props.paperSize === 'A4',
  'size-a3': props.paperSize === 'A3',
}));

// Formatter for currency
const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
};
</script>

<template>
  <div class="print-wrapper">
    <article :class="containerClasses">
      <header class="bill-header">
        <div class="identity">
          <h1 class="h1-brand">{{ props.clinicPrimary }}</h1>
          <h2 class="h2-sub-brand">{{ props.clinicSecondary }}</h2>
          <h3 v-if="props.clinicTertiary" class="h3-tagline">{{ props.clinicTertiary }}</h3>
        </div>

        <div class="contact-block">
          <div class="contact-row">
            <span>{{ props.address }}</span>
            <Icon icon="mdi:map-marker-outline" class="icon-small" />
          </div>
          <div class="contact-row">
            <span>{{ props.phone }}</span>
            <Icon icon="mdi:phone-outline" class="icon-small" />
          </div>
        </div>
      </header>

      <section class="meta-section">
        <div class="field-box">
          <label>Patient Name</label>
          <div class="field-value">{{ props.patientName }}</div>
        </div>
        <div class="field-box">
          <label>Invoice Date</label>
          <div class="field-value">{{ props.invoiceDate }}</div>
        </div>
        <div class="field-box">
          <label>Invoice No.</label>
          <div class="field-value">#{{ props.invoiceNumber }}</div>
        </div>
      </section>

      <section class="content-section">
        <h4 class="section-title">Treatment Plan & Services</h4>
        <div class="table-header">
          <span>Service Description</span>
          <span class="text-right">Amount</span>
        </div>
        <div v-for="(item, i) in props.treatmentPlan" :key="i" class="table-row">
          <div class="service-info">
            <span class="service-name">{{ item.service }}</span>
            <small v-if="item.description" class="service-desc">{{ item.description }}</small>
          </div>
          <div class="service-cost">{{ formatCurrency(item.cost) }}</div>
        </div>
      </section>

      <div class="secondary-info-grid">
        <section>
          <h4 class="section-title">Scheduled Appointments</h4>
          <div v-for="(appt, j) in props.appointments" :key="j" class="appt-item">
            <Icon icon="mdi:calendar-clock" class="appt-icon" />
            <div>
              <strong>{{ appt.date }}</strong> @ {{ appt.time }}
              <div class="appt-proc">{{ appt.procedure }}</div>
            </div>
          </div>
        </section>

        <section>
          <h4 class="section-title">Clinical Notes</h4>
          <div class="notes-box">{{ props.notes }}</div>
        </section>
      </div>

      <footer class="bill-footer">
        <div class="financial-summary">
          <div class="summary-line">
            <span>Total Treatment Cost</span>
            <span>{{ formatCurrency(totalAmount) }}</span>
          </div>
          <div class="summary-line highlight">
            <span>Amount Paid</span>
            <span>- {{ formatCurrency(props.amountPaid) }}</span>
          </div>
          <div class="summary-line balance">
            <span>Balance Remaining</span>
            <span>{{ formatCurrency(amountRemaining) }}</span>
          </div>
        </div>

        <div class="payment-instructions">
          <Icon icon="mdi:information-outline" />
          <span>Payment is due within 15 days. Make all checks payable to {{ props.clinicPrimary }}.</span>
        </div>
      </footer>
    </article>
  </div>
</template>

<style scoped>
.print-wrapper {
  background-color: #f0f0f0;
  min-height: 100vh;
  padding: 40px 20px;
  display: flex;
  justify-content: center;
}

.bill-sheet {
  background: white;
  color: black;
  font-family: 'Inter', sans-serif;
  display: flex;
  flex-direction: column;
  position: relative;
  box-sizing: border-box;
}

/* Page Sizes */
.size-a4 { width: 210mm; height: 297mm; padding: 20mm; }
.size-a3 { width: 297mm; height: 420mm; padding: 30mm; font-size: 1.2rem; }

/* Header & Brand */
.bill-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  border-bottom: 5px solid black;
  padding-bottom: 25px;
  margin-bottom: 40px;
}

.h1-brand { font-size: 3.5rem; font-weight: 900; margin: 0; line-height: 0.9; letter-spacing: -2px; }
.h2-sub-brand { font-size: 1.4rem; font-weight: 700; margin: 5px 0 0; text-transform: uppercase; }
.h3-tagline { font-size: 1rem; font-weight: 400; color: #444; margin: 2px 0 0; }

.contact-block { text-align: right; }
.contact-row { display: flex; align-items: center; justify-content: flex-end; gap: 8px; margin-bottom: 4px; }

/* Metadata */
.meta-section {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr;
  gap: 20px;
  margin-bottom: 40px;
}

.field-box { border-bottom: 1.5px solid black; padding-bottom: 8px; }
.field-box label { display: block; font-size: 0.7rem; font-weight: 900; text-transform: uppercase; margin-bottom: 4px; }
.field-value { font-size: 1.1rem; font-weight: 500; }

/* Content Section */
.section-title {
  text-transform: uppercase;
  font-size: 0.8rem;
  font-weight: 900;
  border-bottom: 3px solid black;
  padding-bottom: 5px;
  margin-bottom: 15px;
}

.table-header {
  display: flex;
  justify-content: space-between;
  font-weight: 800;
  font-size: 0.85rem;
  padding: 0 5px 10px;
}

.table-row {
  display: flex;
  justify-content: space-between;
  padding: 12px 5px;
  border-bottom: 1px solid #eee;
}

.service-name { display: block; font-weight: 700; font-size: 1.05rem; }
.service-desc { color: #666; font-size: 0.85rem; }
.service-cost { font-weight: 700; font-size: 1.05rem; }

/* Secondary Info Grid */
.secondary-info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 40px;
  margin-top: 40px;
  flex-grow: 1;
}

.appt-item {
  display: flex;
  gap: 12px;
  margin-bottom: 15px;
  align-items: flex-start;
}

.appt-icon { font-size: 1.4rem; margin-top: 2px; }
.appt-proc { font-size: 0.9rem; color: #555; }

.notes-box {
  font-style: italic;
  font-size: 0.95rem;
  line-height: 1.5;
  color: #333;
}

/* Footer & Summary */
.bill-footer {
  margin-top: 50px;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

.financial-summary {
  width: 350px;
}

.summary-line {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
  font-size: 1rem;
}

.summary-line.highlight { color: #666; }

.summary-line.balance {
  border-top: 3px solid black;
  margin-top: 10px;
  padding-top: 15px;
  font-size: 1.4rem;
  font-weight: 900;
}

.payment-instructions {
  width: 100%;
  margin-top: 40px;
  padding-top: 20px;
  border-top: 1px solid #ddd;
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 0.8rem;
  color: #666;
}

.text-right { text-align: right; }

@media print {
  @page { margin: 0; }
  .print-wrapper { padding: 0; background: none; }
  .bill-sheet { box-shadow: none; }
}
</style>
