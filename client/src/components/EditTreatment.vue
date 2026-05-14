<template>
  <n-modal
    v-model:show="visible"
    preset="card"
    :closable="true"
    :mask-closable="false"
    class="edit-treatment-modal"
    :title="isEditMode ? 'Edit Treatment Plan' : 'New Treatment Plan'"
    style="width: min(760px, calc(100vw - 2rem))"
  >
    <div class="modal-subtitle">
      {{ isEditMode ? 'Update the treatment details below and save your changes.' : 'Fill treatment details to create a new plan.' }}
    </div>

    <div class="modal-grid">
      <div class="summary-panel">
        <div class="summary-row">
          <span>Plan ID</span>
          <strong>#{{ plan?.id ?? '—' }}</strong>
        </div>

        <div class="summary-row">
          <span>Procedure</span>
          <strong>{{ selectedProcedureLabel }}</strong>
        </div>

        <div class="summary-row">
          <span>Status</span>
          <strong class="status-pill" :data-status="form.status">
            {{ form.status || '—' }}
          </strong>
        </div>
      </div>

      <n-form :model="form" label-placement="top" class="edit-form">
        <div class="form-grid">
          <n-form-item label="Procedure">
            <n-select
              v-model:value="form.procedure_id"
              :options="procedureOptions"
              filterable
              clearable
              class="full"
            />
          </n-form-item>

          <n-form-item label="Status">
            <n-select
              v-model:value="form.status"
              :options="[
                { label: 'Proposed', value: 'proposed' },
                { label: 'Accepted', value: 'accepted' },
                { label: 'Partially Accepted', value: 'partially_accepted' },
                { label: 'Rejected', value: 'rejected' },
                { label: 'Completed', value: 'completed' }
              ]"
              class="full"
            />
          </n-form-item>

          <n-form-item label="Estimated Cost">
            <n-input-number v-model:value="form.total_estimated_cost" :min="0" class="full" />
          </n-form-item>

          <!-- <n-form-item label="Total Amount Paid">
            <n-input-number v-model:value="form.total_amount_paid" :min="0" clearable class="full" />
          </n-form-item> -->

          <n-form-item label="Appointments Needed">
            <n-input-number v-model:value="form.appointments_needed" :min="0" clearable class="full" />
          </n-form-item>

          <n-form-item label="Start Date">
            <n-date-picker
              v-model:value="form.start_date"
              type="date"
              class="full"
              clearable
            />
          </n-form-item>
        </div>
      </n-form>
    </div>

    <template #footer>
      <div class="modal-actions">
        <n-button @click="close" tertiary>Cancel</n-button>
        <n-button type="primary" :loading="loading" @click="submit">
          {{ isEditMode ? 'Save Changes' : 'Create Plan' }}
        </n-button>
      </div>
    </template>
  </n-modal>
</template>

<script setup lang="ts">
import { computed, reactive, watch } from 'vue'
import {
  NModal,
  NForm,
  NFormItem,
  NInputNumber,
  NSelect,
  NDatePicker,
  NButton
} from 'naive-ui'

type TreatmentPlanLike = {
  id?: number
  procedure_id?: number
  total_estimated_cost?: number
  total_amount_paid?: number | null
  appointments_needed?: number | null
  start_date?: string | number | null
  status?: 'proposed' | 'accepted' | 'rejected' | string
}

const props = defineProps<{
  show: boolean
  plan: TreatmentPlanLike | null
  procedures?: any[]
  loading?: boolean
}>()

const emit = defineEmits<{
  (e: 'update:show', value: boolean): void
  (e: 'save', payload: {
    procedure_id: number
    total_estimated_cost: number
    total_amount_paid: number | null
    appointments_needed: number | null
    start_date: string
    status: string
  }): void
}>()

const visible = computed({
  get: () => props.show,
  set: (value: boolean) => emit('update:show', value)
})

const isEditMode = computed(() => !!props.plan?.id)

const form = reactive({
  procedure_id: null as number | null,
  total_estimated_cost: null as number | null,
  total_amount_paid: null as number | null,
  appointments_needed: null as number | null,
  start_date: null as number | null,
  status: 'proposed'

})

function toTimestamp(value: string | number | null | undefined) {
  if (!value) return null
  const d = new Date(value)
  return Number.isNaN(d.getTime()) ? null : d.getTime()
}

function toDateString(value: number | null) {
  if (!value) return new Date().toISOString().slice(0, 10)
  return new Date(value).toISOString().slice(0, 10)
}

watch(
  () => props.plan,
  (plan) => {
    if (!plan) {
      form.procedure_id = null
      form.total_estimated_cost = null
      form.total_amount_paid = null
      form.appointments_needed = null
      form.start_date = toTimestamp(new Date().toISOString())
      form.status = 'proposed'
      return
    }
    form.procedure_id = plan.procedure_id ?? null
    form.total_estimated_cost = plan.total_estimated_cost ?? null
    form.total_amount_paid = plan.total_amount_paid ?? null
    form.appointments_needed = plan.appointments_needed ?? null
    form.start_date = toTimestamp(plan.start_date)
    form.status = plan.status || 'proposed'
  },
  { immediate: true }
)

watch(
  () => props.show,
  (show) => {
    if (!show) return
    if (props.plan) {
      form.procedure_id = props.plan.procedure_id ?? null
      form.total_estimated_cost = props.plan.total_estimated_cost ?? null
      form.total_amount_paid = props.plan.total_amount_paid ?? null
      form.appointments_needed = props.plan.appointments_needed ?? null
      form.start_date = toTimestamp(props.plan.start_date)
      form.status = props.plan.status || 'proposed'
      return
    }

    form.procedure_id = null
    form.total_estimated_cost = null
    form.total_amount_paid = null
    form.appointments_needed = null
    form.start_date = toTimestamp(new Date().toISOString())
    form.status = 'proposed'
  }
)

const procedureOptions = computed(() =>
  (props.procedures || []).map((p: any) => ({
    label: p.name || p.title || `Procedure #${p.id}`,
    value: p.id
  }))
)

const selectedProcedureLabel = computed(() => {
  const found = (props.procedures || []).find((p: any) => p.id === form.procedure_id)
  return found?.name || found?.title || `Procedure #${form.procedure_id ?? '—'}`
})

function close() {
  visible.value = false
}

function submit() {
  if (
    form.procedure_id === null ||
    form.total_estimated_cost === null ||
    !form.status ||
    !form.start_date
  ) {
    return
  }

  emit('save', {
    procedure_id: form.procedure_id,
    total_estimated_cost: form.total_estimated_cost,
    total_amount_paid: form.total_amount_paid,
    appointments_needed: form.appointments_needed,
    start_date: toDateString(form.start_date),
    status: form.status
  })
}
</script>

<style scoped>
.edit-treatment-modal :deep(.n-card) {
  border-radius: 1.15rem;
  box-shadow: 0 1rem 2.5rem rgba(15, 23, 42, 0.12);
  overflow: hidden;
}

.modal-subtitle {
  margin-top: -0.35rem;
  margin-bottom: 1rem;
  color: #64748b;
  font-size: 0.92rem;
}

.modal-grid {
  display: grid;
  grid-template-columns: 18rem minmax(0, 1fr);
  gap: 1rem;
}

.summary-panel {
  background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
  border: 0.0625em solid #e5eaf2;
  border-radius: 1rem;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.summary-row {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.summary-row span {
  font-size: 0.75rem;
  color: #64748b;
}

.summary-row strong {
  font-size: 0.95rem;
  color: #0f172a;
  word-break: break-word;
}

.status-pill {
  display: inline-flex;
  width: fit-content;
  align-items: center;
  border-radius: 999px;
  padding: 0.25rem 0.7rem;
  background: #eef2ff;
}

.status-pill[data-status='accepted'] {
  background: #dcfce7;
}

.status-pill[data-status='rejected'] {
  background: #fee2e2;
}

.edit-form {
  width: 100%;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.9rem;
}

.full {
  width: 100%;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

@media (max-width: 48rem) {
  .modal-grid {
    grid-template-columns: 1fr;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .modal-actions {
    flex-direction: column-reverse;
  }

  .modal-actions :deep(.n-button) {
    width: 100%;
  }
}
</style>
