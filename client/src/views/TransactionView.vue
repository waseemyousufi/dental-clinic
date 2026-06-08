
<script setup lang="ts">
import { ref, reactive, computed, onMounted, h } from 'vue'
import { useRoute } from 'vue-router'
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
  type SelectOption,
  NHighlight,
} from 'naive-ui'

import transactionApi from '@api/transaction'
import type TransactionData from '@api/interfaces/Transaction'
import employeeApi from '@api/employee'
import type EmployeeData from '@api/interfaces/Employee'
import accountApi from '@api/account'
import type AccountData from '@api/interfaces/Account'
import { Icon } from '@iconify/vue'

type TransactionRow = TransactionData & { id?: number }

const message = useMessage()
const route = useRoute()

const getEffectiveBranchId = (): number | undefined => {
  const usr = JSON.parse(localStorage.getItem('user') || 'null')
  const userBranchId = usr?.user?.employee?.branchId

  if (typeof userBranchId === 'number' && Number.isFinite(userBranchId)) {
    return userBranchId
  }

  const raw = route.query.branchId
  const fromQuery = typeof raw === 'string' ? Number(raw) : NaN

  return Number.isFinite(fromQuery) ? fromQuery : undefined
}

const loading = ref(false)
const transactions = ref<TransactionRow[]>([])
const employeeOptions = ref<SelectOption[]>([])
const accountOptions = ref<SelectOption[]>([])
const keyword = ref('')

const showEditor = ref(false)
const submitting = ref(false)
const isEditing = ref(false)
const editingId = ref<number | null>(null)

const formModel = reactive<TransactionRow>({
  transactionType: '',
  amount: 0 as unknown as number,
  transactionDate: '',
  referenceType: '',
  description: '',
  recordedByEmployeeId: 0 as unknown as number,
  accountId: 0 as unknown as number,
})

const transactionTypeOptions = [
  { label: 'In', value: 'in' },
  { label: 'Out', value: 'out' },
]

const tooltipConfig = {
  placement: 'top',
  to: false,
  style: {
    direction: 'rtl',
    textAlign: 'right',
  },
}

const filteredTransactions = computed(() => {
  if (!keyword.value.trim()) return transactions.value

  const k = keyword.value.trim().toLowerCase()

  return transactions.value.filter((t: TransactionRow) =>
    [
      t.transactionType,
      t.referenceType,
      t.description,
      t.accountId,
      t.recordedByEmployeeId,
    ].some((field) =>
      String(field || '')
        .toLowerCase()
        .includes(k),
    ),
  )
})

const columns = [
  {
    title: 'Type',
    key: 'transactionType',
    render(row: TransactionRow) {
      return h(NHighlight, {
        text: row.transactionType as string,
        patterns: [row.transactionType as string],
        highlightStyle: {
          backgroundColor:
            row.transactionType === 'in'
              ? '#16ff4a77'
              : '#e30e0eaa',
          padding: '2px 4px',
          borderRadius: '4px',
          textTransform: 'uppercase',
        },
      })
    },
  },

  {
    title: 'Amount',
    key: 'amount',
    width: 120,
    render(row: TransactionRow) {
      const v = row.amount as any
      return typeof v === 'number'
        ? v.toLocaleString()
        : String(v ?? '')
    },
  },

  {
    title: 'Date',
    key: 'transactionDate',
  },

  {
    title: 'Reference type',
    key: 'referenceType',
    ellipsis: {
      tooltip: tooltipConfig,
    },
  },

  {
    title: 'Description',
    key: 'description',
    ellipsis: {
      tooltip: tooltipConfig,
    },
  },

  {
    title: 'Recorded by',
    key: 'recordedByEmployee',
    width: 160,
  },

  {
    title: 'Account',
    key: 'account',
    width: 120,
  },

  {
    title: 'Actions',
    key: 'actions',

    render(row: TransactionRow) {
      return h(
        'div',
        {
          style: 'display:flex;gap:8px;',
        },
        [
          h(Icon, {
            icon: 'akar-icons:edit',
            width: 20,
            height: 20,
            color: '#4f46e5',
            style: {
              cursor: 'pointer',
            },
            onClick: () => handleEdit(row),
          }),

          h(Icon, {
            icon: 'fluent:delete-16-filled',
            width: 20,
            height: 20,
            color: '#dc2626',
            style: {
              cursor: 'pointer',
            },
            onClick: () => handleDelete(row),
          }),
        ],
      )
    },
  },
]

async function fetchTransactions() {
  try {
    loading.value = true

    const { data } =
      await transactionApi.getBranchTransactions(
        getEffectiveBranchId(),
      )

    transactions.value = data.data as TransactionRow[]
  } catch (error) {
    console.error(error)
    message.error('Failed to load transactions')
  } finally {
    loading.value = false
  }
}

async function fetchEmployees() {
  try {
    const { data } =
      await employeeApi.getBranchEmployees(
        true,
        getEffectiveBranchId(),
      )

    employeeOptions.value = data.data.map(
      (emp: EmployeeData & { id?: number }): SelectOption => ({
        label: emp.name as string,
        value: emp.id,
      }),
    )
  } catch (error) {
    console.error(error)
    message.error('Failed to load employees')
  }
}

async function fetchAccounts() {
  try {
    const { data } =
      await accountApi.getBranchAccounts(
        false,
        getEffectiveBranchId(),
      )

    accountOptions.value = data.data.map(
      (acc: AccountData & { id?: number }): SelectOption => ({
        label: acc.accountName as string,
        value: acc.id,
      }),
    )
  } catch (error) {
    console.error(error)
    message.error('Failed to load accounts')
  }
}

function resetForm() {
  formModel.transactionType = ''
  formModel.amount = 0 as unknown as number
  formModel.transactionDate = ''
  formModel.referenceType = ''
  formModel.description = ''
  formModel.recordedByEmployeeId = 0 as unknown as number
  formModel.accountId = 0 as unknown as number
}

function openCreate() {
  isEditing.value = false
  editingId.value = null
  resetForm()
  showEditor.value = true
}

function handleEdit(row: TransactionRow) {
  isEditing.value = true
  editingId.value = row.id ?? null

  formModel.transactionType = String(row.transactionType || '')
  formModel.amount = row.amount ?? 0
  formModel.transactionDate = String(row.transactionDate || '')
  formModel.referenceType = String(row.referenceType || '')
  formModel.description = String(row.description || '')
  formModel.recordedByEmployeeId =
    row.recordedByEmployeeId ?? 0
  formModel.accountId = row.accountId ?? 0

  showEditor.value = true
}

async function handleDelete(row: TransactionRow) {
  const id = row.id

  if (!id) {
    message.error('Missing transaction id')
    return
  }

  try {
    await transactionApi.deleteTransaction(id)

    message.success('Transaction deleted')

    fetchTransactions()
  } catch (error) {
    console.error(error)
    message.error('Failed to delete transaction')
  }
}

async function handleSubmit() {
  submitting.value = true

  try {
    const payload: TransactionData = {
      transactionType: formModel.transactionType,
      amount: formModel.amount,
      transactionDate: formModel.transactionDate,
      referenceType: formModel.referenceType,
      description: formModel.description,
      recordedByEmployeeId:
        formModel.recordedByEmployeeId,
      accountId: formModel.accountId,
    }

    if (
      isEditing.value &&
      editingId.value != null
    ) {
      await transactionApi.updateTransaction(
        editingId.value,
        payload,
      )

      message.success('Transaction updated')
    } else {
      await transactionApi.postTransaction(payload)

      message.success('Transaction created')
    }

    showEditor.value = false

    await fetchTransactions()
  } catch (error) {
    console.error(error)
    message.error('Failed to save transaction')
  } finally {
    submitting.value = false
  }
}

function handleDateChange(value: number | null) {
  if (!value) {
    formModel.transactionDate = ''
  } else {
    formModel.transactionDate = new Date(value)
      .toISOString()
      .slice(0, 10)
  }
}

function handleEmployeeChange(value: number) {
  formModel.recordedByEmployeeId = value
}

function handleAccountChange(value: number) {
  formModel.accountId = value
}

onMounted(() => {
  fetchTransactions()
  fetchAccounts()
  fetchEmployees()
})
</script>

<template>
  <div class="transaction-view" dir="rtl">
    <n-card
      size="small"
      class="transaction-panel"
    >
      <div class="toolbar">
        <n-input
          v-model:value="keyword"
          clearable
          placeholder="Search by type, description or account"
          size="small"
        />

        <n-button
          type="primary"
          size="small"
          @click="openCreate"
        >
          New Transaction
        </n-button>
      </div>

      <div class="table-wrapper">
        <n-data-table
          class="data-table"
          :columns="columns"
          :data="filteredTransactions"
          :loading="loading"
          :pagination="{
            pageSize: 10,
            simple: true,
          }"
          :scroll-x="1000"
          size="small"
          bordered
        />
      </div>
    </n-card>

    <n-modal
      v-model:show="showEditor"
      preset="card"
      style="
        max-width: 600px;
        max-height: 90vh;
        overflow: auto;
      "
      :title="
        isEditing
          ? 'Edit Transaction'
          : 'New Transaction'
      "
      class="transaction-modal"
    >
      <n-form label-width="160">
        <div class="form-row">
          <n-form-item label="Transaction type">
            <n-select
              :to="false"
              v-model:value="
                formModel.transactionType as string
              "
              :options="transactionTypeOptions"
              placeholder="Select type"
            />
          </n-form-item>

          <n-form-item label="Amount">
            <n-input
              v-model:value="
                formModel.amount as any
              "
              placeholder="e.g. 2500"
            />
          </n-form-item>
        </div>

        <div class="form-row">
          <n-form-item label="Transaction date">
            <n-date-picker
              :to="false"
              type="date"
              size="small"
              style="width: 100%"
              :value="
                formModel.transactionDate
                  ? Date.parse(
                      formModel.transactionDate as string,
                    )
                  : null
              "
              @update:value="handleDateChange"
            />
          </n-form-item>

          <n-form-item label="Reference type">
            <n-input
              v-model:value="
                formModel.referenceType as string
              "
              placeholder="e.g. Invoice, Receipt"
            />
          </n-form-item>
        </div>

        <div class="form-row">
          <n-form-item label="Recorded By">
            <n-select
              :to="false"
              :options="employeeOptions"
              :value="
                formModel.recordedByEmployeeId
              "
              @update:value="
                handleEmployeeChange
              "
            />
          </n-form-item>

          <n-form-item label="Account">
            <n-select
              :to="false"
              :options="accountOptions"
              :value="formModel.accountId"
              @update:value="
                handleAccountChange
              "
            />
          </n-form-item>
        </div>

        <div class="form-row">
          <n-form-item label="Description">
            <n-input
              v-model:value="
                formModel.description as string
              "
              type="textarea"
              placeholder="Short description of the transaction"
            />
          </n-form-item>
        </div>

        <div class="form-actions">
          <n-button
            size="small"
            @click="showEditor = false"
          >
            Cancel
          </n-button>

          <n-button
            type="primary"
            size="small"
            :loading="submitting"
            @click="handleSubmit"
          >
            Save
          </n-button>
        </div>
      </n-form>
    </n-modal>
  </div>
</template>

<style scoped>
.transaction-view {
  padding: 16px 20px;
  display: flex;
  flex-direction: column;
  width: 100%;
  min-height: 100%;
  box-sizing: border-box;
  direction: rtl;
  overflow: visible;
}

.transaction-panel {
  width: 100%;
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: column;
  overflow: visible;
}

.transaction-panel :deep(.n-card__content) {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-height: 0;
  overflow: visible;
}

.transaction-panel :deep(.n-data-table) {
  flex: 1;
  min-width: 0;
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
  overflow-y: visible;
  position: relative;
}

.table-wrapper :deep(.n-data-table-table) {
  min-width: 1000px;
}

.table-wrapper :deep(.n-data-table-td) {
  overflow: visible;
}

.table-wrapper :deep(.n-ellipsis) {
  direction: rtl;
  text-align: right;
}

.form-row {
  display: grid;
  grid-template-columns:
    repeat(2, minmax(0, 1fr));
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
