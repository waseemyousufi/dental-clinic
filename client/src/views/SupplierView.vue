<template>
  <n-space vertical :size="24" class="supplier-view">
    <!-- Header -->
    <div class="view-header">
      <div class="header-copy">
        <h2 class="view-title">
          <n-icon size="24" class="title-icon">
            <Icon icon="mdi:account-multiple" />
          </n-icon>
          Supplier Management
        </h2>
        <p class="view-subtitle">
          Manage suppliers, monitor active status, and handle purchase orders in one place.
        </p>
      </div>

      <n-button type="primary" class="create-btn" @click="openCreateModal">
        <template #icon>
          <n-icon>
            <Icon icon="mdi:plus" />
          </n-icon>
        </template>
        Add New Supplier
      </n-button>
    </div>

    <!-- Stats Cards -->
    <n-grid :cols="{ xs: 1, s: 2, m: 3, l: 4, xl: 5 }" :x-gap="16" :y-gap="16" class="stats-grid">
      <n-grid-item v-for="stat in stats" :key="stat.label">
        <n-card size="small" class="stat-card" :class="'stat-' + stat.key">
          <div class="stat-content">
            <n-icon size="28" class="stat-icon">
              <Icon :icon="stat.icon" />
            </n-icon>
            <div class="stat-info">
              <div class="stat-value">{{ stat.value }}</div>
              <div class="stat-label">{{ stat.label }}</div>
            </div>
          </div>
        </n-card>
      </n-grid-item>
    </n-grid>

    <!-- Search & Filters -->
    <n-card size="small" class="toolbar-card">
      <div class="search-filters-container">
        <n-input v-model:value="searchQuery" placeholder="Search suppliers..." clearable class="search-input">
          <template #prefix>
            <n-icon>
              <Icon icon="mdi:magnify" />
            </n-icon>
          </template>
        </n-input>

        <div class="toolbar-actions">
          <n-select v-model:value="filterStatus" :options="statusOptions" class="status-select" size="small" />
          <n-button quaternary :loading="isRefreshing" @click="refreshData">
            <template #icon>
              <n-icon>
                <Icon icon="mdi:refresh" />
              </n-icon>
            </template>
          </n-button>
        </div>
      </div>
    </n-card>

    <!-- Supplier List -->
    <n-card size="small" title="Suppliers" class="table-card">
      <n-data-table :columns="columns" :data="filteredSuppliers" :pagination="pagination" :bordered="false" size="small"
        :row-key="(row) => row.id" :scroll-x="960" />
    </n-card>

    <!-- Pending Orders -->
    <n-card size="small" class="orders-card">
      <template #header>
        <n-space align="center" :size="8">
          <n-icon size="20">
            <Icon icon="mdi:clipboard-list" />
          </n-icon>
          Pending Orders
        </n-space>
      </template>

      <template #header-extra>
        <n-badge :value="purchaseOrders.length" :max="99" type="warning" />
      </template>

      <n-data-table v-if="purchaseOrders.length > 0" :columns="orderColumns" :data="purchaseOrders" :bordered="false"
        size="small" :row-key="(row) => row.id" :scroll-x="980" />
      <n-empty v-else description="No pending orders right now" style="padding: 24px" />
    </n-card>

    <!-- Modals -->
    <SupplierForm v-if="showFormModal" :initial-data="editingSupplier" @submit="handleSupplierSubmit"
      @cancel="closeFormModal" />

    <PurchaseOrderModal v-if="showOrderModal" :supplier="selectedSupplier"
      :available-products="selectedSupplierProducts" @submit="handleOrderSubmit" @cancel="showOrderModal = false" />
  </n-space>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, h } from 'vue';
import {
  NCard,
  NGrid,
  NGridItem,
  NInput,
  NSelect,
  NDataTable,
  NBadge,
  NEmpty,
  NButton,
  NIcon,
  NSpace,
  NPopconfirm,
  NTag,
  useMessage,
  useDialog
} from 'naive-ui';
import { Icon } from '@iconify/vue';
import { useSupplierStore } from '@/stores/supplierStore';
import {
  generateWhatsAppOrderLink,
  generateWhatsAppCancellationLink,
  sendViaWhatsApp
} from '@/utils/whatsapp';
import type { Supplier, PurchaseOrderItem, PurchaseOrder } from '@/types/supplier';
import SupplierForm from '@/components/suppliers/SupplierForm.vue';
import PurchaseOrderModal from '@/components/suppliers/PurchaseOrderModal.vue';

const message = useMessage();
const dialog = useDialog();
const store = useSupplierStore();

const searchQuery = ref('');
const filterStatus = ref<'all' | 'active' | 'inactive'>('all');
const showFormModal = ref(false);
const showOrderModal = ref(false);
const editingSupplier = ref<Supplier | null>(null);
const selectedSupplier = ref<Supplier | null>(null);
const isRefreshing = ref(false);

const statusOptions = [
  { label: 'All', value: 'all' },
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' }
] as const;

const pagination = {
  pageSize: 10,
  showSizePicker: true,
  pageSizes: [10, 20, 50]
};

onMounted(() => {
  store.loadInitialData();
});

const selectedSupplierProducts = computed(() =>
  selectedSupplier.value ? store.getProductsBySupplier(selectedSupplier.value.id) : []
);

const purchaseOrders = computed(() => store.purchaseOrders);

const filteredSuppliers = computed(() => {
  let list = [...store.suppliers];

  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase().trim();
    list = list.filter((s) =>
      s.name.toLowerCase().includes(q) ||
      s.contactPerson?.toLowerCase().includes(q) ||
      s.phone.includes(q) ||
      (s as any).businessId?.includes(q)
    );
  }

  if (filterStatus.value !== 'all') {
    const isActive = filterStatus.value === 'active';
    list = list.filter((s) => s.isActive === isActive);
  }

  return list.sort((a, b) => new Date(b.updatedAt).getTime() - new Date(a.updatedAt).getTime());
});

const stats = computed(() => [
  {
    label: 'Total Suppliers',
    value: store.suppliers.length,
    icon: 'mdi:account-group',
    key: 'suppliers'
  },
  {
    label: 'Active',
    value: store.activeSuppliers.length,
    icon: 'mdi:check-circle',
    key: 'active'
  },
  {
    label: 'Products',
    value: store.suppliers.flatMap((s) => s.itemIds).length,
    icon: 'mdi:package-variant',
    key: 'products'
  },
  {
    label: 'Pending Orders',
    value: store.purchaseOrders.length,
    icon: 'mdi:clipboard-list',
    key: 'pending'
  },
  {
    label: 'Orders This Month',
    value: store.purchaseOrders.filter((o) => {
      const now = new Date();
      const created = new Date(o.createdAt);
      return created.getMonth() === now.getMonth() && created.getFullYear() === now.getFullYear();
    }).length,
    icon: 'mdi:calendar-clock',
    key: 'month'
  }
]);

function formatDate(value: string) {
  return new Date(value).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

function getStatusMeta(status: PurchaseOrder['status']) {
  const statusMap: Record<
    PurchaseOrder['status'],
    { type: 'default' | 'warning' | 'info' | 'success' | 'error'; icon: string; label: string }
  > = {
    draft: { type: 'default', icon: 'mdi:file-document', label: 'Draft' },
    sent: { type: 'warning', icon: 'mdi:send', label: 'Sent' },
    confirmed: { type: 'info', icon: 'mdi:check-circle', label: 'Confirmed' },
    delivered: { type: 'success', icon: 'mdi:package-check', label: 'Delivered' },
    cancelled: { type: 'error', icon: 'mdi:cancel', label: 'Cancelled' }
  };

  return statusMap[status] ?? statusMap.draft;
}

const columns = computed(() => [
  {
    title: 'Supplier',
    key: 'name',
    width: 220,
    render: (row: Supplier) =>
      h('div', { class: 'supplier-cell' }, [
        h('div', { class: 'supplier-name' }, row.name),
        h('div', { class: 'supplier-subtext', style: { fontSize: '12px', color: 'rgba(127, 127, 127, 0.95)', lineHeight: '1.35' } }, row.contactPerson || 'No contact person')
      ])
  },
  {
    title: 'Contact',
    key: 'contact',
    width: 220,
    render: (row: Supplier) =>
      h(NSpace, { vertical: true, size: 4 }, {
        default: () => [
          h(
            NButton,
            {
              text: true,
              size: 'small',
              onClick: () => window.open(`tel:${row.phone}`)
            },
            {
              default: () => [
                h(NIcon, null, { default: () => h(Icon, { icon: 'mdi:phone' }) }),
                ` ${row.phone}`
              ]
            }
          ),
          row.email &&
          h(
            NButton,
            {
              text: true,
              size: 'small',
              onClick: () => window.open(`mailto:${row.email}`)
            },
            {
              default: () => [
                h(NIcon, null, { default: () => h(Icon, { icon: 'mdi:email' }) }),
                ` ${row.email}`
              ]
            }
          )
        ]
      })
  },
  {
    title: 'Items',
    key: 'items',
    width: 120,
    render: (row: Supplier) => {
      const count = row.itemIds.length;
      return count > 0
        ? h(
          NTag,
          { type: 'info', size: 'small' as const },
          {
            default: () => [
              h(NIcon, { style: { marginRight: '4px' } }, {
                default: () => h(Icon, { icon: 'mdi:package-variant' })
              }),
              ` ${count} items`
            ]
          }
        )
        : h('span', { class: 'muted-text' }, 'No items');
    }
  },
  {
    title: 'Status',
    key: 'isActive',
    width: 110,
    render: (row: Supplier) => {
      const type = row.isActive ? 'success' : 'default';
      const icon = row.isActive ? 'mdi:check-circle' : 'mdi:cancel-circle';
      const label = row.isActive ? 'Active' : 'Inactive';

      return h(
        NTag,
        {
          type: type as any,
          size: 'small' as const,
          class: 'clickable-tag',
          onClick: async () => {
            await store.toggleSupplierStatus(row.id);
          }
        },
        {
          default: () => [
            h(NIcon, { style: { marginRight: '4px' } }, {
              default: () => h(Icon, { icon })
            }),
            label
          ]
        }
      );
    }
  },
  {
    title: 'Actions',
    key: 'actions',
    width: 200,
    fixed: 'right' as const,
    render: (row: Supplier) =>
      h(NSpace, { wrap: true, size: 8 }, {
        default: () => [
          h(
            NButton,
            {
              size: 'small',
              type: 'primary',
              ghost: true,
              onClick: () => openOrderModal(row)
            },
            {
              default: () => [
                h(NIcon, null, { default: () => h(Icon, { icon: 'mdi:cart-plus' }) }),
                ' Order'
              ]
            }
          ),
          h(
            NButton,
            {
              size: 'small',
              type: 'info',
              ghost: true,
              onClick: () => openEditModal(row)
            },
            {
              default: () => h(NIcon, null, { default: () => h(Icon, { icon: 'mdi:pencil' }) })
            }
          ),
          h(
            NPopconfirm,
            {
              onPositiveClick: () => handleDelete(row.id),
              negativeText: 'Cancel',
              positiveText: 'Delete'
            },
            {
              trigger: () =>
                h(
                  NButton,
                  {
                    size: 'small',
                    type: 'error',
                    ghost: true
                  },
                  {
                    default: () => h(NIcon, null, { default: () => h(Icon, { icon: 'mdi:delete' }) })
                  }
                ),
              default: () => 'Delete this supplier?'
            }
          )
        ]
      })
  }
]);

const orderColumns = computed(() => [
  {
    title: 'Order ID',
    key: 'id',
    width: 120,
    render: (row: PurchaseOrder) =>
      h(
        'code',
        {
          class: 'code-pill', style: {
            fontSize: "11px",
            background: "rgba(245, 245, 245, 0.95)",
            padding: "3px 7px",
            borderRadius: "8px",
            whiteSpace: "nowrap",
          }
        },
        row.id.slice(0, 8)
      )
  },
  {
    title: 'Supplier',
    key: 'supplierName',
    width: 180,
    render: (row: PurchaseOrder) => {
      const supplier = store.getSupplierById(row.id);
      return h('div', { class: 'supplier-cell' }, supplier?.name || 'Unknown');
    }
  },
  {
    title: 'Items',
    key: 'items',
    width: 260,
    render: (row: PurchaseOrder) =>
      h(NSpace, { vertical: true, size: 2 }, {
        default: () =>
          row.items.map((item) =>
            h('div', { class: 'order-item-line' }, [
              h('span', { class: 'order-item-name' }, item.productName),
              ` x ${item.quantity} ${item.unit}`
            ])
          )
      })
  },
  {
    title: 'Date',
    key: 'createdAt',
    width: 150,
    render: (row: PurchaseOrder) =>
      h('span', { class: 'muted-text' }, formatDate(row.createdAt))
  },
  {
    title: 'Status',
    key: 'status',
    width: 130,
    render: (row: PurchaseOrder) => {
      const meta = getStatusMeta(row.status);
      return h(
        NTag,
        { type: meta.type as any, size: 'small' as const },
        {
          default: () => [
            h(NIcon, { style: { marginRight: '4px' } }, {
              default: () => h(Icon, { icon: meta.icon })
            }),
            meta.label
          ]
        }
      );
    }
  },
  {
    title: 'Actions',
    key: 'actions',
    width: 220,
    fixed: 'right' as const,
    render: (row: PurchaseOrder) => {
      const supplier = store.getSupplierById(row.supplierId);
      const isPending = row.status === 'sent';

      return h(NSpace, { wrap: true, size: 8 }, {
        default: () => {
          const buttons: any[] = [];

          if (isPending) {
            buttons.push(
              h(
                NButton,
                {
                  size: 'small',
                  type: 'success',
                  ghost: true,
                  onClick: () => handleConfirmDelivery(row.id)
                },
                {
                  default: () => [
                    h(NIcon, null, { default: () => h(Icon, { icon: 'mdi:package-check' }) }),
                    ' Received'
                  ]
                }
              )
            );
          }

          if (supplier && isPending) {
            buttons.push(
              h(
                NPopconfirm,
                {
                  onPositiveClick: () => handleCancelOrder(row.id, supplier, true),
                  negativeText: 'Cancel',
                  positiveText: 'Confirm'
                },
                {
                  trigger: () =>
                    h(
                      NButton,
                      {
                        size: 'small',
                        type: 'error',
                        ghost: true
                      },
                      {
                        default: () => [
                          h(NIcon, null, { default: () => h(Icon, { icon: 'mdi:whatsapp' }) }),
                          ' Cancel'
                        ]
                      }
                    ),
                  default: () => 'Cancel this order and notify supplier via WhatsApp?'
                }
              )
            );
          }

          if (!isPending) {
            buttons.push(
              h(
                NPopconfirm,
                {
                  onPositiveClick: async () => {
                    const success = await store.deleteOrder(row.id);
                    if (success) message.success('Order removed');
                    else message.error('Failed to remove order');
                  },
                  negativeText: 'Cancel',
                  positiveText: 'Delete'
                },
                {
                  trigger: () =>
                    h(
                      NButton,
                      { size: 'small', type: 'error', ghost: true },
                      {
                        default: () =>
                          h(NIcon, null, { default: () => h(Icon, { icon: 'mdi:delete' }) })
                      }
                    ),
                  default: () => 'Remove this order record?'
                }
              )
            );
          }

          return buttons;
        }
      });
    }
  }
]);

const openCreateModal = () => {
  editingSupplier.value = null;
  showFormModal.value = true;
};

const openEditModal = (supplier: Supplier) => {
  editingSupplier.value = { ...supplier };
  showFormModal.value = true;
};

const openOrderModal = (supplier: Supplier) => {
  if (!supplier.isActive) {
    message.warning('Cannot order from inactive supplier');
    return;
  }
  selectedSupplier.value = supplier;
  showOrderModal.value = true;
};

const closeFormModal = () => {
  showFormModal.value = false;
  editingSupplier.value = null;
};

const handleSupplierSubmit = async (
  data: Omit<Supplier, 'id' | 'createdAt' | 'updatedAt'>
) => {
  try {
    if (editingSupplier.value?.id) {
      const success = await store.updateSupplier(editingSupplier.value.id, data);
      if (success) message.success('Supplier updated');
      else message.error('Failed to update supplier');
    } else {
      await store.addSupplier(data);
      message.success('Supplier created');
    }
    closeFormModal();
  } catch {
    message.error('Something went wrong');
  }
};

const handleOrderSubmit = async (items: PurchaseOrderItem[]) => {
  if (!selectedSupplier.value) return;

  try {
    const order = await store.createPurchaseOrder(selectedSupplier.value.id, items);
    const waLink = generateWhatsAppOrderLink(
      selectedSupplier.value.phone,
      selectedSupplier.value.name,
      items
    );

    dialog.warning({
      title: 'Send Purchase Order?',
      content: `Send order with ${items.length} item(s) to ${selectedSupplier.value.name} via WhatsApp?`,
      positiveText: 'Send via WhatsApp',
      negativeText: 'Save as Draft',
      onPositiveClick: async () => {
        sendViaWhatsApp(waLink);
        await store.markOrderSent(order.id);
        message.success('Order sent!');
      },
      onNegativeClick: () => {
        message.info('Order saved as draft');
      }
    });

    showOrderModal.value = false;
  } catch {
    message.error('Failed to create order');
  }
};

const handleConfirmDelivery = async (orderId: string) => {
  const success = await store.confirmDelivery(orderId);
  if (success) {
    message.success('Delivery confirmed!');
  } else {
    message.error('Failed to confirm delivery');
  }
};

const handleCancelOrder = async (
  orderId: string,
  supplier: Supplier | undefined,
  viaWhatsApp: boolean
) => {
  const order = store.purchaseOrders.find((o) => o.id === orderId);
  if (!order || !supplier) return;

  const cancelLink = generateWhatsAppCancellationLink(
    supplier.phone,
    supplier.name,
    orderId.slice(0, 8),
    order.items.map((i) => ({
      productName: i.productName,
      quantity: i.quantity,
      unit: i.unit
    }))
  );

  const result = await store.cancelOrder(orderId);
  if (result) {
    if (viaWhatsApp) sendViaWhatsApp(cancelLink);
    message.success('Order cancelled & WhatsApp message sent!');
  } else {
    message.error('Failed to cancel order');
  }
};

const handleDelete = async (id: string) => {
  const success = await store.deleteSupplier(id);
  if (success) {
    message.success('Supplier deleted');
  } else {
    message.error('Failed to delete supplier');
  }
};

const refreshData = async () => {
  try {
    isRefreshing.value = true;
    await Promise.resolve(store.loadInitialData());
    message.success('Data refreshed');
  } catch {
    message.error('Failed to refresh data');
  } finally {
    isRefreshing.value = false;
  }
};
</script>

<style scoped>
.supplier-view {
  padding: 16px;
  max-width: 1400px;
  margin: 0 auto;
}

@media (min-width: 768px) {
  .supplier-view {
    padding: 24px;
  }
}

.view-header {
  display: flex;
  flex-direction: column;
  gap: 14px;
  padding-bottom: 14px;
  border-bottom: 1px solid rgba(120, 120, 120, 0.16);
}

@media (min-width: 720px) {
  .view-header {
    flex-direction: row;
    justify-content: space-between;
    align-items: flex-end;
  }
}

.header-copy {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.view-title {
  margin: 0;
  font-size: clamp(1.35rem, 1rem + 1vw, 1.7rem);
  line-height: 1.2;
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
}

.title-icon {
  flex-shrink: 0;
}

.view-subtitle {
  margin: 0;
  color: rgba(127, 127, 127, 0.95);
  font-size: 0.95rem;
  line-height: 1.45;
}

.create-btn {
  align-self: flex-start;
}

.stats-grid {
  margin-bottom: 4px;
}

.stat-card :deep(.n-card__content) {
  padding: 0;
}

.stat-card {
  border-radius: 18px;
  overflow: hidden;
}

.stat-content {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  min-height: 84px;
}

.stat-icon {
  flex-shrink: 0;
  width: 42px;
  height: 42px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  color: #fff;
  box-shadow: 0 8px 18px rgba(0, 0, 0, 0.12);
}

.stat-suppliers .stat-icon {
  background: linear-gradient(135deg, #18a058, #36ad6a);
}

.stat-active .stat-icon {
  background: linear-gradient(135deg, #2080f0, #4098fc);
}

.stat-products .stat-icon {
  background: linear-gradient(135deg, #f0a020, #fcb040);
}

.stat-pending .stat-icon {
  background: linear-gradient(135deg, #d03050, #e88080);
}

.stat-month .stat-icon {
  background: linear-gradient(135deg, #646cff, #8b8cf7);
}

.stat-info {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.stat-value {
  font-size: 22px;
  font-weight: 800;
  line-height: 1.1;
}

.stat-label {
  font-size: 12px;
  color: rgba(127, 127, 127, 0.95);
  line-height: 1.2;
}

.toolbar-card {
  border-radius: 18px;
}

.search-filters-container {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

@media (min-width: 768px) {
  .search-filters-container {
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
  }
}

.search-input {
  flex: 1 1 auto;
  min-width: 0;
}

.toolbar-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  justify-content: flex-end;
}

.status-select {
  width: 140px;
}

.table-card,
.orders-card {
  border-radius: 18px;
  overflow: hidden;
}

.orders-card :deep(.n-card-header) {
  padding-bottom: 12px;
}

:deep(.n-data-table) {
  --n-merged-th-color: rgba(250, 250, 250, 0.96);
  --n-th-color: rgba(250, 250, 250, 0.96);
}

:deep(.n-data-table .n-data-table-th) {
  font-weight: 700;
}

:deep(.n-data-table .n-data-table-td) {
  vertical-align: top;
}

.supplier-cell {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
}

.supplier-name {
  font-weight: 650;
  line-height: 1.35;
}


.muted-text {
  color: rgba(127, 127, 127, 0.95);
  font-size: 12px;
}

.clickable-tag {
  cursor: pointer;
  user-select: none;
}

.code-pill {
  font-size: 11px;
  background: rgba(245, 245, 245, 0.95);
  padding: 3px 7px;
  border-radius: 8px;
  white-space: nowrap;
}

.order-item-line {
  font-size: 12px;
  line-height: 1.45;
  color: rgba(80, 80, 80, 0.95);
}

.order-item-name {
  font-weight: 600;
}

@media (max-width: 640px) {
  .supplier-view {
    padding: 12px;
  }

  :deep(.n-card__content) {
    padding: 12px !important;
  }

  .stat-content {
    padding: 12px 14px;
    min-height: 76px;
  }

  .stat-value {
    font-size: 20px;
  }

  .status-select {
    width: 100%;
  }

  .toolbar-actions {
    width: 100%;
    justify-content: space-between;
  }

  :deep(.n-data-table .n-data-table-td) {
    padding: 8px 6px;
  }

  :deep(.n-card-header__main) {
    font-size: 15px;
  }
}

div.n-card-header .iconify {
  margin-top: .4em;
}
</style>
