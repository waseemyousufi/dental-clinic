<script setup lang="ts">
import { ref, reactive, computed, onMounted, h } from 'vue'
import { useRoute } from 'vue-router'
import {
  NCard,
  NButton,
  NInput,
  NDataTable,
  NInputNumber,
  NModal,
  NForm,
  NFormItem,
  NSelect,
  NDatePicker,
  useMessage,
  NTag,
  NHighlight,
  NTooltip,
  type SelectOption,
} from 'naive-ui'
import { Icon } from '@iconify/vue'

import accountApi from '@api/account'
import type AccountData from '@api/interfaces/Account'
import transactionApi from '@api/transaction'
import type TransactionData from '@api/interfaces/Transaction'
import employeeApi from '@api/employee'
import type EmployeeData from '@api/interfaces/Employee'

type AccountRow = AccountData & { id?: number }
type TransactionRow = TransactionData & { id?: number }

const message = useMessage()
const route = useRoute()

const getEffectiveBranchId = (): number | undefined => {
  const usr = JSON.parse(localStorage.getItem('user') || 'null')
  const userBranchId = usr?.user?.employee?.branchId
  if (typeof userBranchId === 'number' && Number.isFinite(userBranchId)) return userBranchId

  const raw = route.query.branchId
  const fromQuery = typeof raw === 'string' ? Number(raw) : NaN
  return Number.isFinite(fromQuery) ? fromQuery : undefined
}

const accountsLoading = ref(false)
const transactionsLoading = ref(false)

const accounts = ref<AccountRow[]>([])
const transactions = ref<TransactionRow[]>([])
const employeeOptions = ref<SelectOption[]>([])
const accountOptions = ref<SelectOption[]>([])

const accountKeyword = ref('')
const transactionKeyword = ref('')

const showAccountEditor = ref(false)
const submittingAccount = ref(false)
const isEditingAccount = ref(false)
const editingAccountId = ref<number | null>(null)

const showTransactionEditor = ref(false)
const submittingTransaction = ref(false)
const isEditingTransaction = ref(false)
const editingTransactionId = ref<number | null>(null)

const showBalanceEditor = ref(false)
const submittingBalance = ref(false)
const balanceActionType = ref<'charge' | 'withdraw'>('charge')
const selectedAccountForBalance = ref<AccountRow | null>(null)

const accountFormModel = reactive<AccountRow>({
  accountName: '',
  accountType: '',
  status: '',
})

const transactionFormModel = reactive<TransactionRow>({
  transactionType: '',
  amount: 0 as unknown as number,
  transactionDate: '',
  referenceType: '',
  description: '',
  recordedByEmployeeId: 0 as unknown as number,
  accountId: 0 as unknown as number,
})

const balanceFormModel = reactive({
  amount: null as number | null,
  description: '',
})

const accountTypeOptions = [
  { label: 'Asset', value: 'asset' },
  { label: 'Income', value: 'income' },
  { label: 'Expense', value: 'expense' },
]

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
]

const transactionTypeOptions = [
  { label: 'In', value: 'in' },
  { label: 'Out', value: 'out' },
]

const filteredAccounts = computed(() => {
  if (!accountKeyword.value.trim()) return accounts.value
  const k = accountKeyword.value.trim().toLowerCase()
  return accounts.value.filter((account: AccountRow) =>
    [account.accountName, account.accountType, account.status].some((field) =>
      String(field || '')
        .toLowerCase()
        .includes(k),
    ),
  )
})

const filteredTransactions = computed(() => {
  if (!transactionKeyword.value.trim()) return transactions.value
  const k = transactionKeyword.value.trim().toLowerCase()
  return transactions.value.filter((transaction: TransactionRow) =>
    [
      transaction.transactionType,
      transaction.referenceType,
      transaction.description,
      transaction.accountId,
      transaction.recordedByEmployeeId,
    ].some((field) =>
      String(field || '')
        .toLowerCase()
        .includes(k),
    ),
  )
})

const incomeAccountCount = computed(() =>
  accounts.value.filter((account) => String(account.accountType || '').toLowerCase() === 'income').length,
)

const selectedAccountBalance = computed(() =>
  selectedAccountForBalance.value ? getAccountBalance(selectedAccountForBalance.value) : 0,
)

function getAccountBalance(row: AccountRow) {
  const raw = row.totalAmount as unknown
  const amount = typeof raw === 'number' ? raw : Number(raw)
  return Number.isFinite(amount) ? amount : 0
}

function isOnlyIncomeAccount(row: AccountRow) {
  return String(row.accountType || '').toLowerCase() === 'income' && incomeAccountCount.value === 1
}

function hasAccountCredit(row: AccountRow) {
  return getAccountBalance(row) > 0
}

function isAccountDeleteDisabled(row: AccountRow) {
  return isOnlyIncomeAccount(row) || hasAccountCredit(row)
}

function getAccountDeleteReason(row: AccountRow) {
  if (isOnlyIncomeAccount(row)) return 'The only income account cannot be deleted.'
  if (hasAccountCredit(row)) return 'Accounts with credit cannot be deleted.'
  return 'Delete'
}

const accountColumns = [
  {
    title: 'Account name',
    key: 'accountName',
    ellipsis: { tooltip: true },
  },
  {
    title: 'Type',
    key: 'accountType',
  },
  {
    title: 'Total amount',
    key: 'totalAmount',
    width: 140,
    render(row: AccountRow) {
      const value = row.totalAmount as any
      return typeof value === 'number' ? value.toLocaleString() : String(value ?? '')
    },
  },
  {
    title: 'Status',
    key: 'status',
    render(row: AccountRow) {
      const status = String(row.status || '').toLowerCase()
      const type = status === 'active' ? 'success' : status === 'inactive' ? 'error' : 'default'
      return h(
        NTag,
        {
          type,
          round: true,
          size: 'small',
        },
        { default: () => row.status || 'N/A' },
      )
    },
  },
  {
    title: 'Actions',
    key: 'actions',
    render(row: AccountRow) {
      const deleteDisabled = isAccountDeleteDisabled(row)
      return h('div', { style: 'display: flex; gap: 8px; align-items: center;' }, [
        h(Icon, {
          icon: 'akar-icons:edit',
          title: 'Edit',
          width: 20,
          height: 20,
          color: '#4f46e5',
          style: { cursor: 'pointer' },
          onClick: () => handleAccountEdit(row),
        }),
        h(Icon, {
          icon: 'material-symbols:download-rounded',
          title: 'Charge',
          width: 20,
          height: 20,
          color: '#0f766e',
          style: { cursor: 'pointer' },
          onClick: () => openBalanceEditor('charge', row),
        }),
        h(Icon, {
          icon: 'material-symbols:upload-rounded',
          title: 'Withdraw',
          width: 20,
          height: 20,
          color: '#ea580c',
          style: { cursor: 'pointer' },
          onClick: () => openBalanceEditor('withdraw', row),
        }),
        h(
          NTooltip,
          { disabled: !deleteDisabled },
          {
            trigger: () =>
              h(Icon, {
                icon: 'fluent:delete-16-filled',
                title: deleteDisabled ? undefined : 'Delete',
                width: 20,
                height: 20,
                color: deleteDisabled ? '#94a3b8' : '#dc2626',
                style: {
                  cursor: deleteDisabled ? 'not-allowed' : 'pointer',
                  opacity: deleteDisabled ? '0.55' : '1',
                },
                onClick: () => {
                  if (!deleteDisabled) handleAccountDelete(row)
                },
              }),
            default: () => getAccountDeleteReason(row),
          },
        ),
      ])
    },
  },
]

const transactionColumns = [
  {
    title: 'Type',
    key: 'transactionType',
    render(row: TransactionRow) {
      return h(NHighlight, {
        text: row.transactionType as string,
        patterns: [row.transactionType as string],
        highlightStyle: {
          backgroundColor: row.transactionType === 'in' ? '#16ff4a77' : '#e30e0eaa',
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
      const value = row.amount as any
      return typeof value === 'number' ? value.toLocaleString() : String(value ?? '')
    },
  },
  {
    title: 'Date',
    key: 'transactionDate',
  },
  {
    title: 'Reference type',
    key: 'referenceType',
    ellipsis: { tooltip: true },
  },
  {
    title: 'Description',
    key: 'description',
    ellipsis: { tooltip: true },
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
      return h('div', { style: 'display: flex; gap: 8px; align-items: center;' }, [
        // h(Icon, {
        //   icon: 'akar-icons:edit',
        //   title: 'Edit',
        //   width: 20,
        //   height: 20,
        //   color: '#4f46e5',
        //   style: { cursor: 'pointer' },
        //   onClick: () => handleTransactionEdit(row),
        // }),
        h(Icon, {
          icon: 'fluent:delete-16-filled',
          title: 'Delete',
          width: 20,
          height: 20,
          color: '#dc2626',
          style: { cursor: 'pointer' },
          onClick: () => handleTransactionDelete(row),
        }),
      ])
    },
  },
]

async function fetchAccounts() {
  try {
    accountsLoading.value = true
    const { data } = await accountApi.getBranchAccounts(true, getEffectiveBranchId())
    accounts.value = data.data as AccountRow[]
  } catch (error) {
    console.error(error)
    message.error('Failed to load accounts')
  } finally {
    accountsLoading.value = false
  }
}

async function fetchTransactions() {
  try {
    transactionsLoading.value = true
    const { data } = await transactionApi.getBranchTransactions(getEffectiveBranchId())
    transactions.value = data.data as TransactionRow[]
  } catch (error) {
    console.error(error)
    message.error('Failed to load transactions')
  } finally {
    transactionsLoading.value = false
  }
}

async function fetchEmployees() {
  try {
    const { data } = await employeeApi.getBranchEmployees(true, getEffectiveBranchId())
    employeeOptions.value = data.data.map((employee: EmployeeData & { id?: number }): SelectOption => ({
      label: employee.name as string,
      value: employee.id,
    }))
  } catch (error) {
    console.error(error)
    message.error('Failed to load employees')
  }
}

async function fetchAccountOptions() {
  try {
    const { data } = await accountApi.getBranchAccounts(false, getEffectiveBranchId())
    accountOptions.value = data.data.map((account: AccountData & { id?: number }): SelectOption => ({
      label: account.accountName as string,
      value: account.id,
    }))
  } catch (error) {
    console.error(error)
    message.error('Failed to load accounts')
  }
}

function resetAccountForm() {
  accountFormModel.accountName = ''
  accountFormModel.accountType = ''
  accountFormModel.status = ''
}

function resetTransactionForm() {
  transactionFormModel.transactionType = ''
  transactionFormModel.amount = 0 as unknown as number
  transactionFormModel.transactionDate = ''
  transactionFormModel.referenceType = ''
  transactionFormModel.description = ''
  transactionFormModel.recordedByEmployeeId = 0 as unknown as number
  transactionFormModel.accountId = 0 as unknown as number
}

function openAccountCreate() {
  isEditingAccount.value = false
  editingAccountId.value = null
  resetAccountForm()
  showAccountEditor.value = true
}

function openTransactionCreate() {
  isEditingTransaction.value = false
  editingTransactionId.value = null
  resetTransactionForm()
  showTransactionEditor.value = true
}

function resetBalanceForm() {
  balanceFormModel.amount = null
  balanceFormModel.description = ''
}

function openBalanceEditor(type: 'charge' | 'withdraw', row: AccountRow) {
  balanceActionType.value = type
  selectedAccountForBalance.value = row
  resetBalanceForm()
  showBalanceEditor.value = true
}

function handleAccountEdit(row: AccountRow) {
  isEditingAccount.value = true
  editingAccountId.value = row.id ?? null

  accountFormModel.accountName = String(row.accountName || '')
  accountFormModel.accountType = String(row.accountType || '')
  accountFormModel.status = String(row.status || '')

  showAccountEditor.value = true
}

function handleTransactionEdit(row: TransactionRow) {
  isEditingTransaction.value = true
  editingTransactionId.value = row.id ?? null

  transactionFormModel.transactionType = String(row.transactionType || '')
  transactionFormModel.amount = (row.amount as any) ?? (0 as unknown as number)
  transactionFormModel.transactionDate = String(row.transactionDate || '')
  transactionFormModel.referenceType = String(row.referenceType || '')
  transactionFormModel.description = String(row.description || '')
  transactionFormModel.recordedByEmployeeId =
    (row.recordedByEmployeeId as any) ?? (0 as unknown as number)
  transactionFormModel.accountId = (row.accountId as any) ?? (0 as unknown as number)

  showTransactionEditor.value = true
}

async function handleAccountDelete(row: AccountRow) {
  if (isAccountDeleteDisabled(row)) {
    message.warning(getAccountDeleteReason(row))
    return
  }

  const id = row.id
  if (!id) {
    message.error('Missing account id')
    return
  }

  try {
    await accountApi.deleteAccount(id)
    message.success('Account deleted')
    await Promise.all([fetchAccounts(), fetchAccountOptions()])
  } catch (error) {
    console.error(error)
    message.error('Failed to delete account')
  }
}

async function handleTransactionDelete(row: TransactionRow) {
  const id = row.id
  if (!id) {
    message.error('Missing transaction id')
    return
  }

  try {
    await transactionApi.deleteTransaction(id)
    message.success('Transaction deleted')
    await Promise.all([fetchTransactions(), fetchAccounts(), fetchAccountOptions()])
  } catch (error) {
    console.error(error)
    message.error('Failed to delete transaction')
  }
}

async function handleAccountSubmit() {
  submittingAccount.value = true
  try {
    const payload: AccountData = {
      accountName: accountFormModel.accountName,
      accountType: accountFormModel.accountType,
      status: accountFormModel.status,
    } as AccountData

    if (isEditingAccount.value && editingAccountId.value != null) {
      await accountApi.updateAccount(editingAccountId.value, payload)
      message.success('Account updated')
    } else {
      await accountApi.postAccount(payload)
      message.success('Account created')
    }

    showAccountEditor.value = false
    await Promise.all([fetchAccounts(), fetchAccountOptions()])
  } catch (error) {
    console.error(error)
    message.error('Failed to save account')
  } finally {
    submittingAccount.value = false
  }
}

async function handleBalanceSubmit() {
  const account = selectedAccountForBalance.value
  const accountId = account?.id
  const amount = Number(balanceFormModel.amount)

  if (!accountId) {
    message.error('Missing account id')
    return
  }

  if (!Number.isFinite(amount) || amount <= 0) {
    message.error('Enter a valid amount')
    return
  }

  if (balanceActionType.value === 'withdraw' && amount > getAccountBalance(account)) {
    message.error('Withdraw amount cannot exceed the current balance')
    return
  }

  submittingBalance.value = true
  try {
    if (balanceActionType.value === 'charge') {
      await accountApi.charge(accountId, amount, balanceFormModel.description || undefined)
      message.success('Account charged')
    } else {
      await accountApi.withdraw(accountId, amount, balanceFormModel.description || undefined)
      message.success('Account withdrawn')
    }

    showBalanceEditor.value = false
    await Promise.all([fetchAccounts(), fetchTransactions(), fetchAccountOptions()])
  } catch (error) {
    console.error(error)
    message.error(`Failed to ${balanceActionType.value} account`)
  } finally {
    submittingBalance.value = false
  }
}

async function handleTransactionSubmit() {
  submittingTransaction.value = true
  try {
    const payload: TransactionData = {
      transactionType: transactionFormModel.transactionType,
      amount: transactionFormModel.amount,
      transactionDate: transactionFormModel.transactionDate,
      referenceType: transactionFormModel.referenceType,
      description: transactionFormModel.description,
      recordedByEmployeeId: transactionFormModel.recordedByEmployeeId,
      accountId: transactionFormModel.accountId,
    }

    if (isEditingTransaction.value && editingTransactionId.value != null) {
      await transactionApi.updateTransaction(editingTransactionId.value, payload)
      message.success('Transaction updated')
    } else {
      await transactionApi.postTransaction(payload)
      message.success('Transaction created')
    }

    showTransactionEditor.value = false
    await Promise.all([fetchTransactions(), fetchAccounts(), fetchAccountOptions()])
  } catch (error) {
    console.error(error)
    message.error('Failed to save transaction')
  } finally {
    submittingTransaction.value = false
  }
}

function handleDateChange(value: number | null) {
  transactionFormModel.transactionDate = value ? new Date(value).toISOString().slice(0, 10) : ''
}

function handleEmployeeChange(value: number) {
  transactionFormModel.recordedByEmployeeId = value
}

function handleAccountChange(value: number) {
  transactionFormModel.accountId = value
}

onMounted(() => {
  fetchAccounts()
  fetchTransactions()
  fetchEmployees()
  fetchAccountOptions()
})
</script>

<template>
  <div class="account-view">
    <n-card size="small" class="panel">
      <div class="section-header">
        <div>
          <h2 class="section-title">Accounts</h2>
          <p class="section-copy">Manage account balances and statuses.</p>
        </div>
      </div>

      <div class="toolbar">
        <n-input
          v-model:value="accountKeyword"
          clearable
          placeholder="Search by name, type or status"
          size="small"
        />
        <n-button type="primary" size="small" @click="openAccountCreate">New Account</n-button>
      </div>

      <div class="table-wrapper">
        <n-data-table
          class="data-table"
          :columns="accountColumns"
          :data="filteredAccounts"
          :loading="accountsLoading"
          :pagination="{ pageSize: 10, simple: true }"
          :scroll-x="800"
          size="small"
          bordered
        />
      </div>
    </n-card>

    <n-card size="small" class="panel">
      <div class="section-header">
        <div>
          <h2 class="section-title">Transactions</h2>
          <p class="section-copy">View and manage all transactions.</p>
        </div>
      </div>

      <div class="toolbar">
        <n-input
          v-model:value="transactionKeyword"
          clearable
          placeholder="Search by type, description or account"
          size="small"
        />
        <n-button type="primary" size="small" @click="openTransactionCreate">New Transaction</n-button>
      </div>

      <div class="table-wrapper">
        <n-data-table
          class="data-table"
          :columns="transactionColumns"
          :data="filteredTransactions"
          :loading="transactionsLoading"
          :pagination="{ pageSize: 10, simple: true }"
          :scroll-x="1000"
          size="small"
          bordered
        />
      </div>
    </n-card>

    <n-modal
      v-model:show="showAccountEditor"
      preset="card"
      style="max-width: 600px;"
      :title="isEditingAccount ? 'Edit Account' : 'New Account'"
      class="account-modal"
    >
      <n-form label-width="120">
        <div class="form-row">
          <n-form-item label="Account name">
            <n-input v-model:value="accountFormModel.accountName as string" placeholder="e.g. Cash on hand" />
          </n-form-item>
          <n-form-item label="Account type">
            <n-select
              v-model:value="accountFormModel.accountType as string"
              :options="accountTypeOptions"
              placeholder="Select type"
            />
          </n-form-item>
          <n-form-item label="Status">
            <n-select
              v-model:value="accountFormModel.status as string"
              :options="statusOptions"
              placeholder="Select status"
            />
          </n-form-item>
        </div>

        <div class="form-actions">
          <n-button size="small" @click="showAccountEditor = false">Cancel</n-button>
          <n-button type="primary" size="small" :loading="submittingAccount" @click="handleAccountSubmit">
            Save
          </n-button>
        </div>
      </n-form>
    </n-modal>

    <n-modal
      v-model:show="showBalanceEditor"
      preset="card"
      style="max-width: 560px;"
      :title="balanceActionType === 'charge' ? 'Charge Account' : 'Withdraw From Account'"
      class="balance-modal"
    >
      <n-form label-width="140">
        <div class="form-row">
          <n-form-item label="Account">
            <n-input :value="selectedAccountForBalance?.accountName as string" disabled />
          </n-form-item>
          <n-form-item label="Current balance">
            <n-input :value="String(selectedAccountBalance)" disabled />
          </n-form-item>
        </div>

        <div class="form-row">
          <n-form-item label="Amount">
            <n-input-number
              v-model:value="balanceFormModel.amount"
              :min="1"
              :precision="0"
              style="width: 100%"
              placeholder="Enter amount"
            />
          </n-form-item>
          <n-form-item label="Description">
            <n-input
              v-model:value="balanceFormModel.description"
              :placeholder="
                balanceActionType === 'charge'
                  ? 'Optional note for this charge'
                  : 'Optional note for this withdrawal'
              "
            />
          </n-form-item>
        </div>

        <div class="form-actions">
          <n-button size="small" @click="showBalanceEditor = false">Cancel</n-button>
          <n-button type="primary" size="small" :loading="submittingBalance" @click="handleBalanceSubmit">
            Save
          </n-button>
        </div>
      </n-form>
    </n-modal>

    <n-modal
      v-model:show="showTransactionEditor"
      preset="card"
      style="max-width: 600px; max-height: 90vh; overflow: auto;"
      :title="isEditingTransaction ? 'Edit Transaction' : 'New Transaction'"
      class="transaction-modal"
    >
      <n-form label-width="160">
        <div class="form-row">
          <n-form-item label="Transaction type">
            <n-select
              v-model:value="transactionFormModel.transactionType as string"
              :options="transactionTypeOptions"
              placeholder="Select type"
            />
          </n-form-item>
          <n-form-item label="Amount">
            <n-input v-model:value="transactionFormModel.amount as any" placeholder="e.g. 2500" />
          </n-form-item>
        </div>

        <div class="form-row">
          <n-form-item label="Transaction date">
            <n-date-picker
              type="date"
              size="small"
              style="width: 100%"
              :value="
                transactionFormModel.transactionDate
                  ? Date.parse(transactionFormModel.transactionDate as string)
                  : null
              "
              @update:value="handleDateChange"
            />
          </n-form-item>
          <n-form-item label="Reference type">
            <n-input
              v-model:value="transactionFormModel.referenceType as string"
              placeholder="e.g. Invoice, Receipt"
            />
          </n-form-item>
        </div>

        <div class="form-row">
          <n-form-item label="Recorded By">
            <n-select
              :options="employeeOptions"
              :value="transactionFormModel.recordedByEmployeeId as number"
              @update:value="handleEmployeeChange"
            />
          </n-form-item>
          <n-form-item label="Account">
            <n-select
              :options="accountOptions"
              :value="transactionFormModel.accountId as number"
              @update:value="handleAccountChange"
            />
          </n-form-item>
        </div>

        <div class="form-row single-column">
          <n-form-item label="Description">
            <n-input
              v-model:value="transactionFormModel.description as string"
              type="textarea"
              placeholder="Short description of the transaction"
            />
          </n-form-item>
        </div>

        <div class="form-actions">
          <n-button size="small" @click="showTransactionEditor = false">Cancel</n-button>
          <n-button
            type="primary"
            size="small"
            :loading="submittingTransaction"
            @click="handleTransactionSubmit"
          >
            Save
          </n-button>
        </div>
      </n-form>
    </n-modal>
  </div>
</template>

<style scoped>
.account-view {
  padding: 16px 20px;
  display: flex;
  flex-direction: column;
  gap: 16px;
  width: 100%;
  min-height: 100%;
  box-sizing: border-box;
}

.panel {
  width: 100%;
  min-height: 0;
  display: flex;
  flex-direction: column;
}

.panel :deep(.n-card__content) {
  display: flex;
  flex-direction: column;
  min-height: 0;
}

.panel :deep(.n-data-table) {
  flex: 1;
  min-width: 0;
}

.section-header {
  margin-bottom: 12px;
}

.section-title {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
}

.section-copy {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 13px;
}

.toolbar {
  display: flex;
  gap: 8px;
  margin-bottom: 12px;
  flex-shrink: 0;
}

.table-wrapper {
  min-width: 0;
  overflow-x: auto;
}

.table-wrapper :deep(.n-data-table-table) {
  min-width: 800px;
}

.form-row {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.single-column {
  grid-template-columns: 1fr;
}

.form-actions {
  margin-top: 16px;
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}

@media (max-width: 768px) {
  .toolbar {
    flex-direction: column;
  }

  .form-row {
    grid-template-columns: 1fr;
  }
}
</style>
