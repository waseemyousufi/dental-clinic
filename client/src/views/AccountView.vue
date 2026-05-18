<script setup lang="ts">
import { ref, reactive, computed, onMounted, h } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
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
const { t } = useI18n()

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
  { label: t('accountView.accountTypeOptions.asset'), value: 'asset' },
  { label: t('accountView.accountTypeOptions.income'), value: 'income' },
  { label: t('accountView.accountTypeOptions.expense'), value: 'expense' },
]

const statusOptions = [
  { label: t('accountView.statusOptions.active'), value: 'active' },
  { label: t('accountView.statusOptions.inactive'), value: 'inactive' },
]

const transactionTypeOptions = [
  { label: t('accountView.transactionTypeOptions.in'), value: 'in' },
  { label: t('accountView.transactionTypeOptions.out'), value: 'out' },
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
  if (isOnlyIncomeAccount(row)) return t('accountView.messages.onlyIncomeAccountDelete')
  if (hasAccountCredit(row)) return t('accountView.messages.creditAccountDelete')
  return t('accountView.actions.deleteTooltip')
}

const accountColumns = [
  {
    title: t('accountView.columns.accountName'),
    key: 'accountName',
    ellipsis: { tooltip: true },
  },
  {
    title: t('accountView.columns.accountType'),
    key: 'accountType',
  },
  {
    title: t('accountView.columns.totalAmount'),
    key: 'totalAmount',
    width: 140,
    render(row: AccountRow) {
      const value = row.totalAmount as any
      return typeof value === 'number' ? value.toLocaleString() : String(value ?? '')
    },
  },
  {
    title: t('accountView.columns.status'),
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
        { default: () => row.status || t('accountView.fallbackValue') },
      )
    },
  },
  {
    title: t('accountView.columns.actions'),
    key: 'actions',
    render(row: AccountRow) {
      const deleteDisabled = isAccountDeleteDisabled(row)
      return h('div', { style: 'display: flex; gap: 8px; align-items: center;' }, [
        h(Icon, {
          icon: 'akar-icons:edit',
          title: t('accountView.actions.editTooltip'),
          width: 20,
          height: 20,
          color: '#4f46e5',
          style: { cursor: 'pointer' },
          onClick: () => handleAccountEdit(row),
        }),
        h(Icon, {
          icon: 'material-symbols:download-rounded',
          title: t('accountView.balanceActions.charge'),
          width: 20,
          height: 20,
          color: '#0f766e',
          style: { cursor: 'pointer' },
          onClick: () => openBalanceEditor('charge', row),
        }),
        h(Icon, {
          icon: 'material-symbols:upload-rounded',
          title: t('accountView.balanceActions.withdraw'),
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
                title: deleteDisabled ? undefined : t('accountView.actions.deleteTooltip'),
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
    title: t('accountView.transactionColumns.type'),
    key: 'transactionType',
    render(row: TransactionRow) {
      return h(NHighlight, {
        text: row.transactionType as string,
        patterns: [row.transactionType as string],
        highlightStyle: {
          backgroundColor: row.transactionType === 'in' ? '#16ff4a77' : row.transactionType === 'voided' ? '#f59e0b77' : row.transactionType === 'charge' ? '#16ff4a77' : row.transactionType === 'withdraw' ? '#e30e0eaa' : '#e30e0eaa' ,
          padding: '2px 4px',
          borderRadius: '4px',
          textTransform: 'uppercase',
        },
      })
    },
  },
  {
    title: t('accountView.transactionColumns.amount'),
    key: 'amount',
    width: 120,
    render(row: TransactionRow) {
      const value = row.amount as any
      return typeof value === 'number' ? value.toLocaleString() : String(value ?? '')
    },
  },
  {
    title: t('accountView.transactionColumns.date'),
    key: 'transactionDate',
  },
  {
    title: t('accountView.transactionColumns.referenceType'),
    key: 'referenceType',
    ellipsis: { tooltip: true },
  },
  {
    title: t('accountView.transactionColumns.description'),
    key: 'description',
    ellipsis: { tooltip: true },
  },
  {
    title: t('accountView.transactionColumns.recordedBy'),
    key: 'recordedByEmployee',
    width: 160,
  },
  {
    title: t('accountView.transactionColumns.account'),
    key: 'account',
    width: 120,
  },
  // {
  //   title: 'Actions',
  //   key: 'actions',
  //   render(row: TransactionRow) {
  //     return h('div', { style: 'display: flex; gap: 8px; align-items: center;' }, [
  //       // h(Icon, {
  //       //   icon: 'akar-icons:edit',
  //       //   title: 'Edit',
  //       //   width: 20,
  //       //   height: 20,
  //       //   color: '#4f46e5',
  //       //   style: { cursor: 'pointer' },
  //       //   onClick: () => handleTransactionEdit(row),
  //       // }),
  //       h(Icon, {
  //         icon: 'fluent:delete-16-filled',
  //         title: 'Delete',
  //         width: 20,
  //         height: 20,
  //         color: '#dc2626',
  //         style: { cursor: 'pointer' },
  //         onClick: () => handleTransactionDelete(row),
  //       }),
  //     ])
  //   },
  // },
]

async function fetchAccounts() {
  try {
    accountsLoading.value = true
    const { data } = await accountApi.getBranchAccounts(true, getEffectiveBranchId())
    accounts.value = data.data as AccountRow[]
  } catch (error) {
    console.error(error)
    message.error(t('accountView.messages.loadAccountsError'))
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
    message.error(t('accountView.messages.loadTransactionsError'))
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
    message.error(t('accountView.messages.loadEmployeesError'))
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
    message.error(t('accountView.messages.loadAccountsError'))
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
    message.error(t('accountView.messages.missingAccountIdError'))
    return
  }

  try {
    await accountApi.deleteAccount(id)
    message.success(t('accountView.messages.accountDeletedSuccess'))
    await Promise.all([fetchAccounts(), fetchAccountOptions()])
  } catch (error) {
    console.error(error)
    message.error(t('accountView.messages.deleteAccountError'))
  }
}

async function handleTransactionDelete(row: TransactionRow) {
  const id = row.id
  if (!id) {
    message.error(t('accountView.messages.missingTransactionIdError'))
    return
  }

  try {
    await transactionApi.deleteTransaction(id)
    message.success(t('accountView.messages.transactionVoidedSuccess'))
    await Promise.all([fetchTransactions(), fetchAccounts(), fetchAccountOptions()])
  } catch (error) {
    console.error(error)
    message.error(t('accountView.messages.deleteTransactionError'))
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
      message.success(t('accountView.messages.accountUpdatedSuccess'))
    } else {
      await accountApi.postAccount(payload)
      message.success(t('accountView.messages.accountCreatedSuccess'))
    }

    showAccountEditor.value = false
    await Promise.all([fetchAccounts(), fetchAccountOptions()])
  } catch (error) {
    console.error(error)
    message.error(t('accountView.messages.saveAccountError'))
  } finally {
    submittingAccount.value = false
  }
}

async function handleBalanceSubmit() {
  const account = selectedAccountForBalance.value
  const accountId = account?.id
  const amount = Number(balanceFormModel.amount)

  if (!accountId) {
    message.error(t('accountView.messages.missingAccountIdError'))
    return
  }

  if (!Number.isFinite(amount) || amount <= 0) {
    message.error(t('accountView.messages.enterValidAmountError'))
    return
  }

  if (balanceActionType.value === 'withdraw' && amount > getAccountBalance(account)) {
    message.error(t('accountView.messages.withdrawAmountExceedsBalance'))
    return
  }

  submittingBalance.value = true
  try {
    if (balanceActionType.value === 'charge') {
      await accountApi.charge(accountId, amount, balanceFormModel.description || undefined)
      message.success(t('accountView.messages.accountChargedSuccess'))
    } else {
      await accountApi.withdraw(accountId, amount, balanceFormModel.description || undefined)
      message.success(t('accountView.messages.accountWithdrawnSuccess'))
    }

    showBalanceEditor.value = false
    await Promise.all([fetchAccounts(), fetchTransactions(), fetchAccountOptions()])
  } catch (error) {
    console.error(error)
    message.error(t('accountView.messages.failedBalanceAction', { action: balanceActionType.value }))
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
      message.success(t('accountView.messages.transactionUpdatedSuccess'))
    } else {
      await transactionApi.postTransaction(payload)
      message.success(t('accountView.messages.transactionCreatedSuccess'))
    }

    showTransactionEditor.value = false
    await Promise.all([fetchTransactions(), fetchAccounts(), fetchAccountOptions()])
  } catch (error) {
    console.error(error)
    message.error(t('accountView.messages.saveTransactionError'))
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
          <h2 class="section-title">{{ t('accountView.accountsHeaderTitle') }}</h2>
          <p class="section-copy">{{ t('accountView.accountsHeaderCopy') }}</p>
        </div>
      </div>

      <div class="toolbar">
        <n-input
          v-model:value="accountKeyword"
          clearable
          :placeholder="t('accountView.searchPlaceholder')"
          size="small"
        />
        <n-button type="primary" size="small" @click="openAccountCreate">{{ t('accountView.newAccountButtonText') }}</n-button>
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
          <h2 class="section-title">{{ t('accountView.transactionsHeaderTitle') }}</h2>
          <p class="section-copy">{{ t('accountView.transactionsHeaderCopy') }}</p>
        </div>
      </div>

      <div class="toolbar">
        <n-input
          v-model:value="transactionKeyword"
          clearable
          :placeholder="t('accountView.transactionSearchPlaceholder')"
          size="small"
        />
        <!-- <n-button type="primary" size="small" @click="openTransactionCreate">{{ t('accountView.newTransactionButtonText') }}</n-button> -->
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
      :title="isEditingAccount ? t('accountView.modal.editTitle') : t('accountView.modal.newTitle')"
      class="account-modal"
    >
      <n-form label-width="120">
        <div class="form-row">
          <n-form-item :label="t('accountView.form.accountNameLabel')">
            <n-input v-model:value="accountFormModel.accountName as string" :placeholder="t('accountView.form.accountNamePlaceholder')" />
          </n-form-item>
          <n-form-item :label="t('accountView.form.accountTypeLabel')">
            <n-select
              v-model:value="accountFormModel.accountType as string"
              :options="accountTypeOptions"
              :placeholder="t('accountView.form.accountTypePlaceholder')"
            />
          </n-form-item>
          <n-form-item :label="t('accountView.form.statusLabel')">
            <n-select
              v-model:value="accountFormModel.status as string"
              :options="statusOptions"
              :placeholder="t('accountView.form.statusPlaceholder')"
            />
          </n-form-item>
        </div>

   <div class="form-actions">
          <n-button size="small" @click="showAccountEditor = false">{{ t('common.cancelButtonText') }}</n-button>
          <n-button type="primary" size="small" :loading="submittingAccount" @click="handleAccountSubmit">
            {{ t('common.saveButtonText') }}
          </n-button>
        </div>
      </n-form>
    </n-modal>

    <n-modal
      v-model:show="showBalanceEditor"
      preset="card"
      style="max-width: 560px;"
      :title="balanceActionType === 'charge' ? t('accountView.balanceModal.chargeTitle') : t('accountView.balanceModal.withdrawTitle')"
      class="balance-modal"
    >
      <n-form label-width="140">
        <div class="form-row">
          <n-form-item :label="t('accountView.balanceModal.accountLabel')">
            <n-input :value="selectedAccountForBalance?.accountName as string" disabled />
          </n-form-item>
          <n-form-item :label="t('accountView.balanceModal.currentBalanceLabel')">
            <n-input :value="String(selectedAccountBalance)" disabled />
          </n-form-item>
        </div>

        <div class="form-row">
          <n-form-item :label="t('accountView.balanceModal.amountLabel')">
            <n-input-number
              v-model:value="balanceFormModel.amount"
              :min="1"
              :precision="0"
              style="width: 100%"
              :placeholder="t('accountView.balanceModal.amountPlaceholder')"
            />
          </n-form-item>
          <n-form-item :label="t('accountView.balanceModal.descriptionLabel')">
            <n-input
              v-model:value="balanceFormModel.description"
              :placeholder="
                balanceActionType === 'charge'
                  ? t('accountView.balanceModal.chargeDescriptionPlaceholder')
                  : t('accountView.balanceModal.withdrawDescriptionPlaceholder')
              "
            />
          </n-form-item>
        </div>

        <div class="form-actions">
          <n-button size="small" @click="showBalanceEditor = false">{{ t('common.cancelButtonText') }}</n-button>
          <n-button type="primary" size="small" :loading="submittingBalance" @click="handleBalanceSubmit">
            {{ t('common.saveButtonText') }}
          </n-button>
        </div>
      </n-form>
    </n-modal>

    <n-modal
      v-model:show="showTransactionEditor"
      preset="card"
      style="max-width: 600px; max-height: 90vh; overflow: auto;"
      :title="isEditingTransaction ? t('accountView.transactionModal.editTitle') : t('accountView.transactionModal.newTitle')"
      class="transaction-modal"
    >
      <n-form label-width="160">
        <div class="form-row">
          <n-form-item :label="t('accountView.transactionForm.transactionTypeLabel')">
            <n-select
              v-model:value="transactionFormModel.transactionType as string"
              :options="transactionTypeOptions"
              :placeholder="t('accountView.transactionForm.transactionTypePlaceholder')"
            />
          </n-form-item>
          <n-form-item :label="t('accountView.transactionForm.amountLabel')">
            <n-input v-model:value="transactionFormModel.amount as any" :placeholder="t('accountView.transactionForm.amountPlaceholder')" />
          </n-form-item>
        </div>

        <div class="form-row">
          <n-form-item :label="t('accountView.transactionForm.transactionDateLabel')">
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
          <n-form-item :label="t('accountView.transactionForm.referenceTypeLabel')">
            <n-input
              v-model:value="transactionFormModel.referenceType as string"
              :placeholder="t('accountView.transactionForm.referenceTypePlaceholder')"
            />
          </n-form-item>
        </div>

        <div class="form-row">
          <n-form-item :label="t('accountView.transactionForm.recordedByLabel')">
            <n-select
              :options="employeeOptions"
              :value="transactionFormModel.recordedByEmployeeId as number"
              @update:value="handleEmployeeChange"
            />
          </n-form-item>
          <n-form-item :label="t('accountView.transactionForm.accountLabel')">
            <n-select
              :options="accountOptions"
              :value="transactionFormModel.accountId as number"
              @update:value="handleAccountChange"
            />
          </n-form-item>
        </div>

        <div class="form-row single-column">
          <n-form-item :label="t('accountView.transactionForm.descriptionLabel')">
            <n-input
              v-model:value="transactionFormModel.description as string"
              type="textarea"
              :placeholder="t('accountView.transactionForm.descriptionPlaceholder')"
            />
          </n-form-item>
        </div>

        <div class="form-actions">
          <n-button size="small" @click="showTransactionEditor = false">{{ t('common.cancelButtonText') }}</n-button>
          <n-button
            type="primary"
            size="small"
            :loading="submittingTransaction"
            @click="handleTransactionSubmit"
          >
            {{ t('common.saveButtonText') }}
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
