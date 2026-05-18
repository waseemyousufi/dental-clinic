<script lang="ts" setup>
import { ref, onMounted, computed, h, useTransitionState } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  NCard, NDataTable, NButton, NSpace, NTag, NInput,
  NModal, NForm, NFormItem, NSelect, NInputNumber,
  NDatePicker, useMessage, useDialog, NPopconfirm,
  NDropdown, NIcon, NLayout, NLayoutContent, NGrid, NGi, NStatistic
} from 'naive-ui'
import { Icon } from '@iconify/vue'
import TreatmentPlanApi from '@api/treatmentPlan'
import procedureApi from '@api/procedure'
import patientApi from '@api/patient'
import type TreatmentPlanData from '@api/interfaces/TreatmentPlan'
import type ProcedureData from '@api/interfaces/Procedure'
import type PatientData from '@api/interfaces/Patient'
import useUserStore from '@/stores/user'
import user from '@api/user'

const message = useMessage()
const dialog = useDialog()
const { t } = useI18n()
const userStore = useUserStore()

// State
const treatmentPlans = ref<TreatmentPlanData[]>([])
const loading = ref(false)
const searchQuery = ref('')
const procedures = ref<ProcedureData[]>([])
const patients = ref<PatientData[]>([])

// Modal State
const showModal = ref(false)
const modalLoading = ref(false)
const editingPlan = ref<TreatmentPlanData | null>(null)
const formModel = ref({
  patient_id: null as number | null,
  procedure_id: null as number | null,
  total_estimated_cost: 0,
  total_amount_paid: 0,
  status: 'proposed' as TreatmentPlanData['status'],
  start_date: null as number | null,
  appointments_needed: 1
})

// Options
const statusOptions = [
  { label: t('treatmentView.statusOptions.proposed'), value: 'proposed' },
  { label: t('treatmentView.statusOptions.accepted'), value: 'accepted' },
  { label: t('treatmentView.statusOptions.partially_accepted'), value: 'partially_accepted' },
  { label: t('treatmentView.statusOptions.rejected'), value: 'rejected' },
  { label: t('treatmentView.statusOptions.completed'), value: 'completed' }
]

const procedureOptions = computed(() =>
  procedures.value.map(p => ({
    label: p.name,
    value: p.id,
    price: p.base_price
  }))
)

const patientOptions = computed(() =>
  patients.value.map(p => ({
    label: `${p.fName} ${p.lName}`,
    value: p.id
  }))
)

// Table Columns
const columns = [
  {
    title: t('treatmentView.columns.patient'),
    key: 'patient',
    render(row: TreatmentPlanData) {
      return h(NSpace, { align: 'center', size: 'small' }, {
        default: () => [
          h(Icon, { icon: 'mdi:account-outline', style: { fontSize: '18px' } }),
          h('span', row.patient ? `${row.patient.fName} ${row.patient.lName}` : t('common.noDataAvailable'))
        ]
      })
    }
  },
  {
    title: t('treatmentView.columns.procedure'),
    key: 'procedure',
    render(row: TreatmentPlanData) {
      return h('span', { style: { fontWeight: 'bold' } }, row.procedure?.name || t('common.noDataAvailable'))
    }
  },
  {
    title: t('treatmentView.columns.status'),
    key: 'status',
    render(row: TreatmentPlanData) {
      const typeMap: Record<string, 'default' | 'info' | 'success' | 'warning' | 'error'> = {
        proposed: 'info',
        accepted: 'success',
        partially_accepted: 'warning',
        rejected: 'error',
        completed: 'default'
      }
      return h(NTag, {
        type: typeMap[row.status] || 'default',
        round: true,
        bordered: false
      }, { default: () => t(`treatmentView.statusOptions.${row.status}`) || row.status.toUpperCase() })
    }
  },
  {
    title: t('treatmentView.columns.date'),
    key: 'start_date',
    render(row: TreatmentPlanData) {
      return row.start_date || t('common.noDataAvailable')
    }
  },
]

if(!userStore.isDoctor) {
  columns.push(
  {
    title: 'Cost',
    key: 'total_estimated_cost',
    render(row: TreatmentPlanData) {
      return h('span', `${row.total_estimated_cost.toLocaleString()} AFN`)
    }
  },
)
}

if(!userStore.isReceptionist) {
  columns.push(
      {
    title: t('treatmentView.columns.actions'),
    key: 'actions',
    render(row: TreatmentPlanData) {
      return h(NSpace, {}, {
        default: () => [
          h(NButton, {
            size: 'small',
            quaternary: true,
            circle: true,
            onClick: () => handleEdit(row)
          }, { icon: () => h(Icon, { icon: 'mdi:pencil-outline' }) }),
          h(NPopconfirm, {
            onPositiveClick: () => handleDelete(row.id!)
          }, {
            trigger: () => h(NButton, {
              size: 'small',
              quaternary: true,
              circle: true,
              type: 'error'
            }, { icon: () => h(Icon, { icon: 'mdi:delete-outline' }) }),
            default: () => t('treatmentView.messages.deleteConfirmMessage')
          }),
          // row.status === 'accepted' ? h(NButton, {
          //   size: 'small',
          //   type: 'primary',
          //   onClick: () => handleExecute(row)
          // }, { default: () => 'Execute' }) : null
        ]
      })
    }
  }
  )
}

// Methods
async function loadData() {
  loading.value = true
  try {
    const [plansRes, proceduresRes, patientsRes] = await Promise.all([
      TreatmentPlanApi.getBranchTreatmentPlans(),
      procedureApi.getProcedures(),
      patientApi.getBranchPatients(true)
    ])

    treatmentPlans.value = plansRes.data.data || plansRes.data
    procedures.value = proceduresRes.data.data || proceduresRes.data
    patients.value = patientsRes.data.data || patientsRes.data
  } catch (err) {
    message.error(t('treatmentView.messages.loadDataError'))
  } finally {
    loading.value = false
  }
}

function handleAdd() {
  editingPlan.value = null
  formModel.value = {
    patient_id: null,
    procedure_id: null,
    total_estimated_cost: 0,
    total_amount_paid: 0,
    status: 'proposed',
    start_date: Date.now(),
    appointments_needed: 1
  }
  showModal.value = true
}

function handleEdit(plan: TreatmentPlanData) {
  editingPlan.value = plan
  formModel.value = {
    patient_id: plan.patient_id,
    procedure_id: plan.procedure_id,
    total_estimated_cost: plan.total_estimated_cost,
    total_amount_paid: plan.total_amount_paid || 0,
    status: plan.status,
    start_date: plan.start_date ? new Date(plan.start_date).getTime() : null,
    appointments_needed: plan.appointments_needed || 1
  }
  showModal.value = true
}

function handleProcedureChange(value: number) {
  const selected = procedures.value.find(p => p.id === value)
  if (selected) {
    formModel.value.total_estimated_cost = selected.base_price
  }
}

async function handleSubmit() {
  if (!formModel.value.patient_id || !formModel.value.procedure_id) {
    message.warning(t('treatmentView.messages.requiredFieldsWarning'))
    return
  }

  modalLoading.value = true
  try {
    const payload = {
      ...formModel.value,
      start_date: formModel.value.start_date ? new Date(formModel.value.start_date).toISOString().split('T')[0] : undefined
    }

    if (editingPlan.value) {
      await TreatmentPlanApi.putTreatmentPlan(editingPlan.value.id!, payload as any)
      message.success(t('treatmentView.messages.planUpdatedSuccess'))
    } else {
      await TreatmentPlanApi.postTreatmentPlan(payload as any)
      message.success(t('treatmentView.messages.planCreatedSuccess'))
    }
    showModal.value = false
    loadData()
  } catch (err) {
    message.error(t('treatmentView.messages.operationFailed'))
  } finally {
    modalLoading.value = false
  }
}

async function handleDelete(id: number) {
  try {
    await TreatmentPlanApi.deleteTreatmentPlan(id)
    message.success(t('treatmentView.messages.planDeletedSuccess'))
    loadData()
  } catch (err) {
    message.error(t('treatmentView.messages.deleteFailed'))
  }
}

function handleExecute(plan: TreatmentPlanData) {
  dialog.warning({
    title: t('treatmentView.executeDialog.title'),
    content: t('treatmentView.executeDialog.content', {
      procedure: plan.procedure?.name || t('common.noDataAvailable'),
      patient: plan.patient?.fName || t('common.noDataAvailable')
    }),
    positiveText: t('treatmentView.executeDialog.positiveText'),
    negativeText: t('common.cancelButtonText'),
    onPositiveClick: async () => {
      try {
        await TreatmentPlanApi.updateStatus(plan.id!, { ...plan, status: 'completed' })
        message.success(t('treatmentView.messages.executionSuccess'))
        loadData()
      } catch (err) {
        message.error(t('treatmentView.messages.executionFailed'))
      }
    }
  })
}

const filteredPlans = computed(() => {
  if (!searchQuery.value) return treatmentPlans.value
  const q = searchQuery.value.toLowerCase()
  return treatmentPlans.value.filter(p =>
    p.patient?.fName.toLowerCase().includes(q) ||
    p.patient?.lName.toLowerCase().includes(q) ||
    p.procedure?.name.toLowerCase().includes(q)
  )
})

const stats = computed(() => {
  const total = treatmentPlans.value.length
  const completed = treatmentPlans.value.filter(p => p.status === 'completed').length
  const accepted = treatmentPlans.value.filter(p => p.status === 'accepted').length
  const revenue = treatmentPlans.value.reduce((acc, curr) => acc + curr.total_estimated_cost, 0)
  return { total, completed, accepted, revenue }
})

onMounted(loadData)
</script>

<template>
  <n-layout class="treatments-container">
    <n-layout-content content-style="padding: 24px;">
      <n-space vertical size="large">
        <div class="header">
          <div>
            <h1 style="margin: 0;">{{ t('treatmentView.headerTitle') }}</h1>
            <p style="color: #666;">{{ t('treatmentView.headerCopy') }}</p>
          </div>
          <n-button v-if="!userStore.isReceptionist" type="primary" size="large" @click="handleAdd">
            <template #icon>
              <Icon icon="mdi:plus" />
            </template>
            {{ t('treatmentView.newPlanButtonText') }}
          </n-button>
        </div>

        <n-grid cols="1 400:2 800:4" x-gap="12" y-gap="12">
          <n-gi>
            <n-card size="small" class="stat-card">
              <n-statistic :label="t('treatmentView.stats.totalPlans')" :value="stats.total">
                <template #prefix>
                  <Icon icon="mdi:clipboard-text-outline" color="#1890ff" />
                </template>
              </n-statistic>
            </n-card>
          </n-gi>
          <n-gi>
            <n-card size="small" class="stat-card">
              <n-statistic :label="t('treatmentView.stats.completed')" :value="stats.completed">
                <template #prefix>
                  <Icon icon="mdi:check-circle-outline" color="#52c41a" />
                </template>
              </n-statistic>
            </n-card>
          </n-gi>
          <n-gi>
            <n-card size="small" class="stat-card">
              <n-statistic :label="t('treatmentView.stats.accepted')" :value="stats.accepted">
                <template #prefix>
                  <Icon icon="mdi:handshake-outline" color="#faad14" />
                </template>
              </n-statistic>
            </n-card>
          </n-gi>
          <n-gi>
            <n-card size="small" class="stat-card" v-if="userStore.isAdmin">
              <n-statistic :label="t('treatmentView.stats.revenue')" :value="stats.revenue">
                <template #prefix>
                  <Icon icon="mdi:cash" color="#eb2f96" />
                </template>
                <template #suffix>AFN</template>
              </n-statistic>
            </n-card>
          </n-gi>
        </n-grid>

        <n-card>
          <n-space vertical>
            <n-input
              v-model:value="searchQuery"
              :placeholder="t('treatmentView.searchPlaceholder')"
              clearable
              size="large"
            >
              <template #prefix>
                <Icon icon="mdi:magnify" />
              </template>
            </n-input>

            <n-data-table
              :loading="loading"
              :columns="columns"
              :data="filteredPlans"
              :pagination="{ pageSize: 10 }"
              striped
            />
          </n-space>
        </n-card>
      </n-space>
    </n-layout-content>

    <!-- Add/Edit Modal -->
    <n-modal v-model:show="showModal" @mask-click="showModal = false">
      <n-card
        style="width: 600px"
        :title="editingPlan ? t('treatmentView.modal.editTitle') : t('treatmentView.modal.newTitle')"
        :bordered="false"
        size="huge"
        role="dialog"
        aria-modal="true"
      >
        <n-form :model="formModel" label-placement="left" label-width="140">
          <n-form-item :label="t('treatmentView.form.patientLabel')" path="patient_id" required>
            <n-select
              v-model:value="formModel.patient_id"
              :options="patientOptions"
              :placeholder="t('treatmentView.form.patientPlaceholder')"
              filterable
            />
          </n-form-item>

          <n-form-item :label="t('treatmentView.form.procedureLabel')" path="procedure_id" required>
            <n-select
              v-model:value="formModel.procedure_id"
              :options="procedureOptions"
              :placeholder="t('treatmentView.form.procedurePlaceholder')"
              filterable
              @update:value="handleProcedureChange"
            />
          </n-form-item>

          <n-form-item :label="t('treatmentView.form.estimatedCostLabel')" path="total_estimated_cost">
            <n-input-number
              v-model:value="formModel.total_estimated_cost"
              :min="0"
              style="width: 100%"
              :placeholder="t('treatmentView.form.estimatedCostPlaceholder')"
            >
              <template #suffix>AFN</template>
            </n-input-number>
          </n-form-item>

          <n-form-item :label="t('treatmentView.form.statusLabel')" path="status">
            <n-select v-model:value="formModel.status" :options="statusOptions" />
          </n-form-item>

          <n-form-item :label="t('treatmentView.form.startDateLabel')" path="start_date">
            <n-date-picker
              v-model:value="formModel.start_date"
              type="date"
              style="width: 100%"
            />
          </n-form-item>

          <n-form-item :label="t('treatmentView.form.appointmentsNeededLabel')" path="appointments_needed">
            <n-input-number v-model:value="formModel.appointments_needed" :min="1" style="width: 100%" />
          </n-form-item>
        </n-form>

        <template #footer>
          <n-space justify="end">
            <n-button @click="showModal = false">{{ t('common.cancelButtonText') }}</n-button>
            <n-button type="primary" :loading="modalLoading" @click="handleSubmit">
              {{ t('common.saveButtonText') }}
            </n-button>
          </n-space>
        </template>
      </n-card>
    </n-modal>
  </n-layout>
</template>

<style scoped>
.treatments-container {
  background-color: #f5f7fa;
  min-height: 100vh;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.stat-card {
  transition: transform 0.2s;
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
</style>
