<script setup lang="ts">
import { ref, reactive, computed, onMounted, h, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
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
  NTimePicker,
  NInputNumber,
  NAvatar,
  NTag,
  NStatistic,
  NGrid,
  NGi,
  NSpin,
  useMessage,
  dateArDZ,
  type DataTableColumn,
} from 'naive-ui'
// import { Pencil, Trash, CurrencyDollar } from '@vicons/ionicons5'

import employeeApi from '@api/employee';
import type EmployeeData from '@api/interfaces/employee';
import userApi from '@api/user'
import accountApi from '@api/account'

import { Icon } from '@iconify/vue';
import EmployeeProfilePopup from '../components/EmployeeProfilePopup.vue';


type EmployeeRow = EmployeeData & { id?: number }

const message = useMessage()
const route = useRoute()
const { t } = useI18n()

const getEffectiveBranchId = (): number | undefined => {
  const usr = JSON.parse(localStorage.getItem('user') || 'null')
  const userBranchId = usr?.user?.employee?.branchId
  if (typeof userBranchId === 'number' && Number.isFinite(userBranchId)) return userBranchId

  const raw = route.query.branchId
  const fromQuery = typeof raw === 'string' ? Number(raw) : NaN
  return Number.isFinite(fromQuery) ? fromQuery : undefined
}

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

const showPayModal = ref(false)
const payingEmployee = ref<EmployeeRow | null>(null)
const accounts = ref<{ label: string; value: number }[]>([])
const salaries = ref<any[]>([]) // Adjusted to any[] to avoid missing type errors, matches original logic
const salariesLoading = ref(false)


function timeToTimestamp(time: string) {
  const [h, m] = time.split(':').map(Number)
  const d = new Date()
  d.setHours(h, m, 0, 0)
  return d.getTime()
}

const salaryForm = reactive({
  salaryMonth: null as number | null, // month timestamp
  amount: 0,
  bonus: 0,
  totalAmount: 0,
  remark: '',
  accountId: null as number | null,
})

const formModel = reactive<EmployeeRow>({
  fName: '',
  lName: '',
  email: '',
  phone: '',
  gender: '',
  hireDate: null as null | string,
  speciality: '',
  qualification: '',
  midLicenseNum: '',
  workStartTime: null as number | null,
  workEndTime: null as number | null,
  position: '',
  positionId: 0 as unknown as number,
  experience: {
    workplace: '',
    position: '',
    totalAmount: 0 as unknown as number,
  },
})


function formatMoney(value: number | string | null | undefined) {
  const n = Number(value ?? 0)
  return n.toLocaleString(undefined, {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  })
}

function formatSalaryMonth(value: string) {
  if (!value) return '-'
  const normalized = value.slice(0, 7)
  const [year, month] = normalized.split('-').map(Number)
  if (!year || !month) return value
  const d = new Date(year, month - 1, 1)
  return d.toLocaleString(undefined, { month: 'short', year: 'numeric' })
}

function loadSalaries() {
  return fetchSalaries()
}


function safeTime(value: string | number | null | undefined) {
  if (!value) return null
  if (typeof value === 'number') return value
  return timeToTimestamp(value)
}

function timestampToTime(ts: number | null) {
  if (!ts) return ''
  const d = new Date(ts)
  const h = String(d.getHours()).padStart(2, '0')
  const m = String(d.getMinutes()).padStart(2, '0')
  return `${h}:${m}`
}

const genderOptions = [
  { label: t('employeeView.genderOptions.male'), value: 'male' },
  { label: t('employeeView.genderOptions.female'), value: 'female' },
]

const positionOptions = [
  { label: t('employeeView.positionOptions.doctor'), value: '1' },
  { label: t('employeeView.positionOptions.doctorAssistant'), value: '2' },
  { label: t('employeeView.positionOptions.receptionist'), value: '3' },
]

const filteredEmployees = computed(() => {
  if (!keyword.value.trim()) return employees.value
  const k = keyword.value.trim().toLowerCase()
  return employees.value.filter((e: EmployeeRow) =>
    [e.name, e.email, e.phone, e.speciality].some((field) =>
      String(field || '')
        .toLowerCase()
        .includes(k),
    ),
  )
})

const columns = [
  {
    title: t('employeeView.columns.name'),
    key: 'name',
    ellipsis: { tooltip: true },
  },
  {
    title: t('employeeView.columns.email'),
    key: 'email',
    ellipsis: { tooltip: true },
  },
  {
    title: t('employeeView.columns.phone'),
    key: 'phone',
    ellipsis: { tooltip: true },
    render(row: EmployeeRow) {
      return row.phone || '-'
    },
  },
  {
    title: t('employeeView.columns.position'),
    key: 'position',
    ellipsis: { tooltip: true },
  },
  {
    title: t('employeeView.columns.workStartTime'),
    key: 'workStartTime',
  },
  {
    title: t('employeeView.columns.workEndTime'),
    key: 'workEndTime',
  },
  {
    title: t('employeeView.columns.actions'),
    key: 'actions',
    render(row: EmployeeRow) {
      return h('div', { style: 'display: flex; gap: 8px; align-items: center;' }, [
        h(
          Icon,
          {
            icon: 'carbon:data-view-alt',
            title: t('employeeView.actions.viewTooltip'),
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
            title: t('employeeView.actions.paySalary'),
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
            title: t('employeeView.actions.deleteTooltip'),
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

const salaryColumns: DataTableColumn<any>[] = [
  {
    title: t('employeeView.salaryColumns.salaryMonth'),
    key: 'salaryMonth',
    render(row) {
      return h('div', { class: 'salary-month-cell' }, [
        h(Icon, {
          icon: 'mdi:calendar-month',
          class: 'cell-icon'
        }),
        h('span', { class: 'salary-month-text' }, row.salaryMonth)
      ])
    }
  },
  {
    title: t('employeeView.salaryColumns.paidTo'),
    key: 'employee',
    render(row) {
      return h('span', { class: 'money-base' }, row.employee)
    },
  },
  {
    title: t('employeeView.salaryColumns.total'),
    key: 'totalAmount',
    render(row) {
      return h('span', { class: 'money-total' }, formatMoney(row.totalAmount))
    },
  },
  {
    title: t('employeeView.salaryColumns.remark'),
    key: 'remark',
    ellipsis: { tooltip: true },
    render(row) {
      return row.remark || '-'
    },
  },
  {
    title: t('employeeView.salaryColumns.account'),
    key: 'accountName',
    render(row) {
      return h('div', { class: 'account-cell' }, [
        h(Icon, {
          icon: 'mdi:bank',
          class: 'cell-icon'
        }),
        h('span', { class: 'account-text' }, row.accountName || '—')
      ])
    }
  },
]

function resetSalaryForm() {
  salaryForm.salaryMonth = null
  salaryForm.amount = 0
  salaryForm.bonus = 0
  salaryForm.totalAmount = 0
  salaryForm.remark = ''
  salaryForm.accountId = null
}

async function openPayDialog(row: EmployeeRow) {
  payingEmployee.value = row
  resetSalaryForm()
  await loadAccounts()
  if (accounts.value.length > 0) {
    salaryForm.accountId = accounts.value[0].value
  }
  showPayModal.value = true
}

function formatMonth(ts: number | null) {
  if (!ts) return ''
  const d = new Date(ts)
  const year = d.getFullYear()
  const month = String(d.getMonth() + 1).padStart(2, '0')
  return `${year}-${month}`
}

async function submitSalary() {
  if (!payingEmployee.value?.id) return
  try {
    const payload = {
      salaryMonth: formatMonth(salaryForm.salaryMonth),
      amount: Number(salaryForm.amount),
      bonus: Number(salaryForm.bonus),
      totalAmount: Number(salaryForm.totalAmount),
      remark: salaryForm.remark,
      accountId: salaryForm.accountId,
    }
    await employeeApi.paySalary(payingEmployee.value.id, payload)
    message.success(t('employeeView.messages.salaryPaidSuccess'))
    showPayModal.value = false
    fetchSalaries()
  } catch (e) {
    message.error(t('employeeView.messages.paySalaryError'))
  }
}

async function fetchSalaries() {
  try {
    salariesLoading.value = true
    const { data } = await employeeApi.getBranchSalaries()
    const rows = Array.isArray(data?.data) ? data.data : Array.isArray(data) ? data : []
    salaries.value = rows
  } catch (error) {
    message.error(t('employeeView.messages.loadSalariesError'))
  } finally {
    salariesLoading.value = false
  }
}

const salarySummary = computed(() => {
  const count = salaries.value.length
  const totalPaid = salaries.value.reduce((sum, row) => sum + Number(row.totalAmount || 0), 0)
  const totalBonus = salaries.value.reduce((sum, row) => sum + Number(row.bonus || 0), 0)
  const totalBase = salaries.value.reduce((sum, row) => sum + Number(row.amount || 0), 0)
  return { count, totalPaid, totalBonus, totalBase }
})

async function loadAccounts() {
  try {
    const { data } = await accountApi.getBranchAccounts()
    const list = data.data ?? data ?? []
    accounts.value = list.map((acc: any) => ({
      label: acc.accountName ?? 'Unnamed account',
      value: acc.id,
    }))
  } catch (e) {
    message.error(t('employeeView.messages.loadAccountsError'))
  }
}

function resetForm() {
  formModel.fName = ''
  formModel.lName = ''
  formModel.email = ''
  formModel.phone = ''
  formModel.gender = ''
  formModel.hireDate = null // Changed to null
  formModel.speciality = ''
  formModel.qualification = ''
  formModel.midLicenseNum = ''
  formModel.workStartTime = null
  formModel.workEndTime = null
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
    const { data } = await employeeApi.getBranchEmployees(false, getEffectiveBranchId())
    employees.value = data.data as EmployeeRow[]
  } catch (error) {
    message.error(t('employeeView.messages.loadEmployeesError'))
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
  formModel.phone = String(row.phone || '')
  formModel.gender = String(row.gender || '')
  formModel.hireDate = String(row.hireDate || null)
  formModel.speciality = String(row.speciality || '')
  formModel.qualification = String(row.qualification || '')
  formModel.midLicenseNum = String(row.midLicenseNum || '')
  formModel.workStartTime = safeTime(row.workStartTime as any)
  formModel.workEndTime = safeTime(row.workEndTime as any)
  formModel.positionId = (row as any).positionId
  formModel.experience.workplace = String(row.experience?.workplace || '')
  formModel.experience.position = String(row.experience?.position || '')
  formModel.experience.totalAmount = (row.experience?.totalAmount as any) ?? 0
  profileImage.value = null
  profileFile.value = null
  showEditor.value = true
}

async function handleDelete(row: EmployeeRow) {
  const id = (row as any).id
  if (!id) return message.error(t('employeeView.messages.missingEmployeeIdError'))
  try {
    await employeeApi.deleteEmployee(id)
    message.success(t('employeeView.messages.employeeDeletedSuccess'))
    fetchEmployees()
  } catch (error) {
    message.error(t('employeeView.messages.deleteEmployeeError'))
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
    reader.onload = (e) => { profileImage.value = e.target?.result as string }
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
      phone: formModel.phone,
      gender: formModel.gender,
      hireDate: formModel.hireDate,
      speciality: formModel.speciality,
      qualification: formModel.qualification,
      midLicenseNum: formModel.midLicenseNum,
      workStartTime: timestampToTime(formModel.workStartTime as any),
      workEndTime: timestampToTime(formModel.workEndTime as any),
      position: '',
      positionId: formModel.positionId,
      experience: { ...formModel.experience },
    }

    console.log(payload)

    let employeeId = editingId.value
    if (isEditing.value && employeeId != null) {
      await employeeApi.updateEmployee(employeeId, payload)
      message.success(t('employeeView.messages.employeeUpdatedSuccess'))
    } else {
      const { data } = await employeeApi.postEmployee(payload)
      message.success(t('employeeView.messages.employeeCreatedSuccess'))
      employeeId = (data as any)?.employeeId
      const created: any = data ?? {}
      if (created.token && created.email) {
        inviteEmail.value = String(created.email)
        inviteToken.value = String(created.token)
        inviteLink.value = `http://localhost:1234/reset-password/?token=${encodeURIComponent(inviteToken.value)}`
        showInvite.value = true
      }
    }

    if (profileFile.value && employeeId != null) {
      try {
        await employeeApi.updateEmployeeProfilePicture(employeeId, profileFile.value)
        profileFile.value = null
      } catch (imgError) {
        message.warning(t('employeeView.messages.profilePictureFailed'))
      }
    }
    showEditor.value = false
    await fetchEmployees()
  } catch (error) {
    message.error(t('employeeView.messages.saveEmployeeError'))
  } finally {
    submitting.value = false
  }
}

function handleHireDateChange(value: number | null) {
  console.log('hire date value:', value)

  if (!value) {
    formModel.hireDate = null
    return
  }

  const date = new Date(value)

  const y = date.getFullYear()
  const m = String(date.getMonth() + 1).padStart(2, '0')
  const d = String(date.getDate()).padStart(2, '0')

  formModel.hireDate = `${y}-${m}-${d}`

  console.log(formModel.hireDate)
}

function handlePositionChange(value: number) {
  formModel.positionId = value
}

function ViewEmployee(row: EmployeeRow) {
  viewPopupData.value = row;
  showViewPopup.value = true;
}

async function copyInviteLink() {
  if (!inviteLink.value) return
  try {
    await navigator.clipboard.writeText(inviteLink.value)
    message.success(t('employeeView.messages.copyLinkSuccess'))
  } catch (error) {
    message.error(t('employeeView.messages.copyLinkError'))
  }
}

async function sendInviteWhatsapp() {
  if (!inviteEmail.value || !inviteToken.value) return
  try {
    await userApi.sendTokenViaEmail({ email: inviteEmail.value, token: inviteToken.value })
    message.success(t('employeeView.messages.inviteEmailSentSuccess'))
  } catch (error) {
    message.error(t('employeeView.messages.sendEmailError'))
  }
}

watch(() => [salaryForm.amount, salaryForm.bonus], () => {
  salaryForm.totalAmount = Number(salaryForm.amount || 0) + Number(salaryForm.bonus || 0)
})

onMounted(() => {
  fetchEmployees()
  fetchSalaries()
})
</script>

<template>
  <div class="employee-view">
    <n-card size="huge" class="employee-panel">
      <div class="employees-hero">
        <div class="employees-hero__left" style="margin-top: -3em;">
          <div class="employees-hero__icon">
            <Icon icon="mdi:account-group" width="28" />
          </div>
          <div>
            <!-- <div class="employees-hero__eyebrow">Clinic management</div> -->
            <h2 class="employees-hero__title">{{ t('employeeView.hero.title') }}</h2>
            <p class="employees-hero__subtitle">
              {{ t('employeeView.hero.subtitle') }}
            </p>
          </div>
        </div>

        <div class="employees-hero__right">
          <div class="employees-hero__stats">
            <div class="hero-stat">
              <Icon icon="mdi:account-multiple-outline" width="18" />
              <div>
                <span class="hero-stat__label">{{ t('employeeView.stats.total') }}</span>
                <strong class="hero-stat__value">{{ employees.length }}</strong>
              </div>
            </div>
            <!-- <div class="hero-stat">
              <Icon icon="mdi:cash-multiple" width="18" />
              <div>
                <span class="hero-stat__label">Payroll</span>
                <strong class="hero-stat__value">Active</strong>
              </div>
            </div> -->
          </div>

          <div class="employees-hero__actions">
            <n-input v-model:value="keyword" clearable :placeholder="t('employeeView.searchPlaceholder')" class="employees-hero__search">
              <template #prefix>
                <Icon icon="mdi:magnify" width="18" />
              </template>
            </n-input>
            <div class="button-row">
              <n-button quaternary class="employees-hero__ghost-btn" @click="fetchEmployees">
                <Icon icon="mdi:refresh" width="18" />
              </n-button>
              <n-button type="primary" class="employees-hero__primary-btn" @click="openCreate">
                <template #icon>
                  <Icon icon="mdi:account-plus" width="18" />
                </template>
                <span class="btn-text">{{ t('employeeView.newEmployeeButtonText') }}</span>
              </n-button>
            </div>
          </div>
        </div>
      </div>

      <div class="table-wrapper">
        <n-data-table :columns="columns" :data="filteredEmployees" :loading="loading"
          :pagination="{ pageSize: 10, simple: true }" :scroll-x="1200" size="small" bordered />
      </div>
    </n-card>

    <n-card size="huge" class="salary-panel">
      <div class="section-head">
        <div class="section-title">
          <div class="section-icon-wrap">
            <Icon icon="solar:money-bag-broken" width="22" />
          </div>
          <div>
            <h3>{{ t('employeeView.salaryPanel.title') }}</h3>
            <p>{{ t('employeeView.salaryPanel.subtitle') }}</p>
          </div>
        </div>
        <n-button quaternary circle @click="fetchSalaries" :loading="salariesLoading">
          <Icon icon="solar:refresh-bold" width="18" />
        </n-button>
      </div>

      <n-grid cols="1 s:2 m:3" responsive="screen" x-gap="12" y-gap="12" class="salary-stats">
        <n-gi>
          <div class="stat-card stat-card-primary">
            <div class="stat-top">
              <Icon icon="mdi:cash-multiple" width="20" /><span>{{ t('employeeView.salaryPanel.records') }}</span>
            </div>
            <div class="stat-value">{{ salarySummary.count }}</div>
          </div>
        </n-gi>
        <n-gi>
          <div class="stat-card">
            <div class="stat-top">
              <Icon icon="mdi:wallet-outline" width="20" /><span>{{ t('employeeView.salaryPanel.basePay') }}</span>
            </div>
            <div class="stat-value">{{ formatMoney(salarySummary.totalBase) }}</div>
          </div>
        </n-gi>
        <n-gi>
          <div class="stat-card stat-card-accent">
            <div class="stat-top">
              <Icon icon="mdi:gift-outline" width="20" /><span>{{ t('employeeView.salaryPanel.totalBonus') }}</span>
            </div>
            <div class="stat-value">{{ formatMoney(salarySummary.totalBonus) }}</div>
          </div>
        </n-gi>
      </n-grid>

      <div class="salary-table-wrap">
        <n-data-table :columns="salaryColumns" :data="salaries" :loading="salariesLoading"
          :pagination="{ pageSize: 8, simple: true }" size="small" bordered striped :scroll-x="900" />
      </div>

      <div class="salary-footer">
        <div class="salary-footer-item">
          <Icon icon="mdi:cash-check" width="18" /><span>{{ t('employeeView.salaryPanel.totalPaid', { amount: formatMoney(salarySummary.totalPaid) }) }}</span>
        </div>
        <div class="salary-footer-item">
          <Icon icon="mdi:calendar-month-outline" width="18" /><span>{{ t('employeeView.salaryPanel.payments', { count: salarySummary.count }) }}</span>
        </div>
      </div>
    </n-card>

    <EmployeeProfilePopup v-if="showViewPopup" v-model:show="showViewPopup" :employee-data="viewPopupData" />
    <n-modal content-scrollable style="max-width: 600px; max-height: 90vh;" v-model:show="showEditor" preset="card"
      :title="isEditing ? t('employeeView.modal.editTitle') : t('employeeView.modal.newTitle')" class="responsive-modal">
      <n-form label-placement="top">
        <div class="profile-upload-row">
          <div class="profile-image-container" @click="triggerFileInput">
            <input type="file" ref="fileInputRef" style="display: none" accept="image/*" @change="handleFileChange" />
            <n-avatar v-if="profileImage" :size="100" round :src="profileImage" />
            <Icon v-else icon="mdi:account-circle" width="80" height="80" color="#ccc" />
          </div>
        </div>

        <div class="form-row dual">
          <n-form-item :label="t('employeeView.form.emailLabel')"><n-input v-model:value="formModel.email" :placeholder="t('employeeView.form.emailPlaceholder')" /></n-form-item>
          <n-form-item :label="t('employeeView.form.phoneLabel')"><n-input v-model:value="formModel.phone" :placeholder="t('employeeView.form.phonePlaceholder')" /></n-form-item>
        </div>

        <div class="form-row dual">
          <n-form-item :label="t('employeeView.form.hireDateLabel')">

            <n-date-picker :to="false" type="date" size="small" style="width: 100%" :value="formModel.hireDate
                ? new Date(formModel.hireDate + 'T00:00:00').getTime()
                : null
              " @update:value="handleHireDateChange" />

          </n-form-item>
          <n-form-item :label="t('employeeView.form.firstNameLabel')"><n-input v-model:value="formModel.fName" :placeholder="t('employeeView.form.firstNamePlaceholder')" /></n-form-item>
        </div>

        <div class="form-row dual">
          <n-form-item :label="t('employeeView.form.lastNameLabel')"><n-input v-model:value="formModel.lName" :placeholder="t('employeeView.form.lastNamePlaceholder')" /></n-form-item>
          <n-form-item :label="t('employeeView.form.qualificationLabel')"><n-input v-model:value="formModel.qualification" :placeholder="t('employeeView.form.qualificationPlaceholder')" /></n-form-item>
        </div>

        <div class="form-row">
          <n-form-item :label="t('employeeView.form.specialityLabel')"><n-input v-model:value="formModel.speciality" :placeholder="t('employeeView.form.specialityPlaceholder')" /></n-form-item>
        </div>

        <div class="form-row">
          <n-form-item :label="t('employeeView.form.medicalLicenseNumberLabel')"><n-input v-model:value="formModel.midLicenseNum" :placeholder="t('employeeView.form.medicalLicenseNumberPlaceholder')" /></n-form-item>
        </div>

        <div class="form-row dual">
          <n-form-item :label="t('employeeView.form.genderLabel')"><n-select :to="false" v-model:value="formModel.gender"
              :options="genderOptions" :placeholder="t('employeeView.form.genderPlaceholder')" /></n-form-item>
          <n-form-item :label="t('employeeView.form.positionLabel')">
            <n-select :to="false" :options="positionOptions" @update:value="handlePositionChange"
              :value="positionOptions[(formModel.positionId as number) - 1]?.value" :placeholder="t('employeeView.form.positionPlaceholder')" />
          </n-form-item>
        </div>

        <div class="form-row dual">
          <n-form-item :label="t('employeeView.form.workStartTimeLabel')"><n-time-picker v-model:value="formModel.workStartTime"
              format="HH:mm" :placeholder="t('employeeView.form.workStartTimePlaceholder')" /></n-form-item>
          <n-form-item :label="t('employeeView.form.workEndTimeLabel')"><n-time-picker v-model:value="formModel.workEndTime"
              format="HH:mm" :placeholder="t('employeeView.form.workEndTimePlaceholder')" /></n-form-item>
        </div>
<!--
        <div class="form-row dual">
          <n-form-item :label="t('employeeView.form.experienceWorkplaceLabel')"><n-input v-model:value="formModel.experience.workplace" :placeholder="t('employeeView.form.experienceWorkplacePlaceholder')" /></n-form-item>
          <n-form-item :label="t('employeeView.form.experiencePositionLabel')"><n-input v-model:value="formModel.experience.position" :placeholder="t('employeeView.form.experiencePositionPlaceholder')" /></n-form-item>
        </div>

        <div class="form-row">
          <n-form-item :label="t('employeeView.form.experienceAmountLabel')"><n-input-number v-model:value="formModel.experience.totalAmount" :min="0" :placeholder="t('employeeView.form.experienceAmountPlaceholder')" /></n-form-item>
        </div> -->

        <div class="form-actions">
          <n-button @click="showEditor = false">{{ t('common.cancelButtonText') }}</n-button>
          <n-button type="primary" :loading="submitting" @click="handleSubmit">{{ t('common.saveButtonText') }}</n-button>
        </div>
      </n-form>
    </n-modal>

    <n-modal v-model:show="showPayModal" style="max-width: 600px;" preset="card" :title="t('employeeView.payModal.title')"
      class="responsive-modal small">
      <div class="payroll-container">
        <div class="payroll-header">
          <Icon icon="mdi:account-cash" width="22" /><strong>{{ payingEmployee?.name }}</strong>
        </div>
        <n-form-item :label="t('employeeView.payModal.salaryMonthLabel')"><n-date-picker :to="false" type="month" v-model:value="salaryForm.salaryMonth"
            style="width: 100%" /></n-form-item>
        <div class="form-row dual">
          <n-form-item :label="t('employeeView.payModal.amountLabel')"><n-input-number v-model:value="salaryForm.amount" :min="0" /></n-form-item>
          <n-form-item :label="t('employeeView.payModal.bonusLabel')"><n-input-number v-model:value="salaryForm.bonus" :min="0" /></n-form-item>
        </div>
        <n-form-item :label="t('employeeView.payModal.payFromAccountLabel')"><n-select :to="false" v-model:value="salaryForm.accountId"
            :options="accounts" /></n-form-item>
        <n-form-item :label="t('employeeView.payModal.remarksLabel')"><n-input v-model:value="salaryForm.remark" type="textarea" /></n-form-item>
        <div class="form-actions">
          <n-button @click="showPayModal = false">{{ t('common.cancelButtonText') }}</n-button>
          <n-button type="primary" @click="submitSalary">{{ t('employeeView.payModal.payButton') }}</n-button>
        </div>
      </div>
    </n-modal>

    <n-modal v-model:show="showInvite" style="max-width: 600px;" preset="card" :title="t('employeeView.inviteModal.title')"
      class="responsive-modal">
      <div class="invite-container">
        <div class="invite-header">
          <Icon icon="mdi:mail-send" width="24" />
          <div>
            <h3>{{ t('employeeView.inviteModal.sentTitle') }}</h3>
            <p>{{ t('employeeView.inviteModal.sentCopy') }}</p>
          </div>
        </div>

        <div class="invite-section">
          <label class="invite-label">{{ t('employeeView.inviteModal.emailLabel') }}</label>
          <div class="invite-field">
            <n-input :value="inviteEmail" readonly />
          </div>
        </div>

        <div class="invite-section">
          <label class="invite-label">{{ t('employeeView.inviteModal.linkLabel') }}</label>
          <div class="invite-field">
            <n-input :value="inviteLink" readonly />
            <n-button ghost type="primary" @click="copyInviteLink" class="copy-btn">
              <template #icon>
                <Icon icon="mdi:content-copy" />
              </template>
              {{ t('employeeView.inviteModal.copyButton') }}
            </n-button>
          </div>
        </div>

        <div class="invite-note">
          <Icon icon="mdi:information-outline" />
          <p>{{ t('employeeView.inviteModal.note') }}</p>
        </div>

        <!-- <div class="form-actions">
          <n-button @click="showInvite = false">Close</n-button>
          <n-button type="primary" @click="sendInviteWhatsapp">
            <template #icon>
              <Icon icon="mdi:email-send" />
            </template>
            Send via Email
          </n-button>
        </div> -->
      </div>
    </n-modal>
  </div>
</template>

<style scoped>
.employee-view {
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  max-width: 1600px;
  margin: 0 auto;
}

.employee-panel,
.salary-panel {
  border-radius: 18px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
}

/* Hero Section */
.employees-hero {
  display: flex;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1.5rem;
  padding-bottom: 1.5rem;
}

.employees-hero__left {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.employees-hero__icon {
  width: 50px;
  height: 50px;
  background: #f0f7ff;
  color: #2563eb;
  border-radius: 12px;
  display: grid;
  place-items: center;
  flex-shrink: 0;
}

.employees-hero__title {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 800;
}

.employees-hero__subtitle {
  margin: 0.25rem 0 0;
  color: #64748b;
  font-size: 0.875rem;
}

.employees-hero__right {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  flex: 1;
  min-width: 300px;
}

.employees-hero__stats {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
}

.hero-stat {
  background: white;
  border: 1px solid #e2e8f0;
  padding: 0.5rem 1rem;
  border-radius: 10px;
  display: flex;
  gap: 0.5rem;
  align-items: center;
  min-width: 110px;
}

.hero-stat__label {
  font-size: 0.7rem;
  color: #64748b;
  text-transform: uppercase;
}

.hero-stat__value {
  font-weight: 700;
  display: block;
}

.employees-hero__actions {
  display: flex;
  gap: 0.5rem;
  width: 100%;
}

.button-row {
  display: flex;
  gap: 0.5rem;
}

.employees-hero__search {
  flex: 1;
}

/* Salary Section */
.section-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.section-title {
  display: flex;
  gap: 0.75rem;
  align-items: center;
}

.section-icon-wrap {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: #1e293b;
  color: white;
  display: grid;
  place-items: center;
}

.stat-card {
  padding: 1rem;
  border-radius: 14px;
  background: #f8fafc;
  border: 1px solid #f1f5f9;
}

.stat-card-primary {
  background: #eff6ff;
  color: #1d4ed8;
}

.stat-card-accent {
  background: #fdf2f8;
  color: #be185d;
}

.stat-top {
  font-size: 0.75rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.stat-value {
  font-size: 1.25rem;
  font-weight: 800;
}

/* Modal & Forms */
.form-row.dual {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.profile-upload-row {
  display: flex;
  justify-content: center;
  margin-bottom: 1rem;
}

.profile-image-container {
  cursor: pointer;
  border: 2px dashed #cbd5e1;
  border-radius: 50%;
  padding: 4px;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  margin-top: 1.5rem;
}

:deep(.salary-month-cell),
:deep(.account-cell) {
  display: flex;
  align-items: center;
  /* 🔥 vertical alignment fix */
  gap: 8px;
}

/* icon styling */
:deep(.cell-icon) {
  font-size: 18px;
  color: #64748b;
  flex-shrink: 0;
}

/* salary month text */
:deep(.salary-month-text) {
  font-weight: 600;
  /* 🔥 bold fix */
  color: #0f172a;
}

/* account text */
:deep(.account-text) {
  color: #334155;
}

div.salary-footer-item {
  display: flex;
  align-items: center;
  gap: .5em;
}

/* Tablet & Mobile Breakpoints */
@media (max-width: 900px) {
  .employees-hero {
    flex-direction: column;
    align-items: stretch;
  }

  .employees-hero__stats {
    justify-content: flex-start;
  }

  .employees-hero__right {
    min-width: 100%;
  }
}

@media (max-width: 600px) {
  .employees-hero__actions {
    flex-direction: column;
  }

  .employees-hero__stats {
    flex-wrap: wrap;
  }

  .hero-stat {
    flex: 1;
  }

  .form-row.dual {
    grid-template-columns: 1fr;
    gap: 0;
  }

  .btn-text {
    display: none;
  }

  .responsive-modal {
    width: 95% !important;
  }
}

.table-wrapper {
  overflow-x: auto;
  width: 100%;
  border-radius: 12px;
}

.salary-footer {
  display: flex;
  justify-content: space-between;
  margin-top: 1rem;
  color: #64748b;
  font-size: 0.8rem;
}

:deep(.n-data-table-wrapper) {
  margin-top: 1em !important
}

/* Invite Modal Styles */
.invite-container {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.invite-header {
  display: flex;
  gap: 1rem;
  align-items: flex-start;
  padding: 1rem;
  background: #f0f7ff;
  border-radius: 12px;
  color: #1d4ed8;
}

.invite-header h3 {
  margin: 0 0 0.25rem 0;
  font-size: 1.125rem;
  font-weight: 700;
}

.invite-header p {
  margin: 0;
  font-size: 0.875rem;
  color: #475569;
}

.invite-section {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.invite-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #334155;
  text-transform: uppercase;
}

.invite-field {
  display: flex;
  gap: 0.5rem;
  align-items: stretch;
}

.invite-field :deep(.n-input) {
  flex: 1;
}

.copy-btn {
  white-space: nowrap;
}

.invite-note {
  display: flex;
  gap: 0.75rem;
  align-items: flex-start;
  padding: 1rem;
  background: #fffbeb;
  border-left: 3px solid #f59e0b;
  border-radius: 8px;
  color: #92400e;
  font-size: 0.875rem;
}

.invite-note p {
  margin: 0;
}

.invite-note :deep(.iconify) {
  flex-shrink: 0;
  margin-top: 0.125rem;
}
</style>
