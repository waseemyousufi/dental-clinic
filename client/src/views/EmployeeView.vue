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
  useMessage,
  dateArDZ,
  type DataTableColumn,
  type DataTableBaseColumn,
  NIcon,
  NAvatar,
} from 'naive-ui'
// import { Pencil, Trash, CurrencyDollar } from '@vicons/ionicons5'

import employeeApi from '@api/employee';
import type EmployeeData from '@api/interfaces/employee';
import userApi from '@api/user'

import { Icon } from '@iconify/vue';
import EmployeeProfilePopup from '../components/EmployeeProfilePopup.vue';


type EmployeeRow = EmployeeData & { id?: number }

const message = useMessage()

const loading = ref(false)
const employees = ref<EmployeeRow[]>([])
const keyword = ref('')

const showEditor = ref(false)
const submitting = ref(false)
const isEditing = ref(false)
const editingId = ref<number | null>(null)

const showInvite = ref(false)
const inviteEmail = ref('')
const inviteToken = ref('')
const inviteLink = ref('')

const showViewPopup = ref(false);
const viewPopupData = ref<EmployeeRow | null>(null);

const profileImage = ref<string | null>(null)
const profileFile = ref<File | null>(null)
const fileInputRef = ref<HTMLInputElement | null>(null)

const formModel = reactive<EmployeeRow>({
  fName: '',
  lName: '',
  gender: '',
  hireDate: '',
  speciality: '',
  qualification: '',
  midLicenseNum: '',
  workStartTime: '',
  workEndTime: '',
  position: '',
  positionId: 0 as unknown as number,
  experience: {
    workplace: '',
    position: '',
    totalAmount: 0 as unknown as number,
  },
})

const genderOptions = [
  { label: 'Male', value: 'male' },
  { label: 'Female', value: 'female' },
]

const positionOptions = [
  { label: 'Doctor', value: '1' },
  { label: 'Doctor Assisstant', value: '2' },
  { label: 'receptionist', value: '3' },
]

const filteredEmployees = computed(() => {
  if (!keyword.value.trim()) return employees.value
  const k = keyword.value.trim().toLowerCase()
  return employees.value.filter((e: EmployeeRow) =>
    [e.name, e.email, e.speciality].some((field) =>
      String(field || '')
        .toLowerCase()
        .includes(k),
    ),
  )
})

const columns = [
  {
    title: 'Name',
    key: 'name',
    ellipsis: { tooltip: true },
  },
  {
    title: 'Email',
    key: 'email',
    ellipsis: { tooltip: true },
  },
  {
    title: 'Position',
    key: 'position',
    ellipsis: { tooltip: true },
  },
  {
    title: 'Work start',
    key: 'workStartTime',
  },
  {
    title: 'Work end',
    key: 'workEndTime',
  },
  {
    title: 'Actions',
    key: 'actions',
    render(row: EmployeeRow) {
      return h('div', { style: 'display: flex; gap: 8px; align-items: center;' }, [
        // h(
        //   Icon,
        //   {
        //     icon: 'akar-icons:edit',
        //     title: 'Edit',
        //     width: 20,
        //     height: 20,
        //     color: '#4f46e5',
        //     style: {cursor: 'pointer'},
        //     onClick: () => handleEdit(row),
        //   },
        // ),
        h(
          Icon,
          {
            icon: 'carbon:data-view-alt',
            title: 'Pay Salary',
            width: 20,
            height: 20,
            style: { cursor: 'pointer' },
            onClick: () => ViewEmployee(row),
          },
        ),
        h(
          Icon,
          {
            icon: 'glyphs-poly:dollar-bills',
            title: 'Pay Salary',
            width: 20,
            height: 20,
            style: { cursor: 'pointer' },
            onClick: () => openPayDialog(row),
          },
        ),
        h(
          Icon,
          {
            icon: 'fluent:delete-16-filled',
            title: 'Delete',
            width: 20,
            height: 20,
            color: '#dc2626',
            style: { cursor: 'pointer' },
            onClick: () => handleDelete(row),
          },
        ),
      ])
    },
  },
]

function resetForm() {
  formModel.name = ''
  formModel.fName = ''
  formModel.lName = ''
  formModel.email = ''
  formModel.gender = ''
  formModel.hireDate = ''
  formModel.speciality = ''
  formModel.qualification = ''
  formModel.midLicenseNum = ''
  formModel.workStartTime = ''
  formModel.workEndTime = ''
  formModel.positionId = 1 as unknown as number
  formModel.experience.workplace = ''
  formModel.experience.position = ''
  formModel.experience.totalAmount = 0 as unknown as number
  profileImage.value = null
  profileFile.value = null
}

async function fetchEmployees() {
  try {
    loading.value = true
    const { data } = await employeeApi.getBranchEmployees()
    employees.value = data.data as EmployeeRow[]
  } catch (error) {
    console.error(error)
    message.error('Failed to load employees')
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

function handleEdit(row: EmployeeRow) {
  isEditing.value = true
  editingId.value = (row as any).id ?? null

  formModel.name = String(row.name || '')
  formModel.fName = String(row.fName || '')
  formModel.lName = String(row.lName || '')
  formModel.email = String(row.email || '')
  formModel.gender = String(row.gender || '')
  formModel.hireDate = String(row.hireDate || '')
  formModel.speciality = String(row.speciality || '')
  formModel.qualification = String(row.qualification || '')
  formModel.midLicenseNum = String(row.midLicenseNum || '')
  formModel.workStartTime = String(row.workStartTime || '')
  formModel.workEndTime = String(row.workEndTime || '')
  formModel.positionId = (row as any).positionId
  formModel.experience.workplace = String(row.experience?.workplace || '')
  formModel.experience.position = String(row.experience?.position || '')
  formModel.experience.totalAmount =
    (row.experience?.totalAmount as any) ?? (0 as unknown as number)

  profileImage.value = null
  profileFile.value = null
  showEditor.value = true
}

async function handleDelete(row: EmployeeRow) {
  const id = (row as any).id
  if (!id) {
    message.error('Missing employee id')
    return
  }
  try {
    await employeeApi.deleteEmployee(id)
    message.success('Employee deleted')
    fetchEmployees()
  } catch (error) {
    console.error(error)
    message.error('Failed to delete employee')
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
    const payload: EmployeeData = {
      name: '',
      fName: formModel.fName,
      lName: formModel.lName,
      email: formModel.email,
      gender: formModel.gender,
      hireDate: formModel.hireDate,
      speciality: formModel.speciality,
      qualification: formModel.qualification,
      midLicenseNum: formModel.midLicenseNum,
      workStartTime: formModel.workStartTime,
      workEndTime: formModel.workEndTime,
      positionId: formModel.positionId,
      experience: {
        workplace: formModel.experience.workplace,
        position: formModel.experience.position,
        totalAmount: formModel.experience.totalAmount,
      },
    }

    let employeeId = editingId.value

    if (isEditing.value && employeeId != null) {
      await employeeApi.updateEmployee(employeeId, payload)
      message.success('Employee updated')
    } else {
      const { data } = await employeeApi.postEmployee(payload)
      message.success('Employee created')
      employeeId = (data as any).id

      const created: any = data ?? {}
      if (created.token && created.email) {
        inviteEmail.value = String(created.email)
        inviteToken.value = String(created.token)
        inviteLink.value = `http://localhost:1235/reset-password/?token=${encodeURIComponent(
          inviteToken.value,
        )}`
        showInvite.value = true
      }
    }

    if (profileFile.value && employeeId != null) {
      await employeeApi.updateProfilePicture(employeeId, profileFile.value)
    }

    showEditor.value = false
    await fetchEmployees()
  } catch (error) {
    console.error(error)
    message.error('Failed to save employee')
  } finally {
    submitting.value = false
  }
}

function handleHireDateChange(value: number | null) {
  if (!value) {
    formModel.hireDate = ''
  } else {
    formModel.hireDate = new Date(value).toISOString().slice(0, 10)
  }
}

function handlePositionChange(value: number) {
  formModel.positionId = value
}

function openPayDialog(row: EmployeeRow) {
  message.info(`Paying salary for ${row.name}`)
}

function ViewEmployee(row: EmployeeRow) {
  viewPopupData.value = row;
  showViewPopup.value = true;
}

async function copyInviteLink() {
  if (!inviteLink.value) return
  try {
    await navigator.clipboard.writeText(inviteLink.value)
    message.success('Link copied to clipboard')
  } catch (error) {
    console.error(error)
    message.error('Failed to copy link')
  }
}

async function sendInviteEmail() {
  if (!inviteEmail.value || !inviteToken.value) return
  try {
    await userApi.sendTokenViaEmail({
      email: inviteEmail.value as unknown as string,
      token: inviteToken.value as unknown as string,
    })
    message.success('Invitation email sent')
  } catch (error) {
    console.error(error)
    message.error('Failed to send email')
  }
}

onMounted(fetchEmployees)
</script>

<template>
  <div class="employee-view">
    <n-card size="huge" class="employee-panel">
      <div class="toolbar">
        <n-input v-model:value="keyword" clearable placeholder="Search by name, email or speciality" size="small" />
        <n-button type="primary" size="small" @click="openCreate"> New Employee </n-button>
      </div>

      <div class="table-wrapper">
        <n-data-table class="data-table" :columns="columns" :data="filteredEmployees" :loading="loading"
          :pagination="{ pageSize: 10, simple: true }" :scroll-x="1200" size="small" bordered />
      </div>
    </n-card>

    <EmployeeProfilePopup :show="showViewPopup" @update:show="showViewPopup = $event" :employee-data="viewPopupData" />

    <n-modal v-model:show="showEditor" content-scrollable style="max-width: 600px; max-height: 90vh; overflow: auto;"
      preset="card" :title="isEditing ? 'Edit Employee' : 'New Employee'" class="employee-modal">
      <n-form label-width="120">
        <div class="profile-upload-row">
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
        </div>

        <div class="form-row">
          <n-form-item label="Email">
            <n-input v-model:value="formModel.email as string" placeholder="Email" />
          </n-form-item>
        </div>

        <div class="form-row">
          <n-form-item label="First name">
            <n-input v-model:value="formModel.fName as string" placeholder="First name" />
          </n-form-item>
          <n-form-item label="Last name">
            <n-input v-model:value="formModel.lName as string" placeholder="Last name" />
          </n-form-item>
        </div>

        <div class="form-row">
          <n-form-item label="Gender">
            <n-select v-model:value="formModel.gender as string" :options="genderOptions" placeholder="Select gender" />
          </n-form-item>
          <n-form-item label="Speciality">
            <n-input v-model:value="formModel.speciality as string" placeholder="e.g. Dentist" />
          </n-form-item>
        </div>

        <div class="form-row">
          <n-form-item label="Qualification">
            <n-input v-model:value="formModel.qualification as string" placeholder="Qualification" />
          </n-form-item>
          <n-form-item label="Medical license #">
            <n-input v-model:value="formModel.midLicenseNum as string" placeholder="License number" />
          </n-form-item>
        </div>

        <div class="form-row">
          <n-form-item label="Work start time">
            <n-input v-model:value="formModel.workStartTime as string" placeholder="e.g. 09:00" />
          </n-form-item>
          <n-form-item label="Work end time">
            <n-input v-model:value="formModel.workEndTime as string" placeholder="e.g. 17:00" />
          </n-form-item>
        </div>

        <div class="form-row">
          <n-form-item label="Hire date">
            <n-date-picker type="date" size="small" style="width: 100%"
              :value="formModel.hireDate ? Date.parse(formModel.hireDate as string) : null"
              @update:value="handleHireDateChange" />
          </n-form-item>
          <n-form-item label="Position">
            <!-- <n-input v-model:value="(formModel.positionId as any)" placeholder="Position ID" /> -->
            <n-select :options="positionOptions" @update:value="handlePositionChange"
              :value="positionOptions[(formModel.positionId as number) - 1]?.label as string" />
          </n-form-item>
        </div>

        <div class="form-row">
          <n-form-item label="Experience workplace">
            <n-input v-model:value="formModel.experience.workplace as string" placeholder="Previous workplace" />
          </n-form-item>
          <n-form-item label="Experience position">
            <n-input v-model:value="formModel.experience.position as string" placeholder="Previous position" />
          </n-form-item>
        </div>

        <div class="form-row">
          <n-form-item label="Experience Amount">
            <n-input v-model:value="formModel.experience.totalAmount as any"
              placeholder="Total experience (e.g. months)" />
          </n-form-item>
        </div>

        <div class="form-actions">
          <n-button size="small" @click="showEditor = false"> Cancel </n-button>
          <n-button type="primary" size="small" :loading="submitting" @click="handleSubmit">
            Save
          </n-button>
        </div>
      </n-form>
    </n-modal>

    <n-modal v-model:show="showInvite" style="max-width: 600px;" preset="card" title="Employee created"
      class="employee-modal">
      <p>
        Employee created, please send them this link for them to get started with clinic management
        software:
      </p>
      <p class="invite-link">
        {{ inviteLink }}
      </p>

      <div class="form-actions">
        <n-button size="small" @click="copyInviteLink"> Copy to clipboard </n-button>
        <n-button type="primary" size="small" @click="sendInviteEmail"> Send via email </n-button>
      </div>
    </n-modal>
  </div>
</template>

<style scoped>
.employee-view {
  /* padding: 16px 20px; */
  display: flex;
  flex-direction: column;
  width: 100%;
  min-height: 100%;
  box-sizing: border-box;
}

.employee-panel {
  width: 100%;
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: column;
}

.employee-panel :deep(.n-card__content) {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-height: 0;
}

.employee-panel :deep(.n-data-table) {
  flex: 1;
  min-width: 0;
}

.table-wrapper {
  flex: 1;
  min-width: 0;
  overflow-x: auto;
}

.table-wrapper :deep(.n-data-table-table) {
  min-width: 1200px;
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

.invite-link {
  margin: 12px 0;
  padding: 8px 10px;
  border-radius: 6px;
  background: #f3f4f6;
  font-family:
    ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New',
    monospace;
  font-size: 0.85rem;
  word-break: break-all;
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
