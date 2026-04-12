<script setup lang="ts">
import { ref, reactive, computed, onMounted, h } from 'vue'
import {
  NCard,
  NButton,
  NInput,
  NDataTable,
  NModal,
  NForm,
  NFormItem,
  NSelect,
  NDatePicker,
  NUpload,
  useMessage,
  type SelectOption,
  type UploadFileInfo,
  type UploadCustomRequestOptions,
} from 'naive-ui'

import dentalXrayApi from '@api/dentalXray'
import type DentalXrayData from '@api/interfaces/DentalXray'
import patientApi from '@api/patient'
import employeeApi from '@api/employee'

type DentalXrayRow = DentalXrayData & {
  id?: number
  patient?: string
  requestedByEmployee?: string
  takenByEmployee?: string
}

const message = useMessage()

const loading = ref(false)
const xrays = ref<DentalXrayRow[]>([])
const keyword = ref('')

const showEditor = ref(false)
const submitting = ref(false)
const isEditing = ref(false)
const editingId = ref<number | null>(null)

const patientOptions = ref<SelectOption[]>([])
const employeeOptions = ref<SelectOption[]>([])
const fileList = ref<UploadFileInfo[]>([])

const formModel = reactive<DentalXrayData>({
  xray_type: '',
  xray_timestamp: '',
  tooth_part: '',
  side: '',
  image_path: '',
  diagnosis_notes: '',
  payment_status: 'unpaid',
  results_summery: '',
  patient_id: 0,
  requestedByEmployee_id: 0,
  takenByEmployee_id: 0,
})

const xrayTypeOptions = [
  { label: 'Bitewing', value: 'Bitewing' },
  { label: 'Periapical', value: 'Periapical' },
  { label: 'Panoramic', value: 'Panoramic' },
  { label: 'Occlusal', value: 'Occlusal' },
  { label: 'CBCT', value: 'CBCT' },
]

const sideOptions = [
  { label: 'Left', value: 'Left' },
  { label: 'Right', value: 'Right' },
  { label: 'Upper', value: 'Upper' },
  { label: 'Lower', value: 'Lower' },
  { label: 'Full', value: 'Full' },
]

const paymentStatusOptions = [
  { label: 'Paid', value: 'paid' },
  { label: 'Unpaid', value: 'unpaid' },
  { label: 'Partial', value: 'partial' },
]

const filteredXrays = computed(() => {
  if (!keyword.value.trim()) return xrays.value
  const k = keyword.value.trim().toLowerCase()
  return xrays.value.filter((x: DentalXrayRow) =>
    [x.xray_type, x.patient, x.tooth_part, x.payment_status].some((field) =>
      String(field || '')
        .toLowerCase()
        .includes(k),
    ),
  )
})

const columns = [
  {
    title: 'Type',
    key: 'xray_type',
  },
  {
    title: 'Patient',
    key: 'patient',
  },
  {
    title: 'Date',
    key: 'xray_timestamp',
    render(row: DentalXrayRow) {
      return row.xray_timestamp ? new Date(row.xray_timestamp).toLocaleString() : '—'
    },
  },
  {
    title: 'Tooth Part',
    key: 'tooth_part',
  },
  {
    title: 'Side',
    key: 'side',
  },
  {
    title: 'Payment',
    key: 'payment_status',
  },
  {
    title: 'Actions',
    key: 'actions',
    render(row: DentalXrayRow) {
      return h('div', { style: 'display: flex; gap: 8px;' }, [
        h(
          NButton,
          {
            size: 'tiny',
            onClick: () => handleEdit(row),
          },
          { default: () => h(EditIcon, { width: 20, height: 20 }) },
        ),
        h(
          NButton,
          {
            size: 'tiny',
            type: 'error',
            tertiary: true,
            onClick: () => handleDelete(row),
          },
          { default: () => h(DeleteIcon, { width: 20, height: 20 }) },
        ),
      ])
    },
  },
]

async function fetchDentalXrays() {
  try {
    loading.value = true
    const { data } = await dentalXrayApi.getBranchDentalXrays()
    xrays.value = data.data as DentalXrayRow[]
  } catch (error) {
    console.error(error)
    message.error('Failed to load X-rays')
  } finally {
    loading.value = false
  }
}

async function fetchPatients() {
  try {
    const { data } = await patientApi.getBranchPatients()
    patientOptions.value = (data.data as any[]).map((p) => ({
      label: `${p.fName} ${p.lName}`,
      value: p.id,
    }))
  } catch (error) {
    console.error(error)
  }
}

async function fetchEmployees() {
  try {
    const { data } = await employeeApi.getBranchEmployees(true)
    employeeOptions.value = (data.data as any[]).map((e) => ({
      label: e.name || `${e.fName} ${e.lName}`,
      value: e.id,
    }))
  } catch (error) {
    console.error(error)
  }
}

function resetForm() {
  formModel.xray_type = ''
  formModel.xray_timestamp = new Date().toISOString()
  formModel.tooth_part = ''
  formModel.side = ''
  formModel.image_path = ''
  formModel.diagnosis_notes = ''
  formModel.payment_status = 'unpaid'
  formModel.results_summery = ''
  formModel.patient_id = 0
  formModel.requestedByEmployee_id = 0
  formModel.takenByEmployee_id = 0
  fileList.value = []
}

function openCreate() {
  isEditing.value = false
  editingId.value = null
  resetForm()
  showEditor.value = true
}

function handleEdit(row: DentalXrayRow) {
  isEditing.value = true
  editingId.value = row.id ?? null
  formModel.xray_type = row.xray_type
  formModel.xray_timestamp = row.xray_timestamp
  formModel.tooth_part = row.tooth_part
  formModel.side = row.side
  formModel.image_path = row.image_path
  formModel.diagnosis_notes = row.diagnosis_notes
  formModel.payment_status = row.payment_status
  formModel.results_summery = row.results_summery
  formModel.patient_id = row.patient_id
  formModel.requestedByEmployee_id = row.requestedByEmployee_id
  formModel.takenByEmployee_id = row.takenByEmployee_id

  fileList.value = row.image_path
    ? [
      {
        id: 'existing',
        name: row.image_path.split('/').pop() || 'image',
        status: 'finished',
        url: row.image_path,
      },
    ]
    : []

  showEditor.value = true
}

async function handleDelete(row: DentalXrayRow) {
  const id = row.id
  if (!id) return
  try {
    await dentalXrayApi.deleteDentalXray(id)
    message.success('X-ray deleted')
    fetchDentalXrays()
  } catch (error) {
    console.error(error)
    message.error('Failed to delete X-ray')
  }
}

// a helper function to make the date sql compatible.
function dateTime(dateStr: string) {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  if (isNaN(date.getTime())) return dateStr

  const Y = date.getFullYear()
  const M = String(date.getMonth() + 1).padStart(2, '0')
  const D = String(date.getDate()).padStart(2, '0')
  const h = String(date.getHours()).padStart(2, '0')
  const m = String(date.getMinutes()).padStart(2, '0')

  return `${Y}/${M}/${D} ${h}:${m}`
}

async function handleCustomRequest({ file, onFinish, onError }: UploadCustomRequestOptions) {
  if (!file.file) return
  try {
    const key = await dentalXrayApi.putXrayImage(file.file)
    formModel.image_path = key
    onFinish()
    message.success('Image uploaded successfully')
  } catch (error) {
    console.error(error)
    onError()
    message.error('Failed to upload image')
  }
}

async function handleSubmit() {
  submitting.value = true
  try {
    const payload = {
      ...formModel,
      xray_timestamp: dateTime(formModel.xray_timestamp),
    }

    console.log(payload)

    if (isEditing.value && editingId.value != null) {
      await dentalXrayApi.updateDentalXray(editingId.value, payload as any)
      message.success('X-ray updated')
    } else {
      await dentalXrayApi.postDentalXray(payload as any)
      message.success('X-ray created')
    }
    showEditor.value = false
    await fetchDentalXrays()
  } catch (error) {
    console.error(error)
    message.error('Failed to save X-ray')
  } finally {
    submitting.value = false
  }
}

function handleDateChange(value: number | null) {
  formModel.xray_timestamp = value ? new Date(value).toISOString() : ''
}

onMounted(() => {
  fetchDentalXrays()
  fetchPatients()
  fetchEmployees()
})
</script>

<template>
  <div class="xray-view">
    <n-card size="small" class="xray-panel">
      <div class="toolbar">
        <n-input v-model:value="keyword" clearable placeholder="Search X-rays..." size="small" />
        <n-button type="primary" size="small" @click="openCreate"> New X-ray </n-button>
      </div>

      <div class="table-wrapper">
        <n-data-table class="data-table" :columns="columns" :data="filteredXrays" :loading="loading"
          :pagination="{ pageSize: 10, simple: true }" :scroll-x="1000" size="small" bordered />
      </div>
    </n-card>

    <n-modal v-model:show="showEditor" preset="card" style="max-height: 90vh; overflow-y: auto; width: 800px;"
      :title="isEditing ? 'Edit X-ray' : 'New X-ray'" class="xray-modal">
      <n-form label-width="140" label-placement="left">
        <div class="form-row">
          <n-form-item label="Patient">
            <n-select v-model:value="formModel.patient_id" :options="patientOptions" placeholder="Select patient"
              filterable />
          </n-form-item>
          <n-form-item label="X-ray Type">
            <n-select v-model:value="formModel.xray_type" :options="xrayTypeOptions" placeholder="Select type" />
          </n-form-item>
        </div>

        <div class="form-row">
          <n-form-item label="Tooth Part">
            <n-input v-model:value="formModel.tooth_part" placeholder="e.g. Upper Left Molar" />
          </n-form-item>
          <n-form-item label="Side">
            <n-select v-model:value="formModel.side" :options="sideOptions" placeholder="Select side" />
          </n-form-item>
        </div>

        <div class="form-row">
          <n-form-item label="Date & Time">
            <n-date-picker type="datetime" style="width: 100%"
              :value="formModel.xray_timestamp ? Date.parse(formModel.xray_timestamp) : null"
              @update:value="handleDateChange" />
          </n-form-item>
          <n-form-item label="Payment Status">
            <n-select v-model:value="formModel.payment_status" :options="paymentStatusOptions" />
          </n-form-item>
        </div>

        <div class="form-row">
          <n-form-item label="Requested By">
            <n-select v-model:value="formModel.requestedByEmployee_id" :options="employeeOptions" placeholder="Doctor"
              filterable />
          </n-form-item>
          <n-form-item label="Taken By">
            <n-select v-model:value="formModel.takenByEmployee_id" :options="employeeOptions" placeholder="Technician"
              filterable />
          </n-form-item>
        </div>

        <n-form-item label="Diagnosis Notes">
          <n-input v-model:value="formModel.diagnosis_notes" type="textarea" placeholder="Enter notes..." />
        </n-form-item>

        <n-form-item label="Results Summary">
          <n-input v-model:value="formModel.results_summery" type="textarea" placeholder="Enter summary..." />
        </n-form-item>

        <n-form-item label="X-ray Image">
          <n-upload v-model:file-list="fileList" list-type="image-card" :max="1" accept="image/*"
            :custom-request="handleCustomRequest">
            Upload
          </n-upload>
        </n-form-item>

        <div class="form-actions">
          <n-button size="small" @click="showEditor = false">Cancel</n-button>
          <n-button type="primary" size="small" :loading="submitting" @click="handleSubmit">
            Save
          </n-button>
        </div>
      </n-form>
    </n-modal>
  </div>
</template>

<style scoped>
.xray-view {
  padding: 16px 20px;
  display: flex;
  flex-direction: column;
  width: 100%;
  min-height: 100%;
  box-sizing: border-box;
}

.xray-panel {
  width: 100%;
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: column;
}

.xray-panel :deep(.n-card__content) {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-height: 0;
}

.toolbar {
  display: flex;
  gap: 8px;
  margin-bottom: 12px;
  flex-shrink: 0;
}

.table-wrapper {
  flex: 1;
  min-width: 0;
  overflow-x: auto;
}

.form-row {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

@media (max-width: 768px) {
  .form-row {
    grid-template-columns: 1fr;
  }
}

.form-actions {
  margin-top: 16px;
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}
</style>
