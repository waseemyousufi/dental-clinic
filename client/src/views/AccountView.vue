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
  useMessage,
  NTag,
} from 'naive-ui'
// import { Pencil, Trash } from '@vicons/ionicons5'

import accountApi from '@api/account'
import type AccountData from '@api/interfaces/Account'
import { Icon } from "@iconify/vue";

type AccountRow = AccountData & { id?: number }

const message = useMessage()

const loading = ref(false)
const accounts = ref<AccountRow[]>([])
const keyword = ref('')

const showEditor = ref(false)
const submitting = ref(false)
const isEditing = ref(false)
const editingId = ref<number | null>(null)

const formModel = reactive<AccountRow>({
  accountName: '',
  accountType: '',
  totalAmount: 0 as unknown as number,
  status: '',
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

const filteredAccounts = computed(() => {
  if (!keyword.value.trim()) return accounts.value
  const k = keyword.value.trim().toLowerCase()
  return accounts.value.filter((a: AccountRow) =>
    [a.accountName, a.accountType, a.status].some((field) =>
      String(field || '')
        .toLowerCase()
        .includes(k),
    ),
  )
})

const columns = [
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
      const v = row.totalAmount as any
      return typeof v === 'number' ? v.toLocaleString() : String(v ?? '')
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
      return h('div', { style: 'display: flex; gap: 8px; align-items: center;' }, [
        h(
          Icon,
          {
            icon: 'akar-icons:edit',
            title: 'Edit',
            width: 20,
            height: 20,
            color: '#4f46e5',
            style: { cursor: 'pointer' },
            onClick: () => handleEdit(row),
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

async function fetchAccounts() {
  try {
    loading.value = true
    const { data } = await accountApi.getBranchAccounts(true)
    accounts.value = data.data as AccountRow[]
  } catch (error) {
    console.error(error)
    message.error('Failed to load accounts')
  } finally {
    loading.value = false
  }
}

function resetForm() {
  formModel.accountName = ''
  formModel.accountType = ''
  formModel.totalAmount = 0 as unknown as number
  formModel.status = ''
}

function openCreate() {
  isEditing.value = false
  editingId.value = null
  resetForm()
  showEditor.value = true
}

function handleEdit(row: AccountRow) {
  isEditing.value = true
  editingId.value = (row as any).id ?? null

  formModel.accountName = String(row.accountName || '')
  formModel.accountType = String(row.accountType || '')
  formModel.totalAmount = (row.totalAmount as any) ?? (0 as unknown as number)
  formModel.status = String(row.status || '')

  showEditor.value = true
}

async function handleDelete(row: AccountRow) {
  const id = (row as any).id
  if (!id) {
    message.error('Missing account id')
    return
  }
  try {
    await accountApi.deleteAccount(id)
    message.success('Account deleted')
    fetchAccounts()
  } catch (error) {
    console.error(error)
    message.error('Failed to delete account')
  }
}

async function handleSubmit() {
  submitting.value = true
  try {
    const payload: AccountData = {
      accountName: formModel.accountName,
      accountType: formModel.accountType,
      totalAmount: formModel.totalAmount,
      status: formModel.status,
    }

    if (isEditing.value && editingId.value != null) {
      await accountApi.updateAccount(editingId.value, payload)
      message.success('Account updated')
    } else {
      await accountApi.postAccount(payload)
      message.success('Account created')
    }

    showEditor.value = false
    await fetchAccounts()
  } catch (error) {
    console.error(error)
    message.error('Failed to save account')
  } finally {
    submitting.value = false
  }
}

onMounted(fetchAccounts)
</script>

<template>
  <div class="account-view">
    <n-card size="small" class="account-panel">
      <div class="toolbar">
        <n-input v-model:value="keyword" clearable placeholder="Search by name, type or status" size="small" />
        <n-button type="primary" size="small" @click="openCreate"> New Account </n-button>
      </div>

      <div class="table-wrapper">
        <n-data-table class="data-table" :columns="columns" :data="filteredAccounts" :loading="loading"
          :pagination="{ pageSize: 10, simple: true }" :scroll-x="800" size="small" bordered />
      </div>
    </n-card>

    <n-modal v-model:show="showEditor" preset="card" style="max-width: 600px;"
      :title="isEditing ? 'Edit Account' : 'New Account'" class="account-modal">
      <n-form label-width="120">
        <div class="form-row">
          <n-form-item label="Account name">
            <n-input v-model:value="formModel.accountName as string" placeholder="e.g. Cash on hand" />
          </n-form-item>
          <n-form-item label="Account type">
            <n-select v-model:value="formModel.accountType as string" :options="accountTypeOptions"
              placeholder="Select type" />
          </n-form-item>
        </div>

        <div class="form-row">
          <n-form-item label="Total amount">
            <n-input v-model:value="formModel.totalAmount as any" placeholder="e.g. 100000" />
          </n-form-item>
          <n-form-item label="Status">
            <n-select v-model:value="formModel.status as string" :options="statusOptions" placeholder="Select status" />
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
  </div>
</template>

<style scoped>
.account-view {
  padding: 16px 20px;
  display: flex;
  flex-direction: column;
  width: 100%;
  min-height: 100%;
  box-sizing: border-box;
}

.account-panel {
  width: 100%;
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: column;
}

.account-panel :deep(.n-card__content) {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-height: 0;
}

.account-panel :deep(.n-data-table) {
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
}

.table-wrapper :deep(.n-data-table-table) {
  min-width: 800px;
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
