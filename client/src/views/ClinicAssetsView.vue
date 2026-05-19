<template>
  <div class="clinic-assets-page">
    <n-space vertical size="large">
      <n-card class="hero-card" :bordered="false">
        <div class="hero">
          <div>
            <div class="eyebrow">{{ t('clinicAssetsView.hero.eyebrow') }}</div>
            <h1>{{ t('clinicAssetsView.hero.title') }}</h1>
            <p>
              {{ t('clinicAssetsView.hero.description') }}
            </p>
          </div>

          <div class="hero-actions">
            <n-button tertiary @click="handleRefresh" :loading="loading">
              {{ t('clinicAssetsView.hero.refreshButtonText') }}
            </n-button>
            <n-button type="primary" @click="openCreate">
              {{ t('clinicAssetsView.hero.newAssetButtonText') }}
            </n-button>
          </div>
        </div>
      </n-card>

      <n-grid :x-gap="16" :y-gap="16" cols="1 s:2 m:4">
        <n-grid-item>
          <n-card class="metric-card" :bordered="false">
            <div class="metric-label">{{ t('clinicAssetsView.metrics.totalAssets') }}</div>
            <div class="metric-value">{{ filteredAssets.length }}</div>
          </n-card>
        </n-grid-item>
        <n-grid-item>
          <n-card class="metric-card" :bordered="false">
            <div class="metric-label">{{ t('clinicAssetsView.metrics.active') }}</div>
            <div class="metric-value">{{ statusCount.active }}</div>
          </n-card>
        </n-grid-item>
        <n-grid-item>
          <n-card class="metric-card" :bordered="false">
            <div class="metric-label">{{ t('clinicAssetsView.metrics.maintenance') }}</div>
            <div class="metric-value">{{ statusCount.maintenance }}</div>
          </n-card>
        </n-grid-item>
        <n-grid-item>
          <n-card class="metric-card" :bordered="false">
            <div class="metric-label">{{ t('clinicAssetsView.metrics.totalValue') }}</div>
            <div class="metric-value">{{ formatMoney(totalValue) }}</div>
          </n-card>
        </n-grid-item>
      </n-grid>

      <n-card class="toolbar-card" :bordered="false">
        <n-space vertical :size="12">
          <n-space wrap align="center" justify="space-between">
            <n-input v-model:value="query" clearable :placeholder="t('clinicAssetsView.toolbar.searchPlaceholder')"
              class="search-input">
              <template #prefix>
                <Icon icon="solar:magnifer-linear" />
              </template>
            </n-input>

            <n-space wrap>
              <n-select :to="false" v-model:value="categoryFilter" :options="categoryOptionsFilter" clearable
                :placeholder="t('clinicAssetsView.toolbar.categoryPlaceholder')" class="filter-select" />
              <n-select :to="false" v-model:value="statusFilter" :options="statusOptionsFilter" clearable
                :placeholder="t('clinicAssetsView.toolbar.statusPlaceholder')" class="filter-select" />
            </n-space>
          </n-space>
        </n-space>
      </n-card>

      <n-spin :show="loading">
        <template v-if="isMobile">
          <n-space vertical size="medium">
            <n-card v-for="asset in pagedAssets" :key="asset.id ?? `${asset.name}-${asset.assetName}`"
              class="asset-card" :bordered="false">
              <div class="asset-card-top">
                <div>
                  <div class="asset-title">{{ asset.assetName }}</div>
                  <div class="asset-subtitle">{{ asset.name }}</div>
                </div>
                <n-tag :type="statusTagType(asset.status)" round>{{ translateStatus(asset.status) }}</n-tag>
              </div>

              <p class="asset-description">
                {{ asset.description || t('clinicAssetsView.emptyDescription') }}
              </p>

              <div class="asset-meta-grid">
                <div>
                  <span class="meta-label">{{ t('clinicAssetsView.meta.category') }}</span>
                  <span class="meta-value">{{ translateCategory(asset.category) }}</span>
                </div>
                <div>
                  <span class="meta-label">{{ t('clinicAssetsView.meta.price') }}</span>
                  <span class="meta-value">{{ formatMoney(asset.price) }}</span>
                </div>
                <div>
                  <span class="meta-label">{{ t('clinicAssetsView.meta.amount') }}</span>
                  <span class="meta-value">{{ asset.amount }}</span>
                </div>
                <div>
                  <span class="meta-label">{{ t('clinicAssetsView.meta.purchased') }}</span>
                  <span class="meta-value">{{ formatDate(asset.dateOfPurchase) }}</span>
                </div>
              </div>

              <n-space justify="end" class="asset-actions" wrap>
                <n-button secondary size="small" @click="openEdit(asset)">
                  {{ t('common.editButtonText') }}
                </n-button>
                <n-button tertiary size="small" type="error" @click="confirmDelete(asset)">
                  {{ t('common.deleteButtonText') }}
                </n-button>
              </n-space>
            </n-card>
          </n-space>
        </template>

        <template v-else>
          <n-card class="table-card" :bordered="false">
            <n-data-table :columns="columns" :data="pagedAssets" :loading="loading" :pagination="false"
              :bordered="false" :single-line="false" :scroll-x="1200" />
          </n-card>
        </template>
      </n-spin>

      <n-card class="footer-card" :bordered="false">
        <n-space align="center" justify="space-between" wrap>
          <div class="footer-text">
            {{ t('clinicAssetsView.footer.showingAssets', { shown: pagedAssets.length, total: filteredAssets.length })
            }}
          </div>
          <n-pagination v-model:page="page" v-model:page-size="pageSize" :item-count="filteredAssets.length"
            :page-sizes="[5, 10, 20, 50]" show-size-picker :disabled="loading" />
        </n-space>
      </n-card>
    </n-space>

    <n-modal v-model:show="formVisible" preset="card" :title="dialogTitle"
      style="width: min(920px, calc(100vw - 24px)); height:80vh" content-scrollable>
      <n-form ref="formRef" :model="formModel" :rules="rules" label-placement="top" size="large">
        <n-grid :x-gap="16" :y-gap="8" cols="1 s:2 m:2">
          <n-grid-item>
            <n-form-item :label="t('clinicAssetsView.form.nameLabel')" path="name">
              <n-input v-model:value="formModel.name" :placeholder="t('clinicAssetsView.form.namePlaceholder')" />
            </n-form-item>
          </n-grid-item>

          <n-grid-item>
            <n-form-item :label="t('clinicAssetsView.form.assetNameLabel')" path="assetName">
              <n-input v-model:value="formModel.assetName"
                :placeholder="t('clinicAssetsView.form.assetNamePlaceholder')" />
            </n-form-item>
          </n-grid-item>

          <n-grid-item :span="2">
            <n-form-item :label="t('clinicAssetsView.form.descriptionLabel')" path="description">
              <n-input v-model:value="formModel.description" type="textarea"
                :placeholder="t('clinicAssetsView.form.descriptionPlaceholder')"
                :autosize="{ minRows: 3, maxRows: 6 }" />
            </n-form-item>
          </n-grid-item>

          <n-grid-item>
            <n-form-item :label="t('clinicAssetsView.form.categoryLabel')" path="category">
              <n-select :to="false" v-model:value="formModel.category" :options="categoryOptions"
                :placeholder="t('clinicAssetsView.form.categoryPlaceholder')" />
            </n-form-item>
          </n-grid-item>

          <n-grid-item>
            <n-form-item :label="t('clinicAssetsView.form.statusLabel')" path="status">
              <n-select :to="false" v-model:value="formModel.status" :options="statusOptions"
                :placeholder="t('clinicAssetsView.form.statusPlaceholder')" />
            </n-form-item>
          </n-grid-item>

          <n-grid-item>
            <n-form-item :label="t('clinicAssetsView.form.amountLabel')" path="amount">
              <n-input-number v-model:value="formModel.amount" :min="0" :precision="0" />
            </n-form-item>
          </n-grid-item>

          <n-grid-item>
            <n-form-item :label="t('clinicAssetsView.form.priceLabel')" path="price">
              <n-input-number v-model:value="formModel.price" :min="0" :precision="2" />
            </n-form-item>
          </n-grid-item>

          <!-- <n-grid-item>
            <n-form-item label="Currency exchange rate" path="currencyExchangeRate">
              <n-input-number v-model:value="formModel.currencyExchangeRate" :min="0" :precision="6" />
            </n-form-item>
          </n-grid-item> -->

          <n-grid-item>
            <n-form-item :label="t('clinicAssetsView.form.totalAmountLabel')" path="totalAmount">
              <n-input-number v-model:value="formModel.totalAmount" :min="0" :precision="2" />
            </n-form-item>
          </n-grid-item>

          <n-grid-item>
            <n-form-item :label="t('clinicAssetsView.form.dateOfPurchaseLabel')" path="dateOfPurchase">
              <n-date-picker :to="false" v-model:value="purchaseDateValue" type="date" clearable style="width: 100%" />
            </n-form-item>
          </n-grid-item>

          <!-- <n-grid-item>
            <n-form-item label="Purchased by employee ID" path="purchasedByEmployeeId">
              <n-input-number v-model:value="formModel.purchasedByEmployeeId" :min="0" :precision="0" />
            </n-form-item>
          </n-grid-item> -->

          <n-grid-item>
            <!-- <n-form-item label="Sterile" path="isSterile">
              <n-switch v-model:value="formModel.isSterile" />
            </n-form-item> -->
          </n-grid-item>
        </n-grid>
      </n-form>

      <template #footer>
        <n-space justify="end">
          <n-button @click="formVisible = false">{{ t('common.cancelButtonText') }}</n-button>
          <n-button type="primary" :loading="submitting" @click="submitForm">
            {{ dialogActionLabel }}
          </n-button>
        </n-space>
      </template>
    </n-modal>

    <n-modal v-model:show="deleteVisible" preset="dialog" :title="t('clinicAssetsView.deleteModal.title')">
      <div class="delete-body">
        {{ t('clinicAssetsView.deleteModal.body', { assetName: deletingAsset?.assetName ?? '' }) }}
      </div>
      <template #action>
        <n-space justify="end">
          <n-button @click="deleteVisible = false">{{ t('common.cancelButtonText') }}</n-button>
          <n-button type="error" :loading="submitting" @click="deleteAsset">
            {{ t('common.deleteButtonText') }}
          </n-button>
        </n-space>
      </template>
    </n-modal>
  </div>
</template>

<script setup lang="ts">
import { computed, h, onMounted, onBeforeUnmount, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  NButton,
  NCard,
  NDataTable,
  NDatePicker,
  NForm,
  NFormItem,
  NGrid,
  NGridItem,
  NInput,
  NInputNumber,
  NModal,
  NPagination,
  NSelect,
  NSpace,
  NSpin,
  NSwitch,
  NTag,
  type DataTableColumns,
  type FormInst,
  type FormRules,
  useMessage,
} from 'naive-ui'
import { Icon } from '@iconify/vue'
import clinicAssetsApi from '@api/clinicAsset.ts'
import type ClinicAssetData from '@api/interfaces/ClinicAsset'

type ClinicAssetFormData = ClinicAssetData & {
  discountPercentage?: number | null
  currencyExchangeRate?: number | null
}

const { t } = useI18n()
const message = useMessage()
const viewportWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1280)

const handleResize = () => {
  viewportWidth.value = window.innerWidth
}

if (typeof window !== 'undefined') {
  window.addEventListener('resize', handleResize)
}

onBeforeUnmount(() => {
  if (typeof window !== 'undefined') {
    window.removeEventListener('resize', handleResize)
  }
})

const loading = ref(false)
const submitting = ref(false)
const assets = ref<ClinicAssetData[]>([])
const query = ref('')
const categoryFilter = ref<string | null>(null)
const statusFilter = ref<string | null>(null)
const page = ref(1)
const pageSize = ref(10)
const formVisible = ref(false)
const deleteVisible = ref(false)
const editingAssetId = ref<number | null>(null)
const deletingAsset = ref<ClinicAssetData | null>(null)
const formRef = ref<FormInst | null>(null)
const purchaseDateValue = ref<number | null>(null)

const isMobile = computed(() => viewportWidth.value < 768)

const emptyForm = (): ClinicAssetFormData => ({
  name: '',
  assetName: '',
  description: '',
  category: 'device',
  width: undefined,
  height: undefined,
  depth: undefined,
  isSterile: false,
  amount: 0,
  price: 0,
  totalAmount: 0,
  dateOfPurchase: '',
  status: 'active',
  purchasedByEmployeeId: undefined,
  discountPercentage: undefined,
  currencyExchangeRate: undefined,
})

const formModel = reactive<ClinicAssetFormData>(emptyForm())

const categoryOptions = computed(() => [
  { label: t('clinicAssetsView.categoryOptions.device'), value: 'device' },
  { label: t('clinicAssetsView.categoryOptions.furniture'), value: 'furniture' },
])

const statusOptions = computed(() => [
  { label: t('clinicAssetsView.statusOptions.active'), value: 'active' },
  { label: t('clinicAssetsView.statusOptions.inactive'), value: 'inactive' },
  { label: t('clinicAssetsView.statusOptions.maintenance'), value: 'maintenance' },
])

const categoryOptionsFilter = computed(() => [
  { label: t('clinicAssetsView.categoryOptions.device'), value: 'device' },
  { label: t('clinicAssetsView.categoryOptions.furniture'), value: 'furniture' },
])

const statusOptionsFilter = computed(() => [
  { label: t('clinicAssetsView.statusOptions.active'), value: 'active' },
  { label: t('clinicAssetsView.statusOptions.inactive'), value: 'inactive' },
  { label: t('clinicAssetsView.statusOptions.maintenance'), value: 'maintenance' },
])

const rules = computed<FormRules>(() => ({
  name: [{ required: true, message: t('clinicAssetsView.validation.nameRequired'), trigger: ['input', 'blur'] }],
  assetName: [{ required: true, message: t('clinicAssetsView.validation.assetNameRequired'), trigger: ['input', 'blur'] }],
  category: [{ required: true, message: t('clinicAssetsView.validation.categoryRequired'), trigger: ['change', 'blur'] }],
  amount: [{ required: true, type: 'number', message: t('clinicAssetsView.validation.amountRequired'), trigger: ['blur', 'change'] }],
  price: [{ required: true, type: 'number', message: t('clinicAssetsView.validation.priceRequired'), trigger: ['blur', 'change'] }],
  totalAmount: [{ required: true, type: 'number', message: t('clinicAssetsView.validation.totalAmountRequired'), trigger: ['blur', 'change'] }],
  dateOfPurchase: [{ required: true, message: t('clinicAssetsView.validation.dateOfPurchaseRequired'), trigger: ['change', 'blur'] }],
  status: [{ required: true, message: t('clinicAssetsView.validation.statusRequired'), trigger: ['change', 'blur'] }],
}))

const formatMoney = (value?: number | null) => {
  const numberValue = Number(value ?? 0)
  return `${numberValue.toLocaleString()} AFG`
}

const formatDate = (value?: string | null) => {
  if (!value) return '—'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  return new Intl.DateTimeFormat(undefined, { year: 'numeric', month: 'short', day: 'numeric' }).format(date)
}

const translateCategory = (category?: string | null) => {
  switch (category) {
    case 'device':
      return t('clinicAssetsView.categoryOptions.device')
    case 'furniture':
      return t('clinicAssetsView.categoryOptions.furniture')
    default:
      return category ?? t('common.noDataAvailable')
  }
}

const translateStatus = (status?: string | null) => {
  switch (status) {
    case 'active':
      return t('clinicAssetsView.statusOptions.active')
    case 'inactive':
      return t('clinicAssetsView.statusOptions.inactive')
    case 'maintenance':
      return t('clinicAssetsView.statusOptions.maintenance')
    default:
      return status ?? t('common.noDataAvailable')
  }
}

const statusTagType = (status?: string) => {
  switch (status) {
    case 'active':
      return 'success'
    case 'maintenance':
      return 'warning'
    case 'inactive':
      return 'default'
    default:
      return 'info'
  }
}

const normalizeForm = (asset: ClinicAssetFormData): ClinicAssetFormData => ({
  ...asset,
  description: asset.description ?? '',
  isSterile: Boolean(asset.isSterile),
  width: asset.width ?? undefined,
  height: asset.height ?? undefined,
  depth: asset.depth ?? undefined,
  purchasedByEmployeeId: asset.purchasedByEmployeeId ?? undefined,
  totalAmount: asset.totalAmount ?? 0,
  discountPercentage: asset.discountPercentage ?? undefined,
  currencyExchangeRate: asset.currencyExchangeRate ?? undefined,
})

const resetForm = () => {
  Object.assign(formModel, emptyForm())
  purchaseDateValue.value = null
}

const hydrateForm = (asset?: ClinicAssetData) => {
  const value = normalizeForm({ ...(asset ?? emptyForm()) })
  Object.assign(formModel, value)
  purchaseDateValue.value = value.dateOfPurchase ? new Date(value.dateOfPurchase).getTime() : null
}

const loadAssets = async () => {
  loading.value = true
  try {
    const response = await clinicAssetsApi.getBranchClinicAssets()
    assets.value = Array.isArray(response.data) ? response.data : response.data?.data ?? []
  } catch {
    message.error(t('clinicAssetsView.messages.loadAssetsError'))
  } finally {
    loading.value = false
  }
}

const handleRefresh = async () => {
  await loadAssets()
}

onMounted(loadAssets)

watch([query, categoryFilter, statusFilter], () => {
  page.value = 1
})

watch(
  purchaseDateValue,
  (value) => {
    formModel.dateOfPurchase = value ? new Date(value).toISOString().slice(0, 10) : ''
  },
  { immediate: true }
)

const filteredAssets = computed(() => {
  const q = query.value.trim().toLowerCase()
  return assets.value.filter((asset) => {
    const matchesQuery =
      !q ||
      [asset.name, asset.assetName, asset.description, asset.category, asset.status]
        .filter(Boolean)
        .some((item) => String(item).toLowerCase().includes(q))

    const matchesCategory = !categoryFilter.value || asset.category === categoryFilter.value
    const matchesStatus = !statusFilter.value || asset.status === statusFilter.value

    return matchesQuery && matchesCategory && matchesStatus
  })
})

const totalValue = computed(() =>
  filteredAssets.value.reduce((sum, asset) => sum + Number(asset.totalAmount ?? 0), 0)
)

const statusCount = computed(() => {
  return filteredAssets.value.reduce(
    (acc, asset) => {
      const key = asset.status as keyof typeof acc
      if (key in acc) acc[key] += 1
      return acc
    },
    { active: 0, inactive: 0, maintenance: 0 }
  )
})

const pagedAssets = computed(() => {
  const start = (page.value - 1) * pageSize.value
  return filteredAssets.value.slice(start, start + pageSize.value)
})

const dialogTitle = computed(() =>
  editingAssetId.value == null
    ? t('clinicAssetsView.modal.createTitle')
    : t('clinicAssetsView.modal.editTitle')
)

const dialogActionLabel = computed(() =>
  editingAssetId.value == null
    ? t('clinicAssetsView.modal.createActionButtonText')
    : t('clinicAssetsView.modal.saveActionButtonText')
)

const openCreate = () => {
  editingAssetId.value = null
  resetForm()
  formVisible.value = true
}

const openEdit = (asset: ClinicAssetData) => {
  editingAssetId.value = asset.id ?? null
  hydrateForm(asset)
  formVisible.value = true
}

const buildPayload = (): Record<string, unknown> => {
  return {
    name: formModel.name.trim(),
    assetName: formModel.assetName.trim(),
    description: formModel.description?.trim() || null,
    category: formModel.category,
    width: formModel.width === null || formModel.width === undefined ? null : Number(formModel.width),
    height: formModel.height === null || formModel.height === undefined ? null : Number(formModel.height),
    depth: formModel.depth === null || formModel.depth === undefined ? null : Number(formModel.depth),
    isSterile: Boolean(formModel.isSterile),
    amount: Number(formModel.amount ?? 0),
    price: Number(formModel.price ?? 0),
    totalAmount: Number(formModel.totalAmount ?? 0),
    dateOfPurchase: formModel.dateOfPurchase || null,
    status: formModel.status,
    purchasedByEmployeeId:
      formModel.purchasedByEmployeeId === null || formModel.purchasedByEmployeeId === undefined
        ? null
        : Number(formModel.purchasedByEmployeeId),
    discountPercentage:
      formModel.discountPercentage === null || formModel.discountPercentage === undefined
        ? null
        : Number(formModel.discountPercentage),
    currencyExchangeRate:
      formModel.currencyExchangeRate === null || formModel.currencyExchangeRate === undefined
        ? null
        : Number(formModel.currencyExchangeRate),
  }
}

const submitForm = async () => {
  const payload = buildPayload() as unknown as ClinicAssetData

  try {
    await formRef.value?.validate()
    if (!payload.dateOfPurchase) {
      message.error(t('clinicAssetsView.validation.dateOfPurchaseRequired'))
      return
    }

    submitting.value = true
    if (editingAssetId.value == null) {
      await clinicAssetsApi.postClinicAsset(payload)
      message.success(t('clinicAssetsView.messages.assetCreatedSuccess'))
    } else {
      await clinicAssetsApi.updateClinicAsset(editingAssetId.value, payload)
      message.success(t('clinicAssetsView.messages.assetUpdatedSuccess'))
    }

    formVisible.value = false
    await loadAssets()
  } catch (error: any) {
    const errors = error?.response?.data?.errors
    const apiMessage = errors
      ? Object.values(errors)
        .flat()
        .filter(Boolean)
        .join(' ')
      : error?.response?.data?.message || error?.response?.data?.error || null

    if (apiMessage) {
      message.error(String(apiMessage))
      return
    }

    if (error instanceof Error && error.message) {
      message.error(error.message)
    } else {
      message.error(t('clinicAssetsView.messages.saveAssetError'))
    }
  } finally {
    submitting.value = false
  }
}

const confirmDelete = (asset: ClinicAssetData) => {
  deletingAsset.value = asset
  deleteVisible.value = true
}

const deleteAsset = async () => {
  if (!deletingAsset.value?.id) return

  try {
    submitting.value = true
    await clinicAssetsApi.deleteClinicAsset(deletingAsset.value.id)
    message.success(t('clinicAssetsView.messages.assetDeletedSuccess'))
    deleteVisible.value = false
    deletingAsset.value = null
    await loadAssets()
  } catch {
    message.error(t('clinicAssetsView.messages.deleteAssetError'))
  } finally {
    submitting.value = false
  }
}

const columns = computed<DataTableColumns<ClinicAssetData>>(() => [
  {
    title: t('clinicAssetsView.columns.asset'),
    key: 'assetName',
    width: 220,
    render(row) {
      return h('div', { class: 'table-asset' }, [
        h('div', { class: 'table-asset-title' }, row.assetName),
        h('div', { class: 'table-asset-subtitle' }, row.name),
      ])
    },
  },
  {
    title: t('clinicAssetsView.columns.category'),
    key: 'category',
    width: 120,
    render(row) {
      return h(NTag, { size: 'small', round: true }, { default: () => translateCategory(row.category) })
    },
  },
  {
    title: t('clinicAssetsView.columns.status'),
    key: 'status',
    width: 140,
    render(row) {
      return h(NTag, { size: 'small', round: true, type: statusTagType(row.status) as any }, { default: () => translateStatus(row.status) })
    },
  },
  {
    title: t('clinicAssetsView.columns.amount'),
    key: 'amount',
    width: 90,
  },
  {
    title: t('clinicAssetsView.columns.price'),
    key: 'price',
    width: 120,
    render(row) {
      return formatMoney(row.price)
    },
  },
  {
    title: t('clinicAssetsView.columns.totalAmount'),
    key: 'totalAmount',
    width: 130,
    render(row) {
      return formatMoney(row.totalAmount)
    },
  },
  {
    title: t('clinicAssetsView.columns.purchaseDate'),
    key: 'dateOfPurchase',
    width: 140,
    render(row) {
      return formatDate(row.dateOfPurchase)
    },
  },
  // {
  //   title: t('clinicAssetsView.columns.sterile'),
  //   key: 'isSterile',
  //   width: 90,
  //   render(row) {
  //     return row.isSterile ? 'Yes' : 'No'
  //   },
  // },
  {
    title: t('clinicAssetsView.columns.actions'),
    key: 'actions',
    width: 160,
    fixed: 'right',
    render(row) {
      return h(
        NSpace,
        { size: 8 },
        {
          default: () => [
            h(
              NButton,
              { size: 'small', tertiary: true, onClick: () => openEdit(row) },
              { default: () => t('common.editButtonText'), icon: () => h(Icon, { icon: 'solar:pen-2-linear' }) }
            ),
            h(
              NButton,
              { size: 'small', tertiary: true, type: 'error', onClick: () => confirmDelete(row) },
              { default: () => t('common.deleteButtonText'), icon: () => h(Icon, { icon: 'solar:trash-bin-trash-linear' }) }
            ),
          ],
        }
      )
    },
  },
])
</script>

<style scoped>
.clinic-assets-page {
  padding: 16px;
  background: linear-gradient(180deg, #f8fbff 0%, #f6f8fc 100%);
  min-height: 100%;
}

.hero-card,
.toolbar-card,
.table-card,
.footer-card,
.metric-card,
.asset-card {
  border-radius: 24px;
  box-shadow: 0 10px 30px rgba(17, 24, 39, 0.06);
  background: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(10px);
}

.hero {
  display: flex;
  justify-content: space-between;
  gap: 20px;
  align-items: flex-start;
}

.eyebrow {
  text-transform: uppercase;
  letter-spacing: 0.14em;
  font-size: 12px;
  color: #6b7280;
  margin-bottom: 10px;
}

h1 {
  margin: 0;
  font-size: clamp(1.8rem, 2.5vw, 2.7rem);
  line-height: 1.1;
  color: #111827;
}

p {
  margin: 10px 0 0;
  color: #4b5563;
  max-width: 56ch;
}

.hero-actions {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.metric-card {
  padding: 6px 0;
}

.metric-label {
  font-size: 13px;
  color: #6b7280;
}

.metric-value {
  margin-top: 6px;
  font-size: 1.7rem;
  font-weight: 700;
  color: #111827;
}

.search-input {
  min-width: min(520px, 100%);
  flex: 1;
}

.filter-select {
  min-width: 160px;
}

.asset-card {
  padding: 4px;
}

.asset-card-top {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  align-items: flex-start;
}

.asset-title {
  font-weight: 700;
  color: #111827;
  font-size: 1.02rem;
}

.asset-subtitle {
  color: #6b7280;
  margin-top: 4px;
  font-size: 0.92rem;
}

.asset-description {
  margin: 14px 0 0;
  color: #4b5563;
}

.asset-meta-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
  margin-top: 16px;
}

.meta-label {
  display: block;
  font-size: 12px;
  color: #6b7280;
}

.meta-value {
  display: block;
  margin-top: 4px;
  font-weight: 600;
  color: #111827;
}

.asset-actions {
  margin-top: 16px;
}

.footer-card {
  padding-top: 2px;
}

.footer-text {
  color: #6b7280;
}

.table-asset-title {
  font-weight: 600;
  color: #111827;
}

.table-asset-subtitle {
  margin-top: 4px;
  font-size: 12px;
  color: #6b7280;
}

.delete-body {
  color: #374151;
  line-height: 1.6;
}

@media (max-width: 768px) {
  .clinic-assets-page {
    padding: 12px;
  }

  .hero {
    flex-direction: column;
  }

  .hero-actions {
    width: 100%;
  }

  .hero-actions .n-button {
    flex: 1;
  }

  .search-input,
  .filter-select {
    width: 100%;
    min-width: 0;
  }
}
</style>
