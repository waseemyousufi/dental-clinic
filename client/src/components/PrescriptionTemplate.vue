<script setup lang="ts">
import { Icon } from '@iconify/vue';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n'

interface Medication {
  name: string;
  dosage: string;
}

interface Props {
  paperSize?: 'A4' | 'A5';
  clinicPrimary: string;
  clinicSecondary: string;
  clinicTertiary?: string;
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

const { t } = useI18n()

const containerClasses = computed(() => ({
  'paper-sheet': true,
  'size-a4': props.paperSize === 'A4',
  'size-a5': props.paperSize === 'A5',
}));
</script>

<template>
  <div class="print-preview-wrapper">
    <article :class="containerClasses">

      <!-- HEADER -->
      <header class="prescription-header">

        <div class="identity">
          <h1 class="h1-brand">{{ props.clinicPrimary }}</h1>

          <h2 class="h2-sub-brand">
            {{ props.clinicSecondary }}
          </h2>

          <h3
            v-if="props.clinicTertiary"
            class="h3-tagline"
          >
            {{ props.clinicTertiary }}
          </h3>
        </div>

        <div class="contact-block">

          <div class="contact-row">
            <span>{{ props.address }}</span>

            <Icon
              icon="mdi:map-marker-outline"
              class="icon-small"
            />
          </div>

          <div class="contact-row">
            <span>{{ props.phone }}</span>

            <Icon
              icon="mdi:phone-outline"
              class="icon-small"
            />
          </div>

        </div>
      </header>

      <!-- PATIENT -->
      <section class="patient-section">

        <div class="field-box">
          <label>{{ t('prescriptionTemplate.patientName') }}</label>

          <div class="field-value">
            {{ props.patientName }}
          </div>
        </div>

        <div class="field-box">
          <label>{{ t('prescriptionTemplate.date') }}</label>

          <div class="field-value">
            {{ props.date }}
          </div>
        </div>

      </section>

      <!-- MAIN -->
      <main class="prescription-main">

        <div class="rx-indicator">
          <Icon icon="mdi:prescription" />
        </div>

        <div class="medication-area">

          <div
            v-for="(med, i) in props.medications"
            :key="i"
            class="med-item"
          >
            <div class="name">
              {{ med.name }}
            </div>

            <div class="dosage">
              {{ med.dosage }}
            </div>
          </div>

          <!-- FALLBACK -->
          <template v-if="props.medications.length === 0">

            <div class="med-item">
              <div class="name">
                {{ t('prescriptionTemplate.defaultMedication1.name') }}
              </div>

              <div class="dosage">
                {{ t('prescriptionTemplate.defaultMedication1.dosage') }}
              </div>
            </div>

            <div class="med-item">
              <div class="name">
                {{ t('prescriptionTemplate.defaultMedication2.name') }}
              </div>

              <div class="dosage">
                {{ t('prescriptionTemplate.defaultMedication2.dosage') }}
              </div>
            </div>

          </template>

        </div>
      </main>

      <!-- FOOTER -->
      <footer class="prescription-footer">

        <div class="legal-notice">
          {{ t('prescriptionTemplate.legalNotice') }}
        </div>

        <div class="signature-column">
          <div class="signature-line"></div>

          <div class="signature-label">
            {{ t('prescriptionTemplate.signatureLabel') }}
          </div>
        </div>

      </footer>

    </article>
  </div>
</template>

<style scoped>

/* =========================
   BASE
========================= */

* {
  box-sizing: border-box;
}

.print-preview-wrapper {
  background: #ececec;
  min-height: 100vh;
  padding: 24px;
  display: flex;
  justify-content: center;
  align-items: flex-start;
}

.paper-sheet {
  background: white;
  color: black;
  font-family: 'Inter', sans-serif;

  display: flex;
  flex-direction: column;

  overflow: hidden;

  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

/* =========================
   PAPER SIZES
========================= */

.size-a4 {
  width: 210mm;
  min-height: 297mm;
  padding: 16mm;
}

.size-a5 {
  width: 148mm;
  min-height: 210mm;
  padding: 10mm;
}

/* =========================
   TYPOGRAPHY
========================= */

.h1-brand {
  font-size: 42px;
  font-weight: 900;
  margin: 0;
  line-height: 0.9;
  letter-spacing: -2px;
}

.h2-sub-brand {
  font-size: 18px;
  font-weight: 800;
  margin: 4px 0 0;
  text-transform: uppercase;
}

.h3-tagline {
  font-size: 13px;
  color: #555;
  margin-top: 4px;
  font-weight: 400;
}

/* A5 smaller typography */

.size-a5 .h1-brand {
  font-size: 28px;
}

.size-a5 .h2-sub-brand {
  font-size: 13px;
}

.size-a5 .h3-tagline {
  font-size: 10px;
}

/* =========================
   HEADER
========================= */

.prescription-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;

  border-bottom: 3px solid black;

  padding-bottom: 14px;
  margin-bottom: 28px;

  gap: 20px;
}

.contact-block {
  text-align: right;
  max-width: 40%;
}

.contact-row {
  display: flex;
  align-items: center;
  justify-content: flex-end;

  gap: 6px;

  margin-bottom: 5px;

  font-size: 13px;
}

.icon-small {
  font-size: 14px;
  flex-shrink: 0;
}

/* =========================
   PATIENT
========================= */

.patient-section {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 24px;

  margin-bottom: 32px;
}

.field-box {
  border-bottom: 1.5px solid black;
  padding-bottom: 6px;
}

.field-box label {
  display: block;

  font-size: 10px;
  font-weight: 800;
  text-transform: uppercase;

  margin-bottom: 4px;
}

.field-value {
  font-size: 16px;
}

/* =========================
   MAIN
========================= */

.prescription-main {
  display: flex;
  align-items: flex-start;

  gap: 18px;

  flex: 1;
}

.rx-indicator {
  font-size: 58px;
  line-height: 1;
  flex-shrink: 0;
}

.medication-area {
  width: 100%;
}

.med-item {
  margin-bottom: 22px;
}

.med-item .name {
  font-size: 17px;
  font-weight: 800;
  line-height: 1.3;
}

.med-item .dosage {
  margin-top: 5px;

  font-size: 14px;
  line-height: 1.5;

  color: #333;
  font-style: italic;
}

/* A5 smaller content */

.size-a5 .rx-indicator {
  font-size: 42px;
}

.size-a5 .med-item .name {
  font-size: 13px;
}

.size-a5 .med-item .dosage {
  font-size: 11px;
}

/* =========================
   FOOTER
========================= */

.prescription-footer {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;

  margin-top: 36px;

  gap: 20px;
}

.legal-notice {
  font-size: 10px;
  line-height: 1.5;

  max-width: 55%;

  color: #666;
}

.signature-line {
  width: 180px;
  border-top: 1.5px solid black;
  margin-bottom: 6px;
}

.signature-label {
  text-align: center;
  font-size: 11px;
  font-weight: 700;
}

/* =========================
   PRINT
========================= */

@media print {

  html,
  body {
    margin: 0;
    padding: 0;
    background: white;
  }

  .print-preview-wrapper {
    padding: 0;
    margin: 0;
    background: white;
    min-height: auto;
  }

  .paper-sheet {
    margin: 0;
    box-shadow: none;

    width: 100%;
    min-height: auto;

    overflow: hidden;

    page-break-after: avoid;
    page-break-inside: avoid;

    break-inside: avoid;

    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }

  .size-a4 {
    width: 210mm;
    min-height: 297mm;

  }

  .size-a5 {
    width: 148mm;
    min-height: 210mm;
  }

  @page {
    margin: 0;
  }

}

/* =========================
   MOBILE
========================= */

@media (max-width: 768px) {

  .print-preview-wrapper {
    padding: 10px;
  }

  .paper-sheet {
    width: 100% !important;
    min-height: auto !important;
  }

}

</style>
