<script setup lang="ts">
import { computed, ref, watchEffect } from 'vue';
import {
  NModal,
  NCard,
  NDescriptions,
  NDescriptionsItem,
  NTag,
  NSpace,
  NButton,
  NInputNumber,
  NDivider,
  NTooltip,
  useMessage,
} from 'naive-ui';
import { Icon } from '@iconify/vue';
import type PatientData from '@api/interfaces/patient';
import patientApi from '@api/patient';
import appointmentApi from '@api/appointment';
import type AppointmentData from '@api/interfaces/Appointment';
import AppointmentFormModal from './AppointmentFormModal.vue';

const props = defineProps<{
  show: boolean;
  patientData: PatientData | null;
}>();

const emit = defineEmits<{
  (e: 'update:show', value: boolean): void;
}>();

const message = useMessage();

const showModal = computed({
  get: () => props.show,
  set: (value) => emit('update:show', value),
});

const chargeAmount = ref<number | null>(null);
const canCharge = ref(false);
const isCharging = ref(false);
const amountDue = ref(0);
const showAppointmentModal = ref(false);
const appointmentSaving = ref(false);


const patientFullName = computed(() => {
  if (!props.patientData) return 'N/A';

  return `${props.patientData.fName ?? ''} ${props.patientData.lName ?? ''}`.trim();
});

const genderLabel = computed(() => {
  const gender = props.patientData?.gender;

  if (!gender) return 'N/A';

  return gender.charAt(0).toUpperCase() + gender.slice(1);
});

const allergiesLabel = computed(() => {
  const allergies = props.patientData?.allergies;

  return allergies && allergies.length > 0
    ? allergies.join(', ')
    : 'None';
});

/**
 * Replace later with backend data.
 */

watchEffect(() => {
  amountDue.value = props.patientData?.totalAmountDue ?? 0;
});

function formatCurrency(value: number) {
  if (value)
    return `${value.toLocaleString()} AFN`;
  return 'No Debit';
}

/**
 * REAL-TIME INPUT VALIDATION
 */
function handleChargeInput(value: number | null) {
  chargeAmount.value = value;

  canCharge.value =
    value !== null &&
    !Number.isNaN(value) &&
    Number(value) > 0;
}

function handleAddAppointment() {
  if (!props.patientData) return;
  showAppointmentModal.value = true;
}

async function handleAppointmentSave(payload: AppointmentData) {
  try {
    appointmentSaving.value = true;
    await appointmentApi.postAppointment(payload);
    message.success('Appointment created successfully.');
    showAppointmentModal.value = false;
  }
  catch (error) {
    console.error('Error creating appointment:', error);
    message.error('Failed to create appointment.');
  }
  finally {
    appointmentSaving.value = false;
  }
}

async function handleChargePatient() {
  if (
    !canCharge.value ||
    !props.patientData ||
    chargeAmount.value == null
  ) {
    return;
  }

  try {
    isCharging.value = true;

    // TODO: backend logic
    console.log('Charge patient:', {
      patient: props.patientData,
      amount: chargeAmount.value,
    });

    await patientApi.setDebit(props.patientData.id, Number(chargeAmount.value))
    amountDue.value = amountDue.value - Number(chargeAmount.value)

    chargeAmount.value = null;
    canCharge.value = false;
  }
  catch (error) {
    console.error('Error charging patient:', error);
    message.error('Failed to charge patient. Amount is uncapable of being charged.');
  } finally {
    isCharging.value = false;
  }
}
</script>

<template>
  <n-modal v-model:show="showModal" closable :mask-closable="false" class="patient-profile-modal" content-scrollable>
    <n-card class="profile-card" title="Patient Profile" :bordered="false" size="huge" style="
        width: min(760px, calc(100vw - 20px));
        max-height: 90vh;
      ">
      <template #header-extra>
        <n-tooltip trigger="hover">
          <template #trigger>
            <n-button quaternary circle type="primary" @click="handleAddAppointment">
              <template #icon>
                <Icon icon="solar:calendar-add-bold-duotone" />
              </template>
            </n-button>
          </template>

          Add appointment
        </n-tooltip>

        <n-button style="margin-left: .4em;" quaternary circle @click="showModal = false">
          <template #icon>
            <Icon icon="material-symbols:close-rounded" />
          </template>
        </n-button>
      </template>

      <div v-if="patientData" class="profile-content">
        <div class="profile-header">
          <div class="header-details">
            <div class="name-row">
              <h2>{{ patientFullName }}</h2>

              <n-tag round size="small" type="info">
                Patient
              </n-tag>
            </div>

            <p class="subtle">
              Review patient details and billing.
            </p>
          </div>

          <div class="amount-badge">
            <span>Total Amount Due</span>
            <strong>
              {{ formatCurrency(amountDue) }}
            </strong>
          </div>
        </div>

        <n-divider />

        <n-descriptions :bordered="false" :column="2" class="profile-details">
          <n-descriptions-item>
            <template #label>
              <n-space align="center" size="small">
                <Icon :size="16" icon="solar:phone-broken" />
                Contact Number
              </n-space>
            </template>

            <p class="indented">
              {{ patientData.phone || 'N/A' }}
            </p>
          </n-descriptions-item>

          <n-descriptions-item>
            <template #label>
              <n-space align="center" size="small">
                <Icon :size="16" icon="streamline-ultimate:gender-hetero" />
                Gender
              </n-space>
            </template>

            <p class="indented">
              {{ genderLabel }}
            </p>
          </n-descriptions-item>

          <!-- <n-descriptions-item>
            <template #label>
              <n-space align="center" size="small">
                <Icon :size="16" icon="healthicons:blood-ab-p" />
                Blood Type
              </n-space>
            </template>

            <p class="indented">
              {{ patientData.bloodType || 'N/A' }}
            </p>
          </n-descriptions-item>

          <n-descriptions-item>
            <template #label>
              <n-space align="center" size="small">
                <Icon :size="16" icon="mdi:allergy-outline" />
                Allergies
              </n-space>
            </template>

            <p class="indented">
              {{ allergiesLabel }}
            </p>
          </n-descriptions-item> -->
        </n-descriptions>

        <n-divider />

        <n-card size="small" class="charge-card" :bordered="true">
          <div class="charge-card__header">
            <div>
              <h3>Charge patient</h3>

              <p>
                Enter an amount and submit the charge.
              </p>
            </div>

            <n-tag round size="small" type="warning">
              Due:
              {{ formatCurrency(amountDue) }}
            </n-tag>
          </div>

          <div class="charge-card__actions">
            <n-input-number :value="chargeAmount" @update:value="handleChargeInput" class="charge-input" :min="1"
              :step="50" placeholder="Enter amount" clearable>
              <template #suffix>
                AFN
              </template>
            </n-input-number>

            <n-button class="charge-button" type="primary" size="large" :disabled="!canCharge" :loading="isCharging"
              @click="handleChargePatient">
              <template #icon>
                <Icon icon="solar:wallet-money-bold-duotone" />
              </template>

              Charge Patient
            </n-button>
          </div>
        </n-card>
      </div>

      <div v-else class="empty-state">
        No patient data to display.
      </div>
    </n-card>
  </n-modal>

  <AppointmentFormModal
    v-model:show="showAppointmentModal"
    :patient-id="patientData?.id ?? null"
    :lock-patient="true"
    :loading="appointmentSaving"
    @save="handleAppointmentSave"
  />
</template>

<style scoped>
.patient-profile-modal :deep(.n-card__content) {
  padding-top: 0;
}

.profile-card {
  border-radius: 24px;
  /* overflow: visible; */
  box-shadow:
    0 24px 80px rgba(15, 23, 42, 0.18);
}

.profile-content {
  display: flex;
  flex-direction: column;
  gap: 8px;
  max-height: calc(90vh - 120px);
  overflow-y: auto;
  padding-top: 8px;
  overflow: visible;
}

.profile-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 18px;
}

.header-details {
  min-width: 0;
}

.name-row {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.name-row h2 {
  margin: 0;
  font-size: 1.7rem;
  line-height: 1.2;
  color: #1f2937;
}

.subtle {
  margin-top: 8px;
  color: #6b7280;
  font-size: 0.95rem;
}

.amount-badge {
  min-width: 180px;
  padding: 14px;
  border-radius: 18px;
  background:
    linear-gradient(180deg,
      #fff7ed 0%,
      #fffbeb 100%);
  border: 1px solid #fde68a;
  text-align: right;
}

.amount-badge span {
  display: block;
  margin-bottom: 4px;
  font-size: 0.8rem;
  color: #92400e;
}

.amount-badge strong {
  font-size: 1rem;
  color: #b91c1c;
}

.profile-details .iconify {
  font-size: 1.1rem;
}

.profile-details .indented {
  margin: 0;
  padding-left: 0.8em;
  color: #111827;
}

.profile-details :deep(.n-descriptions-item__label) {
  display: flex;
  align-items: center;
  gap: 5px;
  margin-bottom: 8px;
  color: #6b7280;
  font-weight: 600;
}

.profile-details :deep(.n-descriptions-item__content) {
  font-size: 0.98rem;
  color: #111827;
}

.charge-card {
  border-radius: 20px;
  box-shadow:
    0 10px 30px rgba(15, 23, 42, 0.06);
}

.charge-card__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 16px;
}

.charge-card__header h3 {
  margin: 0;
  font-size: 1.05rem;
  color: #111827;
}

.charge-card__header p {
  margin-top: 6px;
  color: #6b7280;
  font-size: 0.92rem;
}

.charge-card__actions {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 12px;
  align-items: center;
}

.charge-input {
  width: 100%;
}

.charge-button {
  min-width: 170px;
  transition:
    transform 0.18s ease,
    box-shadow 0.18s ease;
}

.charge-button:not(:disabled) {
  box-shadow:
    0 10px 24px rgba(24, 160, 88, 0.18);
}

.charge-button:not(:disabled):hover {
  transform: translateY(-1px);
}

.empty-state {
  padding: 24px 0;
  text-align: center;
  color: #6b7280;
}

@media (max-width: 640px) {

  .profile-header,
  .charge-card__header {
    flex-direction: column;
  }

  .amount-badge {
    width: 100%;
    text-align: left;
  }

  .charge-card__actions {
    grid-template-columns: 1fr;
  }

  .charge-button {
    width: 100%;
    min-width: unset;
  }

  .profile-details :deep(.n-descriptions) {
    grid-template-columns: 1fr !important;
  }
}
</style>
