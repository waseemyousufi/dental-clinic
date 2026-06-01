<script setup lang="ts">
import { ref, reactive, computed, onMounted, h } from 'vue'
import { useRoute, useRouter } from 'vue-router'
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
  NPopconfirm,
  useMessage,
  NAvatar,
} from 'naive-ui'
import PatientProfilePopup from '@/components/PatientProfilePopup.vue'
import patientApi from '@api/patient'
import type PatientData from '@api/interfaces/Patient'
import { Icon } from '@iconify/vue';
import ItemViewPopup from '../components/ItemViewPopup.vue';
import { useI18n } from 'vue-i18n';
import { useBranchStore } from '@/stores/branchStore'
import useUserStore from '@/stores/user'

const { t } = useI18n(); // Get the t function

type PatientRow = PatientData & { id?: number }

const message = useMessage()
const router = useRouter()
const route = useRoute()
const userStore = useUserStore()

const getEffectiveBranchId = (): number | undefined => {
  const usr = JSON.parse(localStorage.getItem('user') || 'null')
  const userBranchId = usr?.user?.employee?.branchId
  if (typeof userBranchId === 'number' && Number.isFinite(userBranchId)) return userBranchId

  const raw = route.query.branchId
  const fromQuery = typeof raw === 'string' ? Number(raw) : NaN
  return Number.isFinite(fromQuery) ? fromQuery : undefined
}

const loading = ref(false)
const patients = ref<PatientRow[]>([])
const keyword = ref('')

const showEditor = ref(false)
const submitting = ref(false)
const isEditing = ref(false)
const editingId = ref<number | null>(null)

const showViewPopup = ref(false);
const viewPopupData = ref<PatientRow | null>(null);

const profileImage = ref<string | null>(null)
const profileFile = ref<File | null>(null)
const fileInputRef = ref<HTMLInputElement | null>(null)

const formModel = reactive<PatientRow>({
  fName: '',
  lName: '',
  gender: '',
  bloodType: '',
  emgContact: '',
  registerationDate: '',
  phone: '',
})

// Translate options
const genderOptions = computed(() => [
  { label: t('patientView.genderOptions.male'), value: 'male' },
  { label: t('patientView.genderOptions.female'), value: 'female' },
])

const bloodTypeOptions = computed(() => [
  { label: t('patientView.bloodTypeOptions.A+'), value: 'A+' },
  { label: t('patientView.bloodTypeOptions.A-'), value: 'A-' },
  { label: t('patientView.bloodTypeOptions.B+'), value: 'B+' },
  { label: t('patientView.bloodTypeOptions.B-'), value: 'B-' },
  { label: t('patientView.bloodTypeOptions.AB+'), value: 'AB+' },
  { label: t('patientView.bloodTypeOptions.AB-'), value: 'AB-' },
  { label: t('patientView.bloodTypeOptions.O+'), value: 'O+' },
  { label: t('patientView.bloodTypeOptions.O-'), value: 'O-' },
])

const filteredPatients = computed(() => {
  if (!keyword.value.trim()) return patients.value
  const k = keyword.value.trim().toLowerCase()
  return patients.value.filter((p: PatientRow) =>
    [p.fName, p.lName, p.phone].some((field) =>
      String(field || '')
        .toLowerCase()
        .includes(k),
    ),
  )
})



const columns = computed(() => [
  {
    title: t('patientView.columns.firstName'),
    key: 'fName',
    ellipsis: { tooltip: true },
  },
  {
    title: t('patientView.columns.lastName'),
    key: 'lName',
    ellipsis: { tooltip: true },
  },
  {
    title: t('patientView.columns.gender'),
    key: 'gender',
  },
  {
    title: t('patientView.columns.bloodType'),
    key: 'bloodType',
  },
  {
    title: t('patientView.columns.registrationDate'),
    key: 'registerationDate',
  },
])

if (userStore.isAdmin || userStore.settings.rec_can_view_phones) {
  columns.value.push(
    {
      title: t('patientView.columns.phone'),
      key: 'phone',
      ellipsis: { tooltip: true },
    },
    {
      title: t('patientView.columns.emergencyContact'),
      key: 'emgContact',
      ellipsis: { tooltip: true },
    },
  )
}

columns.value.push(  {
    title: t('patientView.columns.actions'),
    key: 'actions',
    render(row: PatientRow) {
      return h('div', { style: 'display: flex; gap: 8px;' }, [
        // View button with icon
        !userStore.isDoctor && h(
          Icon,
          {
            icon: 'carbon:data-view-alt',
            title: t('patientView.actions.viewTooltip'), // Translated tooltip
            width: 20,
            height: 20,
            color: '#4b5563',
            style: { cursor: 'pointer' },
            size: 'tiny',
            onClick: () => { ViewPatient(row) },
          },
        ),
        // Dentist View button
        !userStore.isReceptionist && h(
          Icon,
          {
            icon: 'mdi:tooth',
            title: t('patientView.actions.dentistViewTooltip'), // Translated tooltip
            width: 20,
            height: 20,
            color: '#4f46e5',
            style: { cursor: 'pointer' },
            onClick: () => {
              if (row.id) router.push(`/dentist/patient/${row.id}/?branchId=${getEffectiveBranchId()}`)
            },
          },
        ),
        // Edit button with icon
        !userStore.isDoctor && h(
          Icon,
          {
            icon: 'akar-icons:edit',
            title: t('patientView.actions.editTooltip'), // Translated tooltip
            width: 20,
            height: 20,
            color: '#4f46e5',
            style: { cursor: 'pointer' },
            size: 'tiny',
            onClick: () => handleEdit(row),
          },
        ),
        // Delete button
        userStore.isAdmin && h(
          NPopconfirm,
          {
            onPositiveClick: () => handleDelete(row),
            positiveText: t('common.deleteButtonText'), // Translated button text
            negativeText: t('common.cancelButtonText'), // Translated button text
          },
          {
            trigger: () =>
              h(
                Icon,
                {
                  icon: 'fluent:delete-16-filled',
                  title: t('patientView.actions.deleteTooltip'), // Translated tooltip
                  width: 20,
                  height: 20,
                  color: '#dc2626',
                  style: { cursor: 'pointer' },
                },
              ),
            default: () => t('patientView.actions.deleteConfirmMessage'), // Translated confirmation message
          },
        ),
      ])
    },
  },)

function resetForm() {
  formModel.fName = ''
  formModel.lName = ''
  formModel.gender = ''
  formModel.bloodType = ''
  formModel.emgContact = ''
  formModel.registerationDate = ''
  formModel.phone = ''
  profileImage.value = null
  profileFile.value = null
}

async function fetchPatients() {
  try {
    loading.value = true
    const { data } = await patientApi.getBranchPatients(false, getEffectiveBranchId())
    console.log(data.data)
    patients.value = data.data as PatientRow[]
  } catch (error) {
    console.error(error)
    message.error(t('patientView.messages.loadPatientsError')) // Translated message
  } finally {
    loading.value = false
  }
}

function openCreate() {
  isEditing.value = false
  editingId.value = null
  resetForm()
  showEditor.value = true
}

function handleEdit(row: PatientRow) {
  isEditing.value = true
  editingId.value = (row as any).id ?? null
  formModel.fName = String(row.fName || '')
  formModel.lName = String(row.lName || '')
  formModel.gender = String(row.gender || '')
  formModel.bloodType = String(row.bloodType || '')
  formModel.emgContact = String(row.emgContact || '')
  formModel.registerationDate = String(row.registerationDate || '')
  formModel.phone = String(row.phone || '')

  profileImage.value = null
  profileFile.value = null
  showEditor.value = true
}

async function handleDelete(row: PatientRow) {
  const id = (row as any).id
  if (!id) {
    message.error(t('patientView.messages.missingPatientIdError')) // Translated message
    return
  }
  try {
    await patientApi.deletePatient(id)
    message.success(t('patientView.messages.patientDeletedSuccess')) // Translated message
    fetchPatients()
  } catch (error) {
    console.error(error)
    message.error(t('patientView.messages.deletePatientError')) // Translated message
  }
}

function triggerFileInput() {
  fileInputRef.value?.click()
}

function handleFileChange(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (file) {
    profileFile.value = file
    const reader = new FileReader()
    reader.onload = (e) => {
      profileImage.value = e.target?.result as string
    }
    reader.readAsDataURL(file)
  }
}

async function handleSubmit() {
  submitting.value = true
  try {
    const payload: PatientData = {
      fName: formModel.fName,
      lName: formModel.lName,
      gender: formModel.gender,
      bloodType: formModel.bloodType,
      emgContact: formModel.emgContact,
      registerationDate: formModel.registerationDate,
      phone: formModel.phone,
      reception_cost: userStore.settings.reception_cost, // Include reception cost in payload
    }

    let patientId = editingId.value

    if (isEditing.value && patientId != null) {
      await patientApi.updatePatient(patientId, payload)
      message.success(t('patientView.messages.patientUpdatedSuccess')) // Translated message
    } else {
      const { data } = await patientApi.postPatient(payload)
      message.success(t('patientView.messages.patientCreatedSuccess')) // Translated message
      patientId = (data as any).id
    }

    if (profileFile.value && patientId != null) {
      await patientApi.updateProfilePicture(patientId, profileFile.value)
    }

    showEditor.value = false
    await fetchPatients()
  } catch (error) {
    console.error(error)
    message.error(t('patientView.messages.savePatientError')) // Translated message
  } finally {
    submitting.value = false
  }
}

function handleDateChange(value: number | null) {
  if (!value) {
    formModel.registerationDate = ''
  } else {
    formModel.registerationDate = new Date(value).toISOString().slice(0, 10)
  }
}

function ViewPatient(row: PatientRow) {
  viewPopupData.value = row;
  showViewPopup.value = true;
}

onMounted(fetchPatients)
</script>

<template>
  <div class="patient-view">
    <n-card size="small" class="patient-panel">
      <div class="toolbar">
        <n-input v-model:value="keyword" clearable :placeholder="$t('patientView.searchPlaceholder')" size="small" />
        <n-button type="primary" size="small" @click="openCreate"> {{ $t('patientView.newPatientButtonText') }}
        </n-button>
      </div>

      <div class="table-wrapper">
        <!-- Use computed columns -->
        <n-data-table class="data-table" :columns="columns" :data="filteredPatients" :loading="loading"
          :pagination="{ pageSize: 10, simple: true }" :scroll-x="1000" size="small" bordered />
      </div>
    </n-card>

    <PatientProfilePopup :show="showViewPopup" @update:show="showViewPopup = $event" :patient-data="viewPopupData" />

    <!-- <ItemViewPopup :show="showViewPopup" @update:show="showViewPopup = $event" :item-data="viewPopupData"
      title="Patient Details" /> -->

    <n-modal v-model:show="showEditor" preset="card" style="width: 600px"
      :title="isEditing ? $t('patientView.modal.editTitle') : $t('patientView.modal.newTitle')" class="patient-modal">
      <n-form label-width="110">
        <!-- <div class="profile-upload-row">
          <div class="profile-image-container" @click="triggerFileInput">
            <input type="file" ref="fileInputRef" style="display: none" accept="image/*" @change="handleFileChange" />
            <n-avatar v-if="profileImage" :size="100" round :src="profileImage" />
            <div v-else class="profile-placeholder">
              <Icon icon="mdi:account" width="64" height="64" color="#ccc" />
            </div>
            <div class="upload-overlay">
              <Icon icon="mdi:camera" width="24" height="24" color="#fff" />
            </div>
          </div>
        </div> -->

        <div class="form-row">
          <n-form-item :label="$t('patientView.form.firstNameLabel')">
            <n-input v-model:value="formModel.fName" :placeholder="$t('patientView.form.firstNamePlaceholder')" />
          </n-form-item>
          <n-form-item :label="$t('patientView.form.lastNameLabel')">
            <n-input v-model:value="formModel.lName" :placeholder="$t('patientView.form.lastNamePlaceholder')" />
          </n-form-item>
        </div>

        <div class="form-row">
          <n-form-item :label="$t('patientView.form.genderLabel')">
            <n-select :to="false" v-model:value="formModel.gender" :options="genderOptions"
              :placeholder="$t('patientView.form.genderPlaceholder')" />
          </n-form-item>
          <n-form-item :label="$t('patientView.form.bloodTypeLabel')">
            <n-select :to="false" v-model:value="formModel.bloodType" :options="bloodTypeOptions"
              :placeholder="$t('patientView.form.bloodTypePlaceholder')" />
          </n-form-item>
        </div>

        <div class="form-row">
          <n-form-item :label="$t('patientView.form.phoneLabel')">
            <n-input v-model:value="formModel.phone" :placeholder="$t('patientView.form.phonePlaceholder')" />
          </n-form-item>
          <n-form-item :label="$t('patientView.form.emergencyContactLabel')">
            <n-input v-model:value="formModel.emgContact"
              :placeholder="$t('patientView.form.emergencyContactPlaceholder')" />
          </n-form-item>
        </div>

        <div class="form-row">
          <n-form-item :label="$t('patientView.form.registeredAtLabel')">
            <n-date-picker :to="false" type="date" size="small" style="width: 100%"
              :value="formModel.registerationDate ? Date.parse(formModel.registerationDate) : null"
              @update:value="handleDateChange" />
          </n-form-item>
        </div>

        <div class="form-actions">
          <p style="margin-right: auto;" class="reception-fee">Reception Fee: <span class="digit"
              style="font-weight: bold; color:green"> {{ userStore.settings.reception_cost || 0 }} AFN</span></p>
          <n-button size="small" @click="showEditor = false"> {{ $t('common.cancelButtonText') }} </n-button>
          <n-button type="primary" size="small" :loading="submitting" @click="handleSubmit">
            {{ $t('common.saveButtonText') }}
          </n-button>
        </div>
      </n-form>
    </n-modal>
  </div>
</template>

<style scoped>
.patient-view {
  padding: 16px 20px;
  display: flex;
  flex-direction: column;
  width: 100%;
  min-height: 100%;
  box-sizing: border-box;
}

.patient-panel {
  width: 100%;
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: column;
}

.patient-panel :deep(.n-card__content) {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-height: 0;
}

.patient-panel :deep(.n-data-table) {
  flex: 1;
  min-width: 0;
}

.table-wrapper {
  flex: 1;
  min-width: 0;
  overflow-x: auto;
}

.table-wrapper :deep(.n-data-table-table) {
  min-width: 1000px;
}

.toolbar {
  display: flex;
  gap: 8px;
  margin-bottom: 12px;
  flex-shrink: 0;
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

.profile-upload-row {
  display: flex;
  justify-content: center;
  margin-bottom: 24px;
}

.profile-image-container {
  position: relative;
  width: 100px;
  height: 100px;
  border-radius: 50%;
  border: 2px dashed #ccc;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  transition: border-color 0.3s;
}

.profile-image-container:hover {
  border-color: #4f46e5;
}

.profile-image-container:hover .upload-overlay {
  opacity: 1;
}

.upload-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.3s;
}

.profile-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>
