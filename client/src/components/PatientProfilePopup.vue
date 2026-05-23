<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, watchEffect } from 'vue';
import { useI18n } from 'vue-i18n';
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
import treatmentPlanApi from '@api/treatmentPlan';
import settingsApi from '@api/settings';
import useUserStore from '@/stores/user';
import AppointmentFormModal from './AppointmentFormModal.vue';
import BillTemplate from './BillTemplate.vue';

type PatientProfileData = PatientData & {
  totalAmountDue?: number | null;
};

type BillTreatmentItem = {
  service: string;
  description?: string;
  cost: number;
};

type BillAppointmentItem = {
  date: string;
  time: string;
  procedure: string;
};

type BillPrintPayload = {
  clinicPrimary: string;
  clinicSecondary: string;
  clinicTertiary?: string;
  address: string;
  phone: string;
  currency: string;
  patientName: string;
  invoiceDate: string;
  invoiceNumber: string;
  treatmentPlan: BillTreatmentItem[];
  appointments: BillAppointmentItem[];
  amountPaid: number;
  totalAmount: number;
  balanceAmount: number;
  notes: string;
};

const props = defineProps<{
  show: boolean;
  patientData: PatientProfileData | null;
}>();

const emit = defineEmits<{
  (e: 'update:show', value: boolean): void;
}>();

const message = useMessage();
const { t } = useI18n();
const userStore = useUserStore();

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
const billPreparing = ref(false);
const billPrintData = ref<BillPrintPayload | null>(null);
let billCleanupTimer: ReturnType<typeof setTimeout> | null = null;

const patientFullName = computed(() => {
  if (!props.patientData) return t('common.noDataAvailable');

  return `${props.patientData.fName ?? ''} ${props.patientData.lName ?? ''}`.trim() || t('common.noDataAvailable');
});

const genderLabel = computed(() => {
  const gender = props.patientData?.gender;

  if (!gender) return t('common.noDataAvailable');

  return gender.charAt(0).toUpperCase() + gender.slice(1);
});

const allergiesLabel = computed(() => {
  const allergies = props.patientData?.allergies;

  return allergies && allergies.length > 0
    ? allergies.join(', ')
    : t('patientView.profilePopup.format.none');
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
  return t('patientView.profilePopup.format.noDebit');
}

function formatDisplayDate(dateValue: string | null | undefined) {
  if (!dateValue) return t('common.noDataAvailable');

  const date = new Date(dateValue);
  if (Number.isNaN(date.getTime())) return 'N/A';

  return date.toLocaleDateString();
}

function formatDisplayTime(dateValue: string | null | undefined) {
  if (!dateValue) return t('common.noDataAvailable');

  const date = new Date(dateValue);
  if (Number.isNaN(date.getTime())) return 'N/A';

  return date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
}

function normalizeResponse<T>(response: any): T {
  return (response?.data?.data ?? response?.data ?? []) as T;
}

function cleanupPrintedBill() {
  billPrintData.value = null;

  if (billCleanupTimer) {
    clearTimeout(billCleanupTimer);
    billCleanupTimer = null;
  }
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

async function handlePrintBill() {
  if (!props.patientData) return;

  try {
    billPreparing.value = true;

    const [settingsResponse, treatmentPlansResponse, appointmentsResponse] = await Promise.all([
      settingsApi.getSettings(),
      treatmentPlanApi.getBranchTreatmentPlans(props.patientData.id),
      appointmentApi.getBranchAppointments(),
    ]);

    const settings = normalizeResponse<any>(settingsResponse) ?? {};
    const treatmentPlans = normalizeResponse<any[]>(treatmentPlansResponse);
    const allAppointments = normalizeResponse<any[]>(appointmentsResponse);

    const patientAppointments = allAppointments.filter(
      appointment => Number(appointment.patientId) === Number(props.patientData?.id),
    );

    const treatmentPlanItems: BillTreatmentItem[] = treatmentPlans.map((plan) => {
      const status = plan?.status
        ? String(plan.status).replace(/_/g, ' ')
        : null;
      const startDate = formatDisplayDate(plan?.start_date);
      const descriptionParts = [
        status ? `Status: ${status}` : null,
        plan?.duration ? `Duration: ${plan.duration}` : null,
        plan?.start_date ? `Start: ${startDate}` : null,
      ].filter(Boolean);

      return {
        service: plan?.procedure?.name || `Treatment Plan #${plan?.id ?? ''}`.trim(),
        description: descriptionParts.length ? descriptionParts.join(' • ') : undefined,
        cost: Number(plan?.total_estimated_cost || 0),
      };
    });

    const appointmentItems: BillAppointmentItem[] = patientAppointments.map(appointment => ({
      date: formatDisplayDate(appointment?.appointment_timestamp),
      time: formatDisplayTime(appointment?.appointment_timestamp),
      procedure: appointment?.description || appointment?.status || t('patientView.profilePopup.billTemplate.appointmentFallback'),
    }));

    const totalAmount = treatmentPlanItems.reduce((sum, item) => sum + Number(item.cost || 0), 0);
    const balanceAmount = Math.max(0, Number(amountDue.value || props.patientData.totalAmountDue || 0));
    const amountPaid = Math.max(0, totalAmount - balanceAmount);
    const today = new Date();

    billPrintData.value = {
      clinicPrimary: userStore.clinicName || settings?.clinic_name || t('patientView.profilePopup.billTemplate.clinicPrimaryFallback'),
      clinicSecondary: t('patientView.profilePopup.billTemplate.clinicSecondary'),
      clinicTertiary: settings?.currency ? t('patientView.profilePopup.billTemplate.currencyLabel', { currency: settings.currency }) : undefined,
      address: settings?.address || t('patientView.profilePopup.billTemplate.addressUnavailable'),
      phone: userStore.clinicPhone || settings?.phone || t('patientView.profilePopup.billTemplate.phoneUnavailable'),
      currency: settings?.currency || 'AFN',
      patientName: patientFullName.value,
      invoiceDate: today.toLocaleDateString(),
      invoiceNumber: `${props.patientData.id}-${today.getTime().toString().slice(-5)}`,
      treatmentPlan: treatmentPlanItems,
      appointments: appointmentItems,
      amountPaid,
      totalAmount,
      balanceAmount,
      notes: t('patientView.profilePopup.billTemplate.notes', { balance: formatCurrency(balanceAmount) }),
    };

    await nextTick();

    window.addEventListener('afterprint', cleanupPrintedBill, { once: true });
    billCleanupTimer = setTimeout(cleanupPrintedBill, 1500);
    window.print();
  }
  catch (error) {
    console.error('Error printing patient bill:', error);
    cleanupPrintedBill();
    message.error(t('patientView.profilePopup.messages.prepareBillError'));
  }
  finally {
    billPreparing.value = false;
  }
}

async function handleAppointmentSave(payload: AppointmentData) {
  try {
    appointmentSaving.value = true;
    await appointmentApi.postAppointment(payload);
    message.success(t('patientView.profilePopup.messages.appointmentCreatedSuccess'));
    showAppointmentModal.value = false;
  }
  catch (error) {
    console.error('Error creating appointment:', error);
    message.error(t('patientView.profilePopup.messages.createAppointmentError'));
  }
  finally {
    appointmentSaving.value = false;
  }
}

async function handleChargePatient() {
  if (
    !canCharge.value ||
    !props.patientData ||
    chargeAmount.value == null ||
    Number(chargeAmount.value) <= 0
  ) {
    if (chargeAmount.value !== null && Number(chargeAmount.value) <= 0) {
      message.warning(t('patientView.profilePopup.messages.chargePatientError'))
    }
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
    message.error(t('patientView.profilePopup.messages.chargePatientError'));
  } finally {
    isCharging.value = false;
  }
}

onBeforeUnmount(() => {
  cleanupPrintedBill();
});
</script>

<template>
  <n-modal v-model:show="showModal" closable :mask-closable="true" class="patient-profile-modal" content-scrollable>
    <n-card class="profile-card" :title="t('patientView.profilePopup.title')" :bordered="false" size="huge" style="
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
          {{ t('patientView.profilePopup.addAppointmentTooltip') }}
        </n-tooltip>

        <n-tooltip trigger="hover">
          <template #trigger>
            <n-button quaternary circle type="primary" :loading="billPreparing" @click="handlePrintBill">
              <template #icon>
                <Icon icon="solar:printer-bold-duotone" />
              </template>
            </n-button>
          </template>

          {{ t('patientView.profilePopup.printBillTooltip') }}
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
                {{ t('patientView.profilePopup.patientTag') }}
              </n-tag>
            </div>

            <p class="subtle">
              {{ t('patientView.profilePopup.subtitle') }}
            </p>
          </div>

          <div class="amount-badge">
            <span>{{ t('patientView.profilePopup.totalAmountDueLabel') }}</span>
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
                {{ t('patientView.profilePopup.contactNumberLabel') }}
              </n-space>
            </template>

            <p class="indented">
              {{ patientData.phone || t('common.noDataAvailable') }}
            </p>
          </n-descriptions-item>

          <n-descriptions-item>
            <template #label>
              <n-space align="center" size="small">
                <Icon :size="16" icon="streamline-ultimate:gender-hetero" />
                {{ t('patientView.profilePopup.genderLabel') }}
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
              <h3>{{ t('patientView.profilePopup.chargeCard.title') }}</h3>

              <p>
                {{ t('patientView.profilePopup.chargeCard.description') }}
              </p>
            </div>

            <n-tag round size="small" type="warning">
              {{ t('patientView.profilePopup.chargeCard.dueLabel') }}
              {{ formatCurrency(amountDue) }}
            </n-tag>
          </div>

          <div class="charge-card__actions">
            <n-input-number :value="chargeAmount" @update:value="handleChargeInput" class="charge-input"
              :step="50" :placeholder="t('patientView.profilePopup.chargeCard.amountPlaceholder')" clearable>
              <template #suffix>
                AFN
              </template>
            </n-input-number>

            <n-button class="charge-button" type="primary" size="large" :disabled="!canCharge" :loading="isCharging"
              @click="handleChargePatient">
              <template #icon>
                <Icon icon="solar:wallet-money-bold-duotone" />
              </template>

              {{ t('patientView.profilePopup.chargeCard.chargeButton') }}
            </n-button>
          </div>
        </n-card>
      </div>

      <div v-else class="empty-state">
        {{ t('patientView.profilePopup.emptyState') }}
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

  <div v-if="billPrintData" class="bill-print-host">
    <BillTemplate
      :clinic-primary="billPrintData.clinicPrimary"
      :clinic-secondary="billPrintData.clinicSecondary"
      :clinic-tertiary="billPrintData.currency ? billPrintData.clinicTertiary : ''"
      :address="billPrintData.address"
      :phone="billPrintData.phone"
      :currency="billPrintData.currency"
      :patient-name="billPrintData.patientName"
      :invoice-date="billPrintData.invoiceDate"
      :invoice-number="billPrintData.invoiceNumber"
      :treatment-plan="billPrintData.treatmentPlan"
      :appointments="billPrintData.appointments"
      :amount-paid="billPrintData.amountPaid"
      :total-amount="billPrintData.totalAmount"
      :balance-amount="billPrintData.balanceAmount"
      :notes="billPrintData.notes"
    />
  </div>
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

<style>
.bill-print-host {
  position: fixed;
  inset: 0;
  z-index: -1;
  opacity: 0;
  pointer-events: none;
  overflow: auto;
  background: #fff;
}

@media print {
  body * {
    visibility: hidden !important;
  }

  .bill-print-host,
  .bill-print-host * {
    visibility: visible !important;
  }

  .bill-print-host {
    position: static;
    z-index: auto;
    opacity: 1;
    pointer-events: auto;
    overflow: visible;
  }
}
</style>
