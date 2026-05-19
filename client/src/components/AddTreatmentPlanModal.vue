<script lang="ts" setup>
import { ref, onMounted, watch } from 'vue'
import {
  NModal, NCard, NForm, NFormItem, NSelect,
  NInputNumber, NButton, NSpace, useMessage
} from 'naive-ui'
import { Icon } from '@iconify/vue'
import procedureApi from '@api/procedure'
import TreatmentPlanApi from '@api/treatmentPlan'
import appointment from '@api/appointment'

const props = defineProps<{
  show: boolean
  patientId: number
  appointmentId: number
}>()

const emit = defineEmits(['update:show', 'success'])

const message = useMessage()
const loading = ref(false)
const procedureOptions = ref<{ label: string; value: number; price: number }[]>([])

const formModel = ref({
  procedure_id: null as number | null,
  appointments_needed: 1,
  total_estimated_cost: 0,
})

// Load procedures to populate the dropdown
async function loadProcedures() {
  try {
    const res = await procedureApi.getProcedures()
    const data = res.data?.data ?? res.data
    procedureOptions.value = data.map((p: any) => ({
      label: p.name,
      value: p.id,
      price: parseFloat(p.base_price)
    }))
  } catch (err) {
    message.error('Failed to load clinical procedures')
  }
}

watch(
  () => [
    formModel.value.procedure_id,
    formModel.value.appointments_needed,
  ],
  ([procedureId, appointmentsNeeded]) => {
    const selected = procedureOptions.value.find(
      p => p.value === procedureId
    )

    if (!selected) {
      formModel.value.total_estimated_cost = 0
      return
    }

    formModel.value.total_estimated_cost =
      selected.price * (appointmentsNeeded || 1)
  },
  { immediate: true }
)

// // Automatically update cost when procedure changes
// function handleProcedureChange(value: number) {
//   const selected = procedureOptions.value.find(p => p.value === value)
//   if (selected) {
//     formModel.value.total_estimated_cost = selected.price
//   }
// }

async function handleSubmit() {
  if (!formModel.value.procedure_id) {
    message.warning('Please select a procedure')
    return
  }

  loading.value = true
  try {
    const payload = {
      patient_id: props.patientId,
      appointment_id: props.appointmentId,
      procedure_id: formModel.value.procedure_id,
      appointments_needed: formModel.value.appointments_needed,
      total_estimated_cost: formModel.value.total_estimated_cost,
      status: 'proposed'
    }

    await TreatmentPlanApi.postTreatmentPlan(payload)
    message.success('Treatment plan proposed successfully')
    emit('success')
    closeModal()
  } catch (err) {
    message.error('Could not save treatment plan')
  } finally {
    loading.value = false
  }
}

function closeModal() {
  emit('update:show', false)
  formModel.value.procedure_id = null
  formModel.value.total_estimated_cost = 0
  formModel.value.appointments_needed = 1
}

onMounted(loadProcedures)
</script>

<template>
  <n-modal :show="show" @mask-click="closeModal">
    <n-card style="width: 500px" title="Propose Treatment Plan" :bordered="false" size="huge" role="dialog"
      aria-modal="true">
      <template #header-extra>
        <n-button quaternary circle @click="closeModal">
          <template #icon>
            <Icon icon="mdi:close" />
          </template>
        </n-button>
      </template>

      <n-form :model="formModel">
        <n-form-item label="Select Procedure" path="procedure_id">
          <n-select v-model:value="formModel.procedure_id" :to="false" :options="procedureOptions" placeholder="Search procedure..."
            filterable>
            <template #prefix>
              <Icon icon="healthicons:tooth-outline" />
            </template>
          </n-select>
        </n-form-item>

        <n-form-item label="Estimated Cost (AFN)" path="total_estimated_cost">
          <n-input-number v-model:value="formModel.total_estimated_cost" :min="0" style="width: 100%"
            placeholder="0.00">
            <template #prefix>
              <Icon icon="mdi:cash-multiple" />
            </template>
          </n-input-number>
        </n-form-item>
      </n-form>

      <n-form-item label="Appointments Needed" path="appointments_needed">
        <n-input-number v-model:value="formModel.appointments_needed" :min="1" style="width: 100%" placeholder="1" />
      </n-form-item>

      <template #footer>
        <n-space justify="end">
          <n-button @click="closeModal">Cancel</n-button>
          <n-button type="primary" :loading="loading" @click="handleSubmit">
            <template #icon>
              <Icon icon="mdi:check-circle-outline" />
            </template>
            Confirm Proposal
          </n-button>
        </n-space>
      </template>
    </n-card>
  </n-modal>
</template>
