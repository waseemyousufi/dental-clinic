<template>
  <div class="crud-page">
    <n-card class="page-shell" :bordered="false" size="large">
      <div class="page-header">
        <div>
          <h2 class="title">Branches & Clinic Owners</h2>
          <p class="subtitle">Create, edit, search, and delete records from one clean workspace.</p>
        </div>
      </div>

      <template v-if="hasToken">
        <n-tabs v-model:value="activeTab" type="line" animated class="tabs-root">
          <n-tab-pane name="branches" tab="Branches">
            <template #tab>
              <n-space align="center" :size="8">
                <n-icon size="18"><Icon icon="mdi:office-building-marker-outline" /></n-icon>
                <span>Branches</span>
              </n-space>
            </template>

            <div class="toolbar">
              <n-input
                v-model:value="branchSearch"
                clearable
                round
                placeholder="Search branches..."
                class="search-box"
              >
                <template #prefix>
                  <n-icon><Icon icon="mdi:magnify" /></n-icon>
                </template>
              </n-input>

              <n-button type="primary" round @click="openCreate('branch')">
                <template #icon>
                  <n-icon><Icon icon="mdi:plus" /></n-icon>
                </template>
                Add Branch
              </n-button>
            </div>

            <n-data-table
              remote
              :bordered="false"
              :loading="branchesLoading"
              :columns="branchColumns"
              :data="filteredBranches"
              :pagination="false"
              :row-key="rowKey"
              striped
              class="table-card"
            />
          </n-tab-pane>

          <n-tab-pane name="clinic-owners" tab="Clinic Owners">
            <template #tab>
              <n-space align="center" :size="8">
                <n-icon size="18"><Icon icon="mdi:account-group-outline" /></n-icon>
                <span>Clinic Owners</span>
              </n-space>
            </template>

            <div class="toolbar">
              <n-input
                v-model:value="ownerSearch"
                clearable
                round
                placeholder="Search clinic owners..."
                class="search-box"
              >
                <template #prefix>
                  <n-icon><Icon icon="mdi:magnify" /></n-icon>
                </template>
              </n-input>

              <n-button type="primary" round @click="openCreate('owner')">
                <template #icon>
                  <n-icon><Icon icon="mdi:plus" /></n-icon>
                </template>
                Add Owner
              </n-button>
            </div>

            <n-data-table
              remote
              :bordered="false"
              :loading="ownersLoading"
              :columns="ownerColumns"
              :data="filteredOwners"
              :pagination="false"
              :row-key="rowKey"
              striped
              class="table-card"
            />
          </n-tab-pane>
        </n-tabs>
      </template>

      <template v-else>
        <div class="login-wrap">
          <n-card class="login-card" :bordered="false" size="large">
            <div class="login-icon-wrap">
              <n-icon size="34"><Icon icon="mdi:lock-outline" /></n-icon>
            </div>
            <h3 class="login-title">Enter password</h3>
            <p class="login-subtitle">Authentication is required to access branches and clinic owners.</p>

            <n-form ref="loginFormRef" :model="loginForm" :rules="loginRules" label-placement="top">
              <n-form-item label="Password" path="password">
                <n-input
                  v-model:value="loginForm.password"
                  type="password"
                  show-password-on="mousedown"
                  placeholder="Enter password"
                  @keyup.enter="submitLogin"
                />
              </n-form-item>
            </n-form>

            <n-space justify="end" class="login-actions">
              <n-button type="primary" :loading="loginLoading" @click="submitLogin">
                Submit
              </n-button>
            </n-space>
          </n-card>
        </div>
      </template>
    </n-card>

    <n-modal v-model:show="formVisible" preset="card" :title="modalTitle" :style="modalStyle">
      <n-spin :show="formSubmitting">
        <n-form
          ref="formRef"
          :model="formModel"
          :rules="formRules"
          label-placement="top"
          require-mark-placement="right-hanging"
        >
          <template v-if="formMode === 'branch'">
            <n-form-item label="Branch name" path="branchName">
              <n-input v-model:value="branchForm.branchName" placeholder="Enter branch name" />
            </n-form-item>

            <n-form-item label="Region" path="region">
              <n-input v-model:value="branchForm.region" placeholder="Enter region" />
            </n-form-item>

            <n-form-item label="Phone" path="phone">
              <n-input v-model:value="branchForm.phone" placeholder="Enter phone" />
            </n-form-item>

            <n-form-item label="Owner" path="ownerId">
              <n-select
                v-model:value="branchForm.ownerId"
                :options="ownerSelectOptions"
                placeholder="Select an owner"
                filterable
                clearable
              />
            </n-form-item>
          </template>

          <template v-else>
            <n-form-item label="Full Name" path="name">
              <n-input v-model:value="ownerForm.name" placeholder="Enter owner name" />
            </n-form-item>

            <n-form-item label="Phone" path="phone">
              <n-input v-model:value="ownerForm.phone" placeholder="Enter phone" />
            </n-form-item>

            <n-form-item label="Email" path="email">
              <n-input v-model:value="ownerForm.email" type="email" placeholder="Enter email (optional)" />
            </n-form-item>

            <n-grid :cols="2" :x-gap="12">
              <n-grid-item>
                <n-form-item label="Total amount due" path="totalAmountDue">
                  <n-input-number
                    v-model:value="ownerForm.totalAmountDue"
                    :min="0"
                    :precision="2"
                    placeholder="0.00"
                    style="width: 100%"
                  />
                </n-form-item>
              </n-grid-item>
              <n-grid-item>
                <n-form-item label="Total amount paid" path="totalAmountPaid">
                  <n-input-number
                    v-model:value="ownerForm.totalAmountPaid"
                    :min="0"
                    :precision="2"
                    placeholder="0.00"
                    style="width: 100%"
                  />
                </n-form-item>
              </n-grid-item>
            </n-grid>
          </template>
        </n-form>

        <n-space justify="end" class="modal-actions">
          <n-button tertiary @click="closeForm">Cancel</n-button>
          <n-button type="primary" :loading="formSubmitting" @click="submitForm">
            {{ editingId ? 'Save changes' : 'Create' }}
          </n-button>
        </n-space>
      </n-spin>
    </n-modal>

    <n-modal
      v-model:show="tokenModalVisible"
      preset="card"
      title="Password Reset Link"
      :style="{ width: 'min(580px, calc(100vw - 32px))' }"
    >
      <div class="token-modal">
        <div class="token-modal__label">
          Reset link for: <strong>{{ createdTokenEmail || '—' }}</strong>
        </div>
        <div class="token-modal__hint">
          Share this link with the user so they can reset their password.
        </div>
        <n-input readonly :value="fullResetLink" placeholder="Reset link will appear here" />
        <div class="token-modal__actions">
          <n-button tertiary @click="tokenModalVisible = false">Close</n-button>
          <n-button type="primary" :disabled="!fullResetLink" @click="copyResetLink">
            Copy Link
          </n-button>
        </div>
      </div>
    </n-modal>
  </div>
</template>

<script setup lang="ts">
import { computed, h, onMounted, reactive, ref } from 'vue'
import {
  NButton,
  NCard,
  NDataTable,
  NForm,
  NFormItem,
  NGrid,
  NGridItem,
  NIcon,
  NInput,
  NInputNumber,
  NModal,
  NPopconfirm,
  NSelect,
  NSpace,
  NSpin,
  NTabPane,
  NTabs,
  useMessage,
  type FormInst,
  type FormRules
} from 'naive-ui'
import { Icon } from '@iconify/vue'
import branchApi from '@/api/branch'
import clinicOwnerApi from '@/api/clinicOwner'
import userApi from '@/api/user'

interface BranchData {
  id?: number
  branchName: string
  region: string
  phone: string
  ownerName?: string
  ownerId: number
}

interface ClinicOwnerData {
  id?: number
  totalAmountDue: number
  totalAmountPaid: number
  name: string
  phone: string
  email?: string
}

type TabKey = 'branches' | 'clinic-owners'
type FormMode = 'branch' | 'owner'

const TOKEN_KEY = 'token'

const message = useMessage()
const hasToken = ref<boolean>(Boolean(localStorage.getItem(TOKEN_KEY)))
const activeTab = ref<TabKey>('branches')
const branchesLoading = ref(false)
const ownersLoading = ref(false)
const formSubmitting = ref(false)
const loginLoading = ref(false)
const formVisible = ref(false)
const formMode = ref<FormMode>('branch')
const editingId = ref<number | null>(null)
const tokenModalVisible = ref(false)
const createdToken = ref('')
const createdTokenEmail = ref('')
const resetPasswordBaseLink =
  import.meta.env.VITE_RESET_PASSWORD_BASE_LINK || 'http://localhost:1234/reset-password/?token='
const branchSearch = ref('')
const ownerSearch = ref('')
const formRef = ref<FormInst | null>(null)
const loginFormRef = ref<FormInst | null>(null)
const branches = ref<BranchData[]>([])
const owners = ref<ClinicOwnerData[]>([])

const branchForm = reactive<BranchData>({
  branchName: '',
  region: '',
  phone: '',
  ownerName: '',
  ownerId: 0
})

const ownerForm = reactive<ClinicOwnerData>({
  name: '',
  phone: '',
  email: '',
  totalAmountDue: 0,
  totalAmountPaid: 0
})

const loginForm = reactive({
  password: ''
})

const rowKey = (row: any) => row.id

const modalStyle = {
  width: 'min(640px, calc(100vw - 32px))'
}

const formModel = computed(() => (formMode.value === 'branch' ? branchForm : ownerForm))

const loginRules: FormRules = {
  password: [{ required: true, message: 'Password is required', trigger: ['input', 'blur'] }]
}

const formRules: FormRules = {
  branchName: [{ required: true, message: 'Branch name is required', trigger: ['input', 'blur'] }],
  region: [{ required: true, message: 'Region is required', trigger: ['input', 'blur'] }],
  phone: [{ required: true, message: 'Phone is required', trigger: ['input', 'blur'] }],
  email: [{ type: 'email', message: 'Email must be valid', trigger: ['input', 'blur'] }],
  ownerId: [{ required: true, type: 'number', message: 'Owner is required', trigger: ['change', 'blur'] }],
  name: [{ required: true, message: 'Name is required', trigger: ['input', 'blur'] }],
  totalAmountDue: [{ required: true, type: 'number', message: 'Amount due is required', trigger: ['change', 'blur'] }],
  totalAmountPaid: [{ required: true, type: 'number', message: 'Amount paid is required', trigger: ['change', 'blur'] }]
}

function normalizeText(value: unknown) {
  return String(value ?? '').toLowerCase().trim()
}

function toNumber(value: unknown): number {
  const num = Number(value)
  return Number.isFinite(num) ? num : 0
}

function normalizeOwner(owner: any): ClinicOwnerData {
  return {
    id: owner?.id,
    name: owner?.name ?? '',
    phone: owner?.phone ?? '',
    email: owner?.email ?? '',
    totalAmountDue: toNumber(owner?.totalAmountDue ?? owner?.total_amount_due),
    totalAmountPaid: toNumber(owner?.totalAmountPaid ?? owner?.total_amount_paid)
  }
}

const filteredBranches = computed(() => {
  const q = normalizeText(branchSearch.value)
  if (!q) return branches.value

  return branches.value.filter((item) => {
    return [item.branchName, item.region, item.phone, item.ownerName, item.ownerId]
      .map(normalizeText)
      .some((field) => field.includes(q))
  })
})

const filteredOwners = computed(() => {
  const q = normalizeText(ownerSearch.value)
  if (!q) return owners.value

  return owners.value.filter((item) => {
    return [item.name, item.phone, item.totalAmountDue, item.totalAmountPaid]
      .map(normalizeText)
      .some((field) => field.includes(q))
  })
})

const ownerSelectOptions = computed(() =>
  owners.value
    .filter((owner) => owner.id != null)
    .map((owner) => ({
      label: owner.name,
      value: owner.id as number
    }))
)

const modalTitle = computed(() => {
  const entity = formMode.value === 'branch' ? 'Branch' : 'Clinic Owner'
  return editingId.value ? `Edit ${entity}` : `Add ${entity}`
})

const fullResetLink = computed(() => {
  return createdToken.value ? `${resetPasswordBaseLink}${createdToken.value}` : ''
})

const branchColumns = [
  { title: 'Branch name', key: 'branchName' },
  { title: 'Region', key: 'region' },
  { title: 'Phone', key: 'phone' },
  { title: 'Owner name', key: 'ownerName' },
  {
    title: 'Actions',
    key: 'actions',
    width: 160,
    render: (row: BranchData) =>
      h(NSpace, { size: 8 }, () => [
        h(
          NButton,
          {
            size: 'small',
            tertiary: true,
            onClick: () => openEdit('branch', row)
          },
          { default: () => 'Edit' }
        ),
        h(
          NPopconfirm,
          {
            onPositiveClick: () => removeItem('branch', row.id!)
          },
          {
            trigger: () =>
              h(
                NButton,
                {
                  size: 'small',
                  type: 'error',
                  tertiary: true
                },
                { default: () => 'Delete' }
              ),
            default: () => 'Delete this branch?'
          }
        )
      ])
  }
]

const ownerColumns = [
  { title: 'Name', key: 'name' },
  { title: 'Phone', key: 'phone' },
  {
    title: 'Amount due',
    key: 'totalAmountDue',
    render: (row: ClinicOwnerData) => formatCurrency(row.totalAmountDue)
  },
  {
    title: 'Amount paid',
    key: 'totalAmountPaid',
    render: (row: ClinicOwnerData) => formatCurrency(row.totalAmountPaid)
  },
  {
    title: 'Actions',
    key: 'actions',
    width: 160,
    render: (row: ClinicOwnerData) =>
      h(NSpace, { size: 8 }, () => [
        h(
          NButton,
          {
            size: 'small',
            tertiary: true,
            onClick: () => openEdit('owner', row)
          },
          { default: () => 'Edit' }
        ),
        h(
          NPopconfirm,
          {
            onPositiveClick: () => removeItem('owner', row.id!)
          },
          {
            trigger: () =>
              h(
                NButton,
                {
                  size: 'small',
                  type: 'error',
                  tertiary: true
                },
                { default: () => 'Delete' }
              ),
            default: () => 'Delete this clinic owner?'
          }
        )
      ])
  }
]

function formatCurrency(value: number | null | undefined) {
  const num = Number(value ?? 0)
  return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format(num)
}

function resetBranchForm() {
  branchForm.branchName = ''
  branchForm.region = ''
  branchForm.phone = ''
  branchForm.ownerName = ''
  branchForm.ownerId = 0
}

function resetOwnerForm() {
  ownerForm.name = ''
  ownerForm.phone = ''
  ownerForm.email = ''
  ownerForm.totalAmountDue = 0
  ownerForm.totalAmountPaid = 0
}

function openCreate(mode: FormMode) {
  formMode.value = mode
  editingId.value = null
  if (mode === 'branch') resetBranchForm()
  else resetOwnerForm()
  formVisible.value = true
}

function openEdit(mode: FormMode, row: BranchData | ClinicOwnerData) {
  formMode.value = mode
  editingId.value = row.id ?? null

  if (mode === 'branch') {
    const data = row as BranchData
    branchForm.branchName = data.branchName ?? ''
    branchForm.region = data.region ?? ''
    branchForm.phone = data.phone ?? ''
    branchForm.ownerName = data.ownerName ?? ''
    branchForm.ownerId = toNumber(data.ownerId)
  } else {
    const data = normalizeOwner(row as any)
    ownerForm.name = data.name
    ownerForm.phone = data.phone
    ownerForm.email = data.email ?? ''
    ownerForm.totalAmountDue = data.totalAmountDue
    ownerForm.totalAmountPaid = data.totalAmountPaid
  }

  formVisible.value = true
}

function closeForm() {
  formVisible.value = false
}

function resetTokenModal() {
  tokenModalVisible.value = false
  createdToken.value = ''
  createdTokenEmail.value = ''
}

function copyResetLink() {
  if (!fullResetLink.value) return

  navigator.clipboard
    .writeText(fullResetLink.value)
    .then(() => {
      message.success('Reset link copied to clipboard')
    })
    .catch(() => {
      message.error('Failed to copy reset link')
    })
}

function resetLoginForm() {
  loginForm.password = ''
}

async function submitLogin() {
  try {
    await loginFormRef.value?.validate()
    loginLoading.value = true

    const result: any = await userApi.hyperUserLogin(loginForm.password)
    const token = result?.data?.token ?? result?.token ?? result?.data ?? result

    if (token) {
      localStorage.setItem(TOKEN_KEY, String(token))
      hasToken.value = true
      resetLoginForm()
      message.success('Logged in successfully')
      await Promise.all([loadBranches(), loadOwners()])
      return
    }

    message.error('Login did not return a token')
  } catch (error) {
    message.error('Login failed')
  } finally {
    loginLoading.value = false
  }
}

async function loadBranches() {
  branchesLoading.value = true
  try {
    const res: any = await branchApi.getAllBranches()
    branches.value = Array.isArray(res?.data?.data) ? res.data.data : Array.isArray(res?.data) ? res.data : Array.isArray(res) ? res : []
  } catch (error) {
    message.error('Failed to load branches')
  } finally {
    branchesLoading.value = false
  }
}

async function loadOwners() {
  ownersLoading.value = true
  try {
    const res: any = await clinicOwnerApi.getClinicOwners()
    const rawOwners = Array.isArray(res?.data?.data)
      ? res.data.data
      : Array.isArray(res?.data)
        ? res.data
        : Array.isArray(res)
          ? res
          : []

    owners.value = rawOwners.map(normalizeOwner)
  } catch (error) {
    message.error('Failed to load clinic owners')
  } finally {
    ownersLoading.value = false
  }
}

async function submitForm() {
  try {
    await formRef.value?.validate()
    formSubmitting.value = true

    if (formMode.value === 'branch') {
      const payload = { ...branchForm }
      if (editingId.value) {
        await branchApi.putBranch(editingId.value, payload)
        message.success('Branch updated')
      } else {
        await branchApi.postBranch(payload)
        message.success('Branch created')
      }
      await loadBranches()
    } else {
      const payload = {
        ...ownerForm,
        totalAmountDue: toNumber(ownerForm.totalAmountDue),
        totalAmountPaid: toNumber(ownerForm.totalAmountPaid)
      }

      if (editingId.value) {
        await clinicOwnerApi.putClinicOwner(editingId.value, payload)
        message.success('Clinic owner updated')
      } else {
        const res: any = await clinicOwnerApi.postClinicOwner(payload)
        const token = res?.data?.token ?? res?.token ?? ''
        const email = res?.data?.email ?? payload.email ?? ''

        if (token) {
          createdToken.value = String(token)
          createdTokenEmail.value = String(email)
          tokenModalVisible.value = true
        }
        message.success('Clinic owner created')
      }
      await loadOwners()
    }

    formVisible.value = false
  } catch (error) {
    if (error) message.error('Please check the form and try again')
  } finally {
    formSubmitting.value = false
  }
}

async function removeItem(mode: FormMode, id: number) {
  try {
    if (mode === 'branch') {
      await branchApi.deleteBranch(id)
      message.success('Branch deleted')
      await loadBranches()
    } else {
      await clinicOwnerApi.deleteClinicOwner(id)
      message.success('Clinic owner deleted')
      await loadOwners()
    }
  } catch (error) {
    message.error('Delete failed')
  }
}

onMounted(async () => {
  if (hasToken.value) {
    await Promise.all([loadBranches(), loadOwners()])
  } else {
    resetLoginForm()
  }
})
</script>

<style scoped>
.crud-page {
  padding: 20px;
}

.page-shell {
  border-radius: 20px;
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
}

.page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 18px;
}

.title {
  margin: 0;
  font-size: 1.55rem;
  font-weight: 700;
}

.subtitle {
  margin: 6px 0 0;
  color: var(--n-text-color-3);
}

.toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin: 16px 0;
}

.search-box {
  max-width: 420px;
  flex: 1;
}

.table-card {
  border-radius: 16px;
  overflow: hidden;
}

.modal-actions {
  margin-top: 18px;
}

.login-wrap {
  display: flex;
  justify-content: center;
  padding: 48px 0 12px;
}

.login-card {
  width: min(460px, 100%);
  border-radius: 20px;
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
}

.login-icon-wrap {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 64px;
  height: 64px;
  border-radius: 999px;
  margin-bottom: 16px;
  background: rgba(94, 92, 230, 0.1);
}

.login-title {
  margin: 0;
  font-size: 1.3rem;
  font-weight: 700;
}

.login-subtitle {
  margin: 8px 0 18px;
  color: var(--n-text-color-3);
}

.login-actions {
  margin-top: 6px;
}

.token-modal {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.token-modal__label {
  font-size: 0.95rem;
}

.token-modal__hint {
  font-size: 0.85rem;
  color: var(--n-text-color-3);
}

.token-modal__actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}

@media (max-width: 720px) {
  .crud-page {
    padding: 12px;
  }

  .toolbar {
    flex-direction: column;
    align-items: stretch;
  }

  .search-box {
    max-width: none;
  }
}
</style>
