<script setup lang="ts">
import { computed, h, onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import {
  NButton,
  NCard,
  NDataTable,
  NDatePicker,
  NForm,
  NFormItem,
  NGrid,
  NGridItem,
  NIcon,
  NInput,
  NModal,
  NPopconfirm,
  NSelect,
  NTag,
  useMessage,
  type SelectOption,
} from 'naive-ui'
import { Icon } from '@iconify/vue'
import expenseApi from '@api/expense'
import employeeApi from '@api/employee'
import accountApi from '@api/account'
import type ExpenseData from '@api/interfaces/Expense'
import type EmployeeData from '@api/interfaces/Employee'
import type AccountData from '@api/interfaces/Account'

type ExpenseRow = ExpenseData & { id?: number; paidByEmployeeName?: string }

const message = useMessage()
const route = useRoute()

const loading = ref(false)
const submitting = ref(false)
const showEditor = ref(false)
const isEditing = ref(false)
const editingId = ref<number | null>(null)
const keyword = ref('')

const expenses = ref<ExpenseRow[]>([])
const employeeOptions = ref<SelectOption[]>([])
const accountOptions = ref<SelectOption[]>([])

const formModel = reactive<ExpenseRow>({
  expenseCategory: '',
  unit: '',
  amount: '',
  expenseDate: '',
  description: '',
  paidByEmployeeId: 0 as unknown as number,
  paidByEmployeeName: '',
  accountId: 0 as unknown as number,
})

const getEffectiveBranchId = (): number | undefined => {
  const usr = JSON.parse(localStorage.getItem('user') || 'null')
  const userBranchId = usr?.user?.employee?.branchId
  if (typeof userBranchId === 'number' && Number.isFinite(userBranchId)) return userBranchId

  const raw = route.query.branchId
  const fromQuery = typeof raw === 'string' ? Number(raw) : NaN
  return Number.isFinite(fromQuery) ? fromQuery : undefined
}

const parseExpense = (row: any): ExpenseRow => {
  return {
    id: row.id,
    expenseCategory: String(row.expenseCategory ?? row.expense_category ?? ''),
    unit: String(row.unit ?? ''),
    amount: String(row.amount ?? ''),
    expenseDate: String(row.expenseDate ?? row.expense_date ?? ''),
    description: String(row.description ?? ''),
    paidByEmployeeId: Number(row.paidByEmployeeId ?? row.paidByEmployee_id ?? 0),
    paidByEmployeeName: String(row.paidByEmployeeName ?? row.paid_by_employee_name ?? ''),
    accountId: Number(row.accountId ?? row.account_id ?? 0),
  }
}

const filteredExpenses = computed(() => {
  const list = expenses.value
  if (!keyword.value.trim()) return list
  const k = keyword.value.trim().toLowerCase()
  return list.filter((e) =>
    [
      e.expenseCategory,
      e.unit,
      e.amount,
      e.expenseDate,
      e.description,
      e.paidByEmployeeName,
      e.paidByEmployeeId,
      e.accountId,
    ].some((field) => String(field ?? '').toLowerCase().includes(k)),
  )
})

const totalAmount = computed(() =>
  filteredExpenses.value.reduce((sum, e) => sum + (Number(e.amount) || 0), 0),
)
const todayTotal = computed(() => {
  const today = new Date().toISOString().slice(0, 10)
  return filteredExpenses.value
    .filter((e) => e.expenseDate === today)
    .reduce((sum, e) => sum + (Number(e.amount) || 0), 0)
})

const columns = [
  {
    title: 'Date',
    key: 'expenseDate',
    width: 120,
  },
  {
    title: 'Category',
    key: 'expenseCategory',
    width: 150,
    render(row: ExpenseRow) {
      return h(
        NTag,
        {
          round: true,
          size: 'small',
          type: 'info',
        },
        { default: () => row.expenseCategory || 'N/A' },
      )
    },
  },
  {
    title: 'Description',
    key: 'description',
    ellipsis: { tooltip: true },
  },
  {
    title: 'Unit',
    key: 'unit',
    width: 120,
  },
  {
    title: 'Amount',
    key: 'amount',
    width: 120,
    render(row: ExpenseRow) {
      const value = Number(row.amount) || 0
      return `${value.toLocaleString()}`
    },
  },
  {
    title: 'Paid by',
    key: 'paidByEmployeeName',
    width: 160,
    render(row: ExpenseRow) {
      return row.paidByEmployeeName || `ID: ${row.paidByEmployeeId || '-'}`
    },
  },
  {
    title: 'Actions',
    key: 'actions',
    width: 120,
    render(row: ExpenseRow) {
      return h('div', { class: 'row-actions' }, [
        h(
          NButton,
          {
            text: true,
            type: 'primary',
            onClick: () => handleEdit(row),
          },
          {
            icon: () => h(Icon, { icon: 'akar-icons:edit' }),
          },
        ),
        h(
          NPopconfirm,
          {
            onPositiveClick: () => handleDelete(row),
            negativeText: 'Cancel',
            positiveText: 'Delete',
          },
          {
            trigger: () =>
              h(
                NButton,
                {
                  text: true,
                  type: 'error',
                },
                { icon: () => h(Icon, { icon: 'fluent:delete-16-filled' }) },
              ),
            default: () => 'Delete this expense?',
          },
        ),
      ])
    },
  },
]

async function fetchExpenses() {
  try {
    loading.value = true
    const { data } = await expenseApi.getBranchExpenses(getEffectiveBranchId())
    const rows = Array.isArray(data?.data) ? data.data : Array.isArray(data) ? data : []
    expenses.value = rows.map((row: any) => parseExpense(row))
  } catch (error) {
    console.error(error)
    message.error('Failed to load expenses')
  } finally {
    loading.value = false
  }
}

async function fetchEmployees() {
  try {
    const { data } = await employeeApi.getBranchEmployees(true, getEffectiveBranchId())
    employeeOptions.value = data.data.map((emp: EmployeeData & { id?: number }): SelectOption => ({
      label: (emp.name as string) || 'Unknown',
      value: emp.id,
    }))
  } catch (error) {
    console.error(error)
  }
}

async function fetchAccounts() {
  try {
    const { data } = await accountApi.getBranchAccounts(true, getEffectiveBranchId())
    accountOptions.value = data.data.map((acc: AccountData & { id?: number }): SelectOption => ({
      label: (acc.accountName as string) || 'Account',
      value: acc.id,
    }))
  } catch (error) {
    console.error(error)
  }
}

function resetForm() {
  formModel.expenseCategory = ''
  formModel.unit = ''
  formModel.amount = ''
  formModel.expenseDate = ''
  formModel.description = ''
  formModel.paidByEmployeeId = 0 as unknown as number
  formModel.paidByEmployeeName = ''
  formModel.accountId = 0 as unknown as number
}

function openCreate() {
  isEditing.value = false
  editingId.value = null
  resetForm()
  showEditor.value = true
}

function handleEdit(row: ExpenseRow) {
  isEditing.value = true
  editingId.value = row.id ?? null
  formModel.expenseCategory = String(row.expenseCategory || '')
  formModel.unit = String(row.unit || '')
  formModel.amount = String(row.amount || '')
  formModel.expenseDate = String(row.expenseDate || '')
  formModel.description = String(row.description || '')
  formModel.paidByEmployeeId = Number(row.paidByEmployeeId || 0)
  formModel.paidByEmployeeName = String(row.paidByEmployeeName || '')
  formModel.accountId = Number(row.accountId || 0)
  showEditor.value = true
}

async function handleDelete(row: ExpenseRow) {
  const id = row.id
  if (!id) {
    message.error('Missing expense id')
    return
  }
  try {
    await expenseApi.deleteExpense(id)
    message.success('Expense deleted')
    await fetchExpenses()
  } catch (error) {
    console.error(error)
    message.error('Failed to delete expense')
  }
}

async function handleSubmit() {
  const payload: ExpenseData = {
    expenseCategory: formModel.expenseCategory,
    unit: formModel.unit,
    amount: formModel.amount,
    expenseDate: formModel.expenseDate,
    description: formModel.description,
    paidByEmployeeId: formModel.paidByEmployeeId,
    paidByEmployeeName: formModel.paidByEmployeeName,
    accountId: formModel.accountId,
  }

  submitting.value = true
  try {
    if (isEditing.value && editingId.value != null) {
      await expenseApi.updateExpense(editingId.value, payload)
      message.success('Expense updated')
    } else {
      await expenseApi.postExpense(payload)
      message.success('Expense created')
    }
    showEditor.value = false
    await fetchExpenses()
  } catch (error) {
    console.error(error)
    message.error('Failed to save expense')
  } finally {
    submitting.value = false
  }
}

function handleDateChange(value: number | null) {
  if (!value) {
    formModel.expenseDate = ''
    return
  }
  formModel.expenseDate = new Date(value).toISOString().slice(0, 10)
}

function handleAccountChange(value: number) {
  formModel.accountId = value
}

onMounted(() => {
  fetchExpenses()
  fetchEmployees()
  fetchAccounts()
})
</script>

<template>
  <div class="expense-view">
    <div class="view-header">
      <div>
        <h2 class="view-title">
          <n-icon size="24"><Icon icon="mdi:cash-multiple" /></n-icon>
          Clinical Expenses
        </h2>
        <p class="view-subtitle">
          Add and track daily clinic expenses like fuel, supplies, and operations costs.
        </p>
      </div>
      <n-button type="primary" class="add-btn" @click="openCreate">
        <template #icon>
          <n-icon><Icon icon="mdi:plus" /></n-icon>
        </template>
        Add Expense
      </n-button>
    </div>

    <n-grid :cols="{ xs: 1, s: 2, m: 3 }" :x-gap="12" :y-gap="12" class="stats-grid">
      <n-grid-item>
        <n-card size="small" class="stat-card">
          <div class="stat-title">Total expenses</div>
          <div class="stat-value">{{ totalAmount.toLocaleString() }}</div>
        </n-card>
      </n-grid-item>
      <n-grid-item>
        <n-card size="small" class="stat-card">
          <div class="stat-title">Today</div>
          <div class="stat-value">{{ todayTotal.toLocaleString() }}</div>
        </n-card>
      </n-grid-item>
      <n-grid-item>
        <n-card size="small" class="stat-card">
          <div class="stat-title">Records</div>
          <div class="stat-value">{{ filteredExpenses.length }}</div>
        </n-card>
      </n-grid-item>
    </n-grid>

    <n-card size="small" class="toolbar-card">
      <div class="toolbar">
        <n-input v-model:value="keyword" clearable placeholder="Search by category, date, description, payer..." class="search-input">
          <template #prefix>
            <n-icon><Icon icon="mdi:magnify" /></n-icon>
          </template>
        </n-input>
      </div>
    </n-card>

    <n-card size="small" class="table-card">
      <div class="table-wrapper desktop-table">
        <n-data-table
          :columns="columns"
          :data="filteredExpenses"
          :loading="loading"
          :pagination="{ pageSize: 10, simple: true }"
          :row-key="(row: ExpenseRow) => row.id ?? `${row.expenseDate}-${row.description}`"
          :scroll-x="1000"
          size="small"
          bordered
        />
      </div>

      <div class="mobile-list">
        <n-card v-for="row in filteredExpenses" :key="row.id ?? `${row.expenseDate}-${row.description}`" size="small" class="mobile-item">
          <div class="mobile-top">
            <n-tag type="info" round>{{ row.expenseCategory }}</n-tag>
            <strong>{{ (Number(row.amount) || 0).toLocaleString() }}</strong>
          </div>
          <div class="mobile-meta">
            <span>{{ row.expenseDate }}</span>
            <span>{{ row.unit }}</span>
          </div>
          <p class="mobile-description">{{ row.description }}</p>
          <div class="mobile-meta">
            <span>{{ row.paidByEmployeeName || `Employee #${row.paidByEmployeeId}` }}</span>
          </div>
          <div class="mobile-actions">
            <n-button size="tiny" type="primary" ghost @click="handleEdit(row)">
              <template #icon><Icon icon="akar-icons:edit" /></template>
              Edit
            </n-button>
            <n-popconfirm @positive-click="handleDelete(row)" negative-text="Cancel" positive-text="Delete">
              <template #trigger>
                <n-button size="tiny" type="error" ghost>
                  <template #icon><Icon icon="fluent:delete-16-filled" /></template>
                  Delete
                </n-button>
              </template>
              Delete this expense?
            </n-popconfirm>
          </div>
        </n-card>
      </div>
    </n-card>

    <n-modal
      v-model:show="showEditor"
      preset="card"
      class="expense-modal"
      style="max-width: 700px; max-height: 90vh; overflow: auto"
      :title="isEditing ? 'Edit Expense' : 'Add Expense'"
    >
      <n-form label-placement="top">
        <div class="form-row">
          <n-form-item label="Expense category">
            <n-input v-model:value="formModel.expenseCategory as string" placeholder="e.g. Gas Tank, Medical Supplies" />
          </n-form-item>
          <n-form-item label="Unit">
            <n-input v-model:value="formModel.unit as string" placeholder="e.g. liter, item, service" />
          </n-form-item>
        </div>

        <div class="form-row">
          <n-form-item label="Amount">
            <n-input v-model:value="formModel.amount as string" placeholder="e.g. 50" />
          </n-form-item>
          <n-form-item label="Expense date">
            <n-date-picker
              type="date"
              style="width: 100%"
              :value="formModel.expenseDate ? Date.parse(formModel.expenseDate as string) : null"
              @update:value="handleDateChange"
            />
          </n-form-item>
        </div>

        <div class="form-row">
          <!-- <n-form-item label="Paid by employee">
            <n-select
              v-model:value="formModel.paidByEmployeeId as any"
              :options="employeeOptions"
              placeholder="Select employee"
              filterable
            />
          </n-form-item> -->
          <n-form-item label="Account">
            <n-select
              :options="accountOptions"
              v-model:value="formModel.accountId as any"
              @update:value="handleAccountChange"
              placeholder="Select account"
              filterable
            />
          </n-form-item>
        </div>

        <div class="form-row single">
          <n-form-item label="Description">
            <n-input
              v-model:value="formModel.description as string"
              type="textarea"
              :autosize="{ minRows: 3, maxRows: 5 }"
              placeholder="Add details for accounting and audit trail"
            />
          </n-form-item>
        </div>

        <div class="form-actions">
          <n-button @click="showEditor = false">Cancel</n-button>
          <n-button type="primary" :loading="submitting" @click="handleSubmit">Save Expense</n-button>
        </div>
      </n-form>
    </n-modal>
  </div>
</template>

<style scoped>
.expense-view {
  padding: 14px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

@media (min-width: 768px) {
  .expense-view {
    padding: 20px;
    gap: 14px;
  }
}

.view-header {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

@media (min-width: 720px) {
  .view-header {
    flex-direction: row;
    justify-content: space-between;
    align-items: flex-end;
  }
}

.view-title {
  margin: 0;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: clamp(1.3rem, 1rem + 1vw, 1.7rem);
}

.view-subtitle {
  margin: 4px 0 0;
  color: rgba(120, 120, 120, 0.95);
  font-size: 0.95rem;
}

.add-btn {
  align-self: flex-start;
}

.stat-card :deep(.n-card__content) {
  padding: 14px 16px;
}

.stat-title {
  color: rgba(120, 120, 120, 0.95);
  font-size: 12px;
  margin-bottom: 6px;
}

.stat-value {
  font-size: 24px;
  font-weight: 750;
  line-height: 1.2;
}

.toolbar {
  display: flex;
  gap: 10px;
}

.search-input {
  width: 100%;
}

.table-wrapper {
  overflow-x: auto;
}

.table-wrapper :deep(.n-data-table-table) {
  min-width: 1000px;
}

.row-actions {
  display: flex;
  align-items: center;
  gap: 6px;
}

.mobile-list {
  display: none;
}

@media (max-width: 768px) {
  .desktop-table {
    display: none;
  }

  .mobile-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
  }
}

.mobile-item :deep(.n-card__content) {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.mobile-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.mobile-meta {
  display: flex;
  justify-content: space-between;
  color: rgba(120, 120, 120, 0.95);
  font-size: 12px;
  gap: 6px;
}

.mobile-description {
  margin: 0;
  font-size: 13px;
}

.mobile-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}

.form-row {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.form-row.single {
  grid-template-columns: 1fr;
}

@media (max-width: 768px) {
  .form-row {
    grid-template-columns: 1fr;
  }

  :deep(.view-patient-container) {
    padding-top: 11em !important;
    display: none !important;
  }
}

.form-actions {
  margin-top: 12px;
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}
</style>
