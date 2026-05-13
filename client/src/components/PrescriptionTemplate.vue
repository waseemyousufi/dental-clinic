<script setup lang="ts">
import { Icon } from '@iconify/vue';
import { computed } from 'vue';

interface Medication {
  name: string;
  dosage: string;
}

interface Props {
  paperSize?: 'A4' | 'A3';
  clinicPrimary: string;   // H1
  clinicSecondary: string; // H2
  clinicTertiary?: string; // H3
  address: string;
  phone: string;
  patientName: string;
  date: string;
  medications: Medication[];
}

const props = withDefaults(defineProps<Props>(), {
  paperSize: 'A4',
  medications: () => []
});

// Calculate scaling classes for A3 vs A4
const containerClasses = computed(() => ({
  'paper-sheet': true,
  'size-a4': props.paperSize === 'A4',
  'size-a3': props.paperSize === 'A3',
}));
</script>

<template>
  <div class="print-preview-wrapper">
    <article :class="containerClasses">
      <!-- Clinic Identity -->
      <header class="prescription-header">
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

      <!-- Patient Data Fields -->
      <section class="patient-section">
        <div class="field-box">
          <label>Patient Name</label>
          <div class="field-value">{{ props.patientName }}</div>
        </div>
        <div class="field-box">
          <label>Date</label>
          <div class="field-value">{{ props.date }}</div>
        </div>
      </section>

      <!-- Prescription Content -->
      <main class="prescription-main">
        <div class="rx-indicator">
          <Icon icon="mdi:prescription" />
        </div>

        <div class="medication-area">
          <div v-for="(med, i) in props.medications" :key="i" class="med-item">
            <div class="name">{{ med.name }}</div>
            <div class="dosage">{{ med.dosage }}</div>
          </div>
          <!-- Mock Dental Data if empty -->
          <template v-if="medications.length === 0">
            <div class="med-item">
              <div class="name">Chlorhexidine Gluconate 0.12% Oral Rinse</div>
              <div class="dosage">Swish 15ml for 30 seconds twice daily after brushing.</div>
            </div>
            <div class="med-item">
              <div class="name">Paracetamol 500mg + Codeine 30mg</div>
              <div class="dosage">1 tablet every 6 hours as needed for dental pain.</div>
            </div>
          </template>
        </div>
      </main>

      <!-- Minimal Footer -->
      <footer class="prescription-footer">
        <div class="legal-notice">
          This document is electronically generated for clinical records.
          Valid only with an authorized signature.
        </div>
        <div class="signature-column">
          <div class="signature-line"></div>
          <div class="signature-label">Medical Practitioner Signature</div>
        </div>
      </footer>
    </article>
  </div>
</template>

<style scoped>
/* Base Styles & Variables */
.print-preview-wrapper {
  background-color: #f0f0f0;
  min-height: 100vh;
  padding: 40px 20px;
  display: flex;
  justify-content: center;
}

.paper-sheet {
  background: white;
  color: black;
  font-family: 'Inter', sans-serif;
  display: flex;
  flex-direction: column;
  position: relative;
  box-sizing: border-box;
}

/* Size Handling */
.size-a4 {
  width: 210mm;
  height: 297mm;
  padding: 20mm;
}

.size-a3 {
  width: 297mm;
  height: 420mm;
  padding: 30mm;
  font-size: 1.4rem; /* Scaled for larger sheet */
}

/* Typography Hierarchy */
.h1-brand { font-size: 3.5rem; font-weight: 900; margin: 0; line-height: 0.9; letter-spacing: -2px; }
.h2-sub-brand { font-size: 1.4rem; font-weight: 700; margin: 5px 0 0; text-transform: uppercase; }
.h3-tagline { font-size: 1rem; font-weight: 400; color: #444; margin: 2px 0 0; }

.size-a3 .h1-brand { font-size: 5rem; }
.size-a3 .h2-sub-brand { font-size: 2rem; }

/* Header Layout */
.prescription-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  border-bottom: 5px solid black;
  padding-bottom: 25px;
  margin-bottom: 50px;
}

.contact-block { text-align: right; }
.contact-row {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 10px;
  font-weight: 500;
  margin-bottom: 5px;
}

/* Form Fields */
.patient-section {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 40px;
  margin-bottom: 60px;
}

.field-box {
  border-bottom: 1.5px solid black;
  padding-bottom: 8px;
}

.field-box label {
  display: block;
  font-size: 0.75rem;
  font-weight: 900;
  text-transform: uppercase;
  margin-bottom: 4px;
}

.field-value { font-size: 1.2rem; }

/* RX Main Area */
.prescription-main {
  display: flex;
  gap: 30px;
  flex: 1;
}

.rx-indicator { font-size: 5rem; line-height: 1; }

.med-item { margin-bottom: 35px; }
.med-item .name { font-size: 1.3rem; font-weight: 800; }
.med-item .dosage { font-size: 1.1rem; font-style: italic; margin-top: 5px; color: #333; }

/* Footer */
.prescription-footer {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-top: 40px;
}

.legal-notice { font-size: 0.8rem; max-width: 250px; line-height: 1.4; color: #666; }
.signature-line { width: 250px; border-top: 2px solid black; margin-bottom: 8px; }
.signature-label { font-size: 0.85rem; font-weight: 700; text-align: center; }

/* Print Logic */
@media print {
  @page { margin: 0; }
  .print-preview-wrapper { padding: 0; background: none; }
  .size-a4 { width: 210mm; height: 297mm; }
  .size-a3 { width: 297mm; height: 420mm; }
  .paper-sheet { box-shadow: none; }
}
</style>
