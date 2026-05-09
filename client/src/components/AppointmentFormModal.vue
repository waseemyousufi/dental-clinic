<template>
  <n-modal v-model:show="visible" class="appointment-add-edit-modal" transform-origin="center" :mask-closable="false">
    <n-card
      :title="isEditMode ? 'Edit Appointment' : 'New Appointment'"
      class="appointment-form-card"
      bordered
      size="medium"
      style="max-width: 600px;"
      role="dialog"
      aria-modal="true"
    >
      <n-form :model="formModel" class="appointment-form" size="small" :show-require-mark="false">
        <div class="appointment-form__rows">
          <div class="appointment-form__pair">
            <n-form-item label="Patient" path="patientId" class="appointment-form__field">
              <n-select
                v-model:value="formModel.patientId"
                :options="patientOptions"
                :disabled="lockPatient"
                placeholder="Patient"
                filterable
                size="small"
              />
            </n-form-item>
            <n-form-item label="Doctor" path="employeeId" class="appointment-form__field">
              <n-select
                v-model:value="formModel.employeeId"
                :options="employeeOptions"
                placeholder="Employee"
                filterable
                size="small"
              />
            </n-form-item>
          </div>
          <div class="appointment-form__pair">
            <n-form-item label="Date & time" path="appointment_timestamp" class="appointment-form__field">
              <n-date-picker v-model:value="formModel.appointment_timestamp" type="datetime" clearable size="small" style="width: 100%" />
            </n-form-item>
            <n-form-item label="Status" path="status" class="appointment-form__field">
              <n-select v-model:value="formModel.status" :options="statusOptions" size="small" />
            </n-form-item>
          </div>
          <div class="appointment-form__pair">
            <n-form-item label="Appointment Cost" path="appointment_cost" class="appointment-form__field">
              <n-input-number v-model:value="formModel.appointment_cost" :min="0" size="small" />
            </n-form-item>
            <n-form-item v-if="isDoctorUsing" label="Clinical Notes" path="clinical_notes" class="appointment-form__field">
              <n-input v-model:value="formModel.clinical_notes" type="textarea" placeholder="Details..." size="small" :autosize="{ minRows: 2, maxRows: 6 }" />
            </n-form-item>

          </div>
          <n-form-item v-if="!isDoctorUsing" label="Description" path="description" class="appointment-form__field appointment-form__field--full">
            <n-input v-model:value="formModel.description" type="textarea" placeholder="Details..." size="small" :autosize="{ minRows: 2, maxRows: 6 }" />
          </n-form-item>
        </div>
      </n-form>

      <template #footer>
        <n-space justify="end" size="small">
          <n-button size="small" @click="visible = false">Cancel</n-button>
          <n-button size="small" type="primary" :loading="loading" @click="submit">{{ isEditMode ? 'Update' : 'Save' }}</n-button>
        </n-space>
      </template>
    </n-card>
  </n-modal>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { NModal, NCard, NForm, NFormItem, NSelect, NDatePicker, NInput, NButton, NSpace, useMessage, NInputNumber } from 'naive-ui'
import employeeApi from '@api/employee'
import patientApi from '@api/patient'
import type AppointmentData from '@api/interfaces/Appointment'
import type EmployeeData from '@api/interfaces/Employee'
import type PatientData from '@api/interfaces/patient'

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

const props = withDefaults(defineProps<{
  show: boolean
  appointment?: Partial<AppointmentData> | null
  patientId?: number | null
  treatmentPlanId?: number | null
  lockPatient?: boolean
  loading?: boolean
  isDoctorUsing: boolean
}>(), {
  appointment: null,
  patientId: null,
  treatmentPlanId: null,
  lockPatient: false,
  loading: false,
  isDoctorUsing: false
})

const emit = defineEmits<{
  (e: 'update:show', value: boolean): void
  (e: 'save', payload: AppointmentData): void
}>()

const message = useMessage()
const employeeOptions = ref<{ label: string; value: number }[]>([])
const patientOptions = ref<{ label: string; value: number }[]>([])

const statusOptions = [
  { label: 'Pending', value: 'pending' },
  { label: 'Confirmed', value: 'confirmed' },
  { label: 'Completed', value: 'completed' },
  { label: 'Cancelled', value: 'cancelled' },
]

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
  appointment_cost: 0,
  clinical_notes: '',
})

const formatName = (obj: EmployeeAbbr | PatientAbbr) => {
  if (obj.name) return obj.name
  return `${obj.fName || ''} ${obj.lName || ''}`.trim() || 'Unknown'
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

function resetForm() {
  formModel.value = {
    id: props.appointment?.id,
    description: props.appointment?.description || '',
    appointment_timestamp: toTimestamp(props.appointment?.appointment_timestamp) ?? Date.now(),
    status: props.appointment?.status || 'pending',
    employeeId: props.appointment?.employeeId,
    patientId: props.patientId ?? props.appointment?.patientId,
    treatment_plan_id: props.treatmentPlanId ?? props.appointment?.treatment_plan_id ?? null,
    appointment_cost: props.appointment?.appointment_cost ?? 0,
    clinical_notes: props.appointment?.clinical_notes ?? '',
  }
}

async function loadOptions() {
  try {
    const [empRes, patRes] = await Promise.all([
      employeeApi.getBranchEmployees(true),
      patientApi.getBranchPatients(true),
    ])

    const rawEmps = empRes.data?.data || empRes.data
    employeeOptions.value = (Array.isArray(rawEmps) ? rawEmps : rawEmps?.employees || []).map((emp: EmployeeData) => ({
      label: formatName(emp as EmployeeAbbr),
      value: emp.id,
    }))

    const rawPats = patRes.data?.data || patRes.data
    patientOptions.value = (Array.isArray(rawPats) ? rawPats : rawPats?.patients || []).map((pat: PatientData) => ({
      label: formatName(pat as PatientAbbr),
      value: pat.id,
    }))
  } catch (error) {
    message.error('Failed to load appointment options')
  }
}

function submit() {
  if (
    !formModel.value.description ||
    !formModel.value.appointment_timestamp ||
    formModel.value.employeeId == null ||
    formModel.value.patientId == null
  ) {
    message.warning('Please fill in all required fields')
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
    appointment_cost: formModel.value.appointment_cost,
    clinical_notes: formModel.value.clinical_notes,
  })
}

watch(
  () => [props.show, props.appointment, props.patientId, props.treatmentPlanId],
  () => {
    if (props.show) resetForm()
  },
  { immediate: true },
)

onMounted(async () => {
  await loadOptions()
  resetForm()
})
</script>

<style scoped lang="scss">
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
    padding: 0 12px 10px;
  }

  :deep(.appointment-form-card .n-card__footer) {
    padding: 6px 12px 10px;
  }

  :deep(.appointment-form .n-form-item-feedback-wrapper) {
    min-height: 0;
  }

  .appointment-form__rows {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }

  .appointment-form__pair {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 6px 8px;
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
    padding-bottom: 2px;
    font-size: 12px;
  }
}
</style>
