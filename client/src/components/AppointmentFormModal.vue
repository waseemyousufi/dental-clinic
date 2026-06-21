<template>
  <n-modal v-model:show="visible" class="appointment-add-edit-modal" transform-origin="center" :mask-closable="true">
    <n-card :title="isEditMode ? t('appointmentView.addEditModal.editTitle') : t('appointmentView.addEditModal.newTitle')" class="appointment-form-card" bordered
      size="medium" style="max-width: 600px;" role="dialog" aria-modal="true" closable @close="visible = false">
      <n-form ref="formRef" :model="formModel" :rules="validationRules" class="appointment-form" size="small" :show-require-mark="false">
        <div class="appointment-form__rows">
          <div class="appointment-form__pair">
            <n-form-item :label="t('appointmentView.addEditModal.form.patientLabel')" path="patientId" class="appointment-form__field">
              <n-select :to="false" v-model:value="formModel.patientId" :options="patientOptions" :disabled="lockPatient"
                :placeholder="t('appointmentView.addEditModal.form.patientPlaceholder')" filterable size="small"  />
            </n-form-item>

            <n-form-item :label="t('appointmentView.addEditModal.form.employeeLabel')" path="employeeId" class="appointment-form__field">
              <n-select :to="false" v-model:value="formModel.employeeId" :options="employeeOptions" :placeholder="t('appointmentView.addEditModal.form.employeePlaceholder')"
                filterable size="small" />
            </n-form-item>
          </div>

          <div class="appointment-form__pair">
            <n-form-item :label="t('appointmentView.addEditModal.form.datetimeLabel')" path="appointment_timestamp" class="appointment-form__field">
              <n-date-picker v-model:value="formModel.appointment_timestamp" type="datetime" clearable size="small"
                style="width: 100%" :to="false" />
            </n-form-item>

            <n-form-item :label="t('appointmentView.addEditModal.form.statusLabel')" path="status" class="appointment-form__field">
              <n-select
                :to="false"
                v-model:value="formModel.status"
                :options="statusOptions"
                :placeholder="t('appointmentView.addEditModal.form.statusPlaceholder')"
                :disabled="!isEditMode"
                size="small"
              />
            </n-form-item>
          </div>

          <div class="appointment-form__pair">
            <n-form-item :label="t('appointmentView.addEditModal.form.treatmentPlanLabel')" path="treatment_plan_id" class="appointment-form__field">
              <n-select :to="false" v-model:value="formModel.treatment_plan_id" :options="treatmentPlanOptions"
                :placeholder="t('appointmentView.addEditModal.form.treatmentPlanPlaceholder')" filterable clearable size="small" />
            </n-form-item>

            <n-form-item :label="t('appointmentView.addEditModal.form.procedureLabel')" path="procedure_id" class="appointment-form__field">
              <n-select :to="false" v-model:value="formModel.procedure_id" :options="procedureOptions"
                :placeholder="t('appointmentView.addEditModal.form.procedurePlaceholder')" filterable clearable :disabled="Boolean(props.appointment?.id)"
                size="small" />
            </n-form-item>
          </div>

          <div class="appointment-form__pair">
            <n-form-item :label="t('appointmentView.addEditModal.form.appointmentCostLabel')" path="appointment_cost" class="appointment-form__field">
              <n-input-number v-model:value="formModel.appointment_cost" :min="0" size="small"
                :disabled="Boolean(props.appointment?.id) || hasTreatmentPlan" style="width: 100%" />
            </n-form-item>

            <n-form-item v-if="isDoctorUsing" :label="t('appointmentView.addEditModal.form.clinicalNotesLabel')" path="clinical_notes"
              class="appointment-form__field">
              <n-input v-model:value="formModel.clinical_notes" type="textarea" :placeholder="t('appointmentView.addEditModal.form.detailsPlaceholder')" size="small"
                :autosize="{ minRows: 2, maxRows: 6 }" />
            </n-form-item>
          </div>

          <n-form-item v-if="!isDoctorUsing" :label="t('appointmentView.addEditModal.form.descriptionLabel')" path="description"
            class="appointment-form__field appointment-form__field--full">
            <n-input v-model:value="formModel.description" type="textarea" :placeholder="t('appointmentView.addEditModal.form.detailsPlaceholder')" size="small"
              :autosize="{ minRows: 2, maxRows: 6 }" />
          </n-form-item>
        </div>
      </n-form>

      <template #footer>
        <n-space justify="end" size="small">
          <n-button size="small" @click="visible = false">
            {{ t('common.cancelButtonText') }}
          </n-button>

          <n-button size="small" type="primary" :loading="loading" @click="submit">
            {{ isEditMode ? t('common.updateButtonText') : t('common.saveButtonText') }}
          </n-button>
        </n-space>
      </template>
    </n-card>
  </n-modal>
  {{ console.log('formmodel: ', formModel.value) }}
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  NModal,
  NCard,
  NForm,
  NFormItem,
  NSelect,
  NDatePicker,
  NInput,
  NButton,
  NSpace,
  useMessage,
  NInputNumber,
} from 'naive-ui'

import employeeApi from '@api/employee'
import patientApi from '@api/patient'
import procedureApi from '@api/procedure'
import treatmentPlanApi from '@api/treatmentPlan'

import type AppointmentData from '@api/interfaces/Appointment'
import type EmployeeData from '@api/interfaces/Employee'
import type PatientData from '@api/interfaces/patient'
import procedure from '@api/procedure'
import { BASE_OPTION_DEFAULTS } from '@fullcalendar/core/internal'
import type patient from '@api/patient'

type EmployeeAbbr = {
  id: number
  name?: string
  fName?: string
  lName?: string
}

type PatientAbbr = {
  id: number
  name?: string
  fName?: string
  lName?: string
}

type SelectOption = {
  label: string
  value: number
}

type ProcedureRecord = {
  id: number
  name?: string
  cost?: number | string | null
  price?: number | string | null
  appointment_cost?: number | string | null
}

type TreatmentPlanRecord = {
  id: number
  name?: string
  cost?: number | string | null
  price?: number | string | null
  appointment_cost?: number | string | null
}

const props = withDefaults(defineProps<{
  show: boolean
  appointment?: Partial<AppointmentData> | null
  patientId?: number | null
  treatmentPlanId?: number | null
  lockPatient?: boolean
  loading?: boolean
  isDoctorUsing: boolean
  procedureId?: number | null
}>(), {
  appointment: null,
  patientId: null,
  treatmentPlanId: null,
  lockPatient: false,
  loading: false,
  isDoctorUsing: false,
  procedureId: null,
})

const emit = defineEmits<{
  (e: 'update:show', value: boolean): void
  (e: 'save', payload: AppointmentData): void
}>()

const message = useMessage()
const { t } = useI18n()

const formRef = ref<any>(null)
const validationRules = {
  patientId: [
    { required: true, message: t('appointmentView.addEditModal.validation.patientRequired') || 'Patient is required', trigger: 'blur' },
  ],
  employeeId: [
    { required: true, message: t('appointmentView.addEditModal.validation.employeeRequired') || 'Employee is required', trigger: 'blur' },
  ],
  appointment_timestamp: [
    { required: true, message: t('appointmentView.addEditModal.validation.dateRequired') || 'Date and time is required', trigger: 'blur' },
  ],
  treatment_plan_id: [
    { required: true, message: t('appointmentView.addEditModal.validation.treatmentPlanRequired') || 'Treatment plan is required', trigger: 'blur' },
  ],
  procedure_id: [
    { required: true, message: t('appointmentView.addEditModal.validation.procedureRequired') || 'Procedure is required', trigger: 'blur' },
  ],
  appointment_cost: [
    { required: true, message: t('appointmentView.addEditModal.validation.costRequired') || 'Appointment cost is required', trigger: 'blur' },
  ],
}

const employeeOptions = ref<SelectOption[]>([])
const patientOptions = ref<SelectOption[]>([])
const treatmentPlanOptions = ref<SelectOption[]>([])
const procedureOptions = ref<SelectOption[]>([])

const procedureRecords = ref<ProcedureRecord[]>([])
const treatmentPlanRecords = ref<TreatmentPlanRecord[]>([])

const statusOptions = computed(() => [
  { label: t('appointmentView.statusOptions.pending'), value: 'pending' },
  { label: t('appointmentView.statusOptions.confirmed'), value: 'confirmed' },
  { label: t('appointmentView.statusOptions.completed'), value: 'completed' },
  { label: t('appointmentView.statusOptions.cancelled'), value: 'cancelled' },
  { label: t('appointmentView.statusOptions.noShow'), value: 'no_show' },
])

const visible = computed({
  get: () => props.show,
  set: (value: boolean) => emit('update:show', value),
})

const isEditMode = computed(() => !!props.appointment?.id)

const formModel = ref({
  id: undefined as number | undefined,
  description: '',
  appointment_timestamp: null as number | null,
  status: 'pending',
  employeeId: undefined as number | undefined,
  patientId: undefined as number | undefined,
  treatment_plan_id: null as number | null,
  procedure_id: null as number | null,
  appointment_cost: 0,
  clinical_notes: '',
})

const formatName = (obj: EmployeeAbbr | PatientAbbr) => {
  if (obj.name) return obj.name
  return '#'+obj.id + ` - ${obj.fName || ''} ${obj.lName || ''}`.trim()  || t('appointmentView.fallbackName')
}

function normalizeId(value: unknown): number | null {
  if (value === null || value === undefined || value === '') return null
  const n = Number(value)
  return Number.isNaN(n) ? null : n
}

function normalizeNumber(value: unknown): number | null {
  if (value === null || value === undefined || value === '') return null
  const n = Number(value)
  return Number.isNaN(n) ? null : n
}

function toTimestamp(value: string | number | null | undefined): number | null {
  if (!value) return null

  const d = new Date(value)
  return Number.isNaN(d.getTime()) ? null : d.getTime()
}

function toSqlDateTime(value: number): string {
  const date = new Date(value)

  const Y = date.getFullYear()
  const M = String(date.getMonth() + 1).padStart(2, '0')
  const D = String(date.getDate()).padStart(2, '0')
  const h = String(date.getHours()).padStart(2, '0')
  const m = String(date.getMinutes()).padStart(2, '0')

  return `${Y}/${M}/${D} ${h}:${m}`
}

function getRecordCost(
  records: Array<{ id: number; cost?: number | string | null; price?: number | string | null; appointment_cost?: number | string | null }>,
  id: number | null,
): number | null {
  if (id == null) return null
  const record = records.find(item => item.id === id)
  if (!record) return null

  console.log('getRecordCost', { id, record })
  return (
    normalizeNumber(record.base_price) ??
    normalizeNumber(record.appointment_cost) ??
    normalizeNumber(record.cost) ??
    normalizeNumber(record.price)
  )
}

const hasTreatmentPlan = computed(() => {
  return formModel.value.treatment_plan_id != null
})

function resetForm() {
  const appt = props.appointment

  const existingProcedureId = normalizeId(appt?.procedureId ?? (appt as any)?.procedure_id)
  const existingTreatmentPlanId = normalizeId(appt?.treatmentPlanId ?? (appt as any)?.treatment_plan_id)

  const selectedProcedureId =
    normalizeId(props.procedureId ?? existingProcedureId) ??
    normalizeId(procedureOptions.value[0]?.value) ??
    null

  const selectedTreatmentPlanId =
    normalizeId(props.treatmentPlanId ?? existingTreatmentPlanId) ?? null

  const defaultCostFromAppointment =
    normalizeNumber((appt as any)?.appointmentCost ?? (appt as any)?.appointment_cost)

  const defaultCostFromProcedure =
    getRecordCost(procedureRecords.value, selectedProcedureId)

  const defaultCostFromTreatmentPlan =
    getRecordCost(treatmentPlanRecords.value, selectedTreatmentPlanId)

  formModel.value = {
    id: appt?.id,

    description: appt?.description || '',

    appointment_timestamp:
      toTimestamp(appt?.appointment_timestamp) ?? Date.now(),

    status: isEditMode.value ? (appt?.status || 'pending') : 'pending',

    employeeId: normalizeId(appt?.employeeId),

    patientId: normalizeId(props.patientId ?? appt?.patientId),

    treatment_plan_id: selectedTreatmentPlanId,

    procedure_id: selectedProcedureId,

    appointment_cost:
      defaultCostFromAppointment ??
      defaultCostFromProcedure ??
      defaultCostFromTreatmentPlan ??
      0,

    clinical_notes:
      (appt as any)?.clinicalNotes ??
      (appt as any)?.clinical_notes ??
      '',
  }
}



async function loadOptions() {
  try {
    const [
      empRes,
      patRes,
      procedureRes,
      treatmentPlanRes,
    ] = await Promise.all([
      employeeApi.getBranchEmployees(true),
      patientApi.getBranchPatients(true),
      procedureApi.getProcedures({ includeInactive: true, longTermOnly: false, shortTermOnly: true }),
      treatmentPlanApi.getBranchTreatmentPlans(undefined, true),
    ])

    console.log('treatmentPlanRes', treatmentPlanRes)

    const rawEmps = empRes.data?.data || empRes.data
    console.log('rawEmps', rawEmps)
    employeeOptions.value = (
      Array.isArray(rawEmps)
        ? rawEmps
        : rawEmps?.employees || []
    ).filter(emp => emp.position === 'doctor').map((emp: EmployeeData) => ({
      label: formatName(emp as EmployeeAbbr),
      value: normalizeId(emp.id) ?? 0,
    }))

    const rawPats = patRes.data?.data || patRes.data

    patientOptions.value = (
      Array.isArray(rawPats)
        ? rawPats
        : rawPats?.patients || []
    ).map((pat: PatientData) => ({
      label: formatName(pat as PatientAbbr),
      value: normalizeId(pat.id) ?? 0,
    }))

    const rawProcedures = procedureRes.data?.data.filter((p: any) => p.id) || procedureRes.data
    const procedureList = (
      Array.isArray(rawProcedures)
        ? rawProcedures
        : rawProcedures?.procedures || []
    ) as any[]

    procedureRecords.value = procedureList
      .map((procedure: any) => ({
        id: normalizeId(procedure.id) ?? 0,
        name: procedure.name,
        cost: procedure.cost,
        price: procedure.price,
        base_price: procedure.base_price,
        appointment_cost: procedure.appointment_cost,
      }))
      .filter(item => item.id !== 0 || item.name)

    procedureOptions.value = procedureRecords.value.map((procedure) => ({
      label: procedure.name || t('appointmentView.fallbackName'),
      value: procedure.id,
    }))

    const rawTreatmentPlans = treatmentPlanRes.data?.data || treatmentPlanRes.data
    const treatmentPlanList = (
      Array.isArray(rawTreatmentPlans)
        ? rawTreatmentPlans
        : rawTreatmentPlans?.treatmentPlans || []
    ) as any[]

    treatmentPlanRecords.value = treatmentPlanList
      .map((plan: any) => ({
        id: normalizeId(plan.id) ?? 0,
        name: plan.name,
        cost: plan.cost,
        price: plan.price,
        procedure_id: normalizeId(plan.procedure_id),
        patient_id: normalizeId(plan.patient_id),
        appointment_cost: plan.appointment_cost,
        procedure_name: plan.procedure_name,
      }))
      .filter(item => item.id !== 0 || item.name)

      console.log('treatmentPlanRecords', treatmentPlanRecords.value)

    treatmentPlanOptions.value = treatmentPlanRecords.value.map((plan) => ({
      label: plan.procedure_name || t('appointmentView.fallbackName'),
      value: plan.id,
    }))
  } catch (error) {
    console.log(error)
    message.error(t('appointmentView.messages.loadOptionsError'))
  }
}

function submit() {
  if (!formRef.value) return

  formRef.value.validate((errors: any) => {
    if (errors) {
      message.warning(t('appointmentView.addEditModal.validation.requiredFields') || 'Please fill in all required fields')
      return
    }

    emit('save', {
      id: formModel.value.id,

      description: formModel.value.description,

      appointment_timestamp: toSqlDateTime(formModel.value.appointment_timestamp),

      status: formModel.value.status,

      employeeId: Number(formModel.value.employeeId),

      patientId: Number(formModel.value.patientId),

      treatment_plan_id: formModel.value.treatment_plan_id,

      procedure_id: formModel.value.procedure_id,

      appointment_cost: Number(formModel.value.appointment_cost ?? 0),

      clinical_notes: formModel.value.clinical_notes,
    } as AppointmentData)
  })
}

watch(
  () => [
    props.show,
    props.appointment,
    props.patientId,
    props.treatmentPlanId,
    props.procedureId,
  ],
  () => {
    if (props.show) resetForm()
  },
  { immediate: true },
)

onMounted(async () => {
  await loadOptions()
  if (props.show) {
    resetForm()
  }
})

watch(
  () => [
    formModel.value.procedure_id,
    formModel.value.treatment_plan_id,
  ],
  ([procedureId, treatmentPlanId]) => {
    // If treatment plan selected → force cost to 0
    if (treatmentPlanId != null) {
      formModel.value.appointment_cost = 0
      return
    }

    // Otherwise use procedure cost
    const procedureCost = getRecordCost(
      procedureRecords.value,
      procedureId
    )

    formModel.value.appointment_cost =
      procedureCost ?? 0
  },
  { immediate: true }
)

watch(
  () => formModel.value.patientId,
  (patientId) => {
    if (!patientId) {
      treatmentPlanOptions.value = []
      return
    }

    treatmentPlanOptions.value = treatmentPlanRecords.value
      .filter(plan => plan.patient_id === patientId)
      .map((plan) => ({
        label: plan.procedure_name || t('appointmentView.fallbackName'),
        value: plan.id,
      }))

    // clear selected plan if it doesn't belong to patient
    if (
      formModel.value.treatment_plan_id &&
      !treatmentPlanRecords.value.some(
        plan =>
          plan.id === formModel.value.treatment_plan_id &&
          plan.patient_id === patientId
      )
    ) {
      formModel.value.treatment_plan_id = null
    }
  },
  { immediate: true }
)
</script>

<style scoped>
.appointment-add-edit-modal {
  :deep(.n-modal-body-wrapper) {
    padding: 6px 8px;
  }

  :deep(.appointment-form-card.n-card) {
    width: 100%;
    max-width: min(calc(100vw - 20px), 380px);
    box-sizing: border-box;
  }

  @media (min-width: 420px) {
    :deep(.appointment-form-card.n-card) {
      max-width: min(calc(100vw - 24px), 420px);
    }
  }

  @media (min-width: 520px) {
    :deep(.appointment-form-card.n-card) {
      max-width: min(calc(100vw - 28px), 460px);
    }
  }

  :deep(.appointment-form-card .n-card-header) {
    padding: 10px 12px 6px;
  }

  :deep(.appointment-form-card .n-card-content) {
    padding: 0 12px 12px;
  }

  :deep(.appointment-form-card .n-card__footer) {
    padding: 8px 12px 12px;
  }

  :deep(.appointment-form .n-form-item-feedback-wrapper) {
    min-height: 0;
  }

  .appointment-form__rows {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  .appointment-form__pair {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px 12px;
    align-items: start;
  }

  @media (max-width: 479px) {
    .appointment-form__pair {
      grid-template-columns: 1fr;
    }
  }

  :deep(.appointment-form__field.n-form-item) {
    margin-bottom: 0;
  }

  :deep(.appointment-form__field .n-form-item-label) {
    padding-bottom: 6px;
    font-size: 12px;
  }

  .appointment-form__field--full {
    width: 100%;
  }
}
</style>
