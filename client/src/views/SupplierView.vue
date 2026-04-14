<template>
  <n-space vertical :size="24" class="supplier-view">
    <!-- Header -->
    <div class="view-header">
      <h2>
        <n-icon size="24" style="vertical-align: middle; margin-right: 8px"><Icon icon="mdi:account-multiple" /></n-icon>
        Supplier Management
      </h2>
      <n-button type="primary" @click="openCreateModal">
        <template #icon><n-icon><Icon icon="mdi:plus" /></n-icon></template>
        Add New Supplier
      </n-button>
    </div>

    <!-- Stats Cards with Icons -->
    <n-grid :cols="5" :x-gap="16" class="stats-grid">
      <n-grid-item v-for="stat in stats" :key="stat.label">
        <n-card size="small" class="stat-card" :class="'stat-' + stat.key">
          <div class="stat-content">
            <n-icon size="28" class="stat-icon"><Icon :icon="stat.icon" /></n-icon>
            <div class="stat-info">
              <div class="stat-value">{{ stat.value }}</div>
              <div class="stat-label">{{ stat.label }}</div>
            </div>
          </div>
        </n-card>
      </n-grid-item>
    </n-grid>

    <!-- Search & Filters -->
    <n-card size="small">
      <n-space justify="space-between" align="center">
        <n-input v-model:value="searchQuery" placeholder="Search suppliers..." style="max-width: 300px" clearable>
          <template #prefix><n-icon><Icon icon="mdi:magnify" /></n-icon></template>
        </n-input>
        <n-space>
          <n-select v-model:value="filterStatus" :options="[
            { label: 'All', value: 'all' },
            { label: 'Active', value: 'active' },
            { label: 'Inactive', value: 'inactive' }
          ]" style="width: 120px" size="small" />
          <n-button quaternary @click="refreshData">
            <template #icon><n-icon><Icon icon="mdi:refresh" /></n-icon></template>
          </n-button>
        </n-space>
      </n-space>
    </n-card>

    <!-- Supplier List -->
    <n-card size="small" title="Suppliers">
      <n-data-table :columns="columns" :data="filteredSuppliers" :pagination="{ pageSize: 10 }" :bordered="false"
        size="small" :row-key="row => row.id" />
    </n-card>

    <!-- Pending Orders -->
    <n-card size="small" class="orders-card">
      <template #header>
        <n-space align="center" :size="8">
          <n-icon size="20"><Icon icon="mdi:clipboard-list" /></n-icon>
          Pending Orders
        </n-space>
      </template>
      <template #header-extra>
        <n-badge :value="pendingOrders.length" :max="99" type="warning" />
      </template>
      <n-data-table v-if="pendingOrders.length > 0" :columns="orderColumns" :data="pendingOrders" :bordered="false"
        size="small" :row-key="row => row.id" />
      <n-empty v-else description="No pending orders right now" style="padding: 24px" />
    </n-card>

    <!-- Modals -->
    <SupplierForm v-if="showFormModal" :product-options="productOptions" :initial-data="editingSupplier"
      @submit="handleSupplierSubmit" @cancel="closeFormModal" />

    <PurchaseOrderModal v-if="showOrderModal" :supplier="selectedSupplier"
      :available-products="selectedSupplierProducts" @submit="handleOrderSubmit" @cancel="showOrderModal = false" />
  </n-space>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, h } from 'vue';
import { NCard, NGrid, NGridItem, NInput, NSelect, NDataTable, NBadge, NEmpty, NButton, NIcon, NSpace, NPopconfirm, NTag, useMessage, useDialog } from 'naive-ui';
import { Icon } from '@iconify/vue';
import { useSupplierStore } from '@/stores/supplierStore';
import { generateWhatsAppOrderLink, generateWhatsAppCancellationLink, sendViaWhatsApp } from '@/utils/whatsapp';
import type { Supplier, PurchaseOrderItem, PurchaseOrder } from '@/types/supplier';
import SupplierForm from '@/components/suppliers/SupplierForm.vue';
import PurchaseOrderModal from '@/components/suppliers/PurchaseOrderModal.vue';

const message = useMessage();
const dialog = useDialog();
const store = useSupplierStore();

// State
const searchQuery = ref('');
const filterStatus = ref<'all' | 'active' | 'inactive'>('all');
const showFormModal = ref(false);
const showOrderModal = ref(false);
const editingSupplier = ref<Supplier | null>(null);
const selectedSupplier = ref<Supplier | null>(null);

// Load data
onMounted(() => {
  store.loadInitialData();
});

// Computed
const productOptions = computed(() =>
  store.products.map(p => ({ label: p.name, value: p.id, sku: p.sku }))
);

const selectedSupplierProducts = computed(() =>
  selectedSupplier.value ? store.getProductsBySupplier(selectedSupplier.value.id) : []
);

const pendingOrders = computed(() => store.pendingOrders);

const filteredSuppliers = computed(() => {
  let list = [...store.suppliers];

  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    list = list.filter(s =>
      s.name.toLowerCase().includes(q) ||
      s.contactPerson?.toLowerCase().includes(q) ||
      s.phone.includes(q) ||
      (s as any).businessId?.includes(q)
    );
  }

  if (filterStatus.value !== 'all') {
    const isActive = filterStatus.value === 'active';
    list = list.filter(s => s.isActive === isActive);
  }

  return list.sort((a, b) => new Date(b.updatedAt).getTime() - new Date(a.updatedAt).getTime());
});

const stats = computed(() => [
  { label: 'Total Suppliers', value: store.suppliers.length, precision: 0, icon: 'mdi:account-group', key: 'suppliers' },
  { label: 'Active', value: store.activeSuppliers.length, precision: 0, icon: 'mdi:check-circle', key: 'active' },
  { label: 'Products', value: store.products.length, precision: 0, icon: 'mdi:package-variant', key: 'products' },
  { label: 'Pending Orders', value: store.pendingOrders.length, precision: 0, icon: 'mdi:clipboard-list', key: 'pending' },
  {
    label: 'Orders This Month',
    value: store.purchaseOrders.filter(o =>
      new Date(o.createdAt).getMonth() === new Date().getMonth()
    ).length,
    precision: 0,
    icon: 'mdi:calendar-clock',
    key: 'month'
  }
]);

// Supplier Table Columns
const columns = computed(() => [
  {
    title: 'Supplier',
    key: 'name',
    width: 220,
    render: (row: Supplier) =>
      h('div', [
        h('div', { style: { fontWeight: '600' } }, row.name),
        h('div', { style: { fontSize: '12px', color: '#666' } }, row.contactPerson)
      ])
  },
  {
    title: 'Contact',
    key: 'contact',
    width: 200,
    render: (row: Supplier) =>
      h(NSpace, { vertical: true, size: 4 }, {
        default: () => [
          h(NButton, {
            text: true,
            size: 'small',
            onClick: () => window.open(`tel:${row.phone}`)
          }, { default: () => [h(NIcon, {}, { default: () => h(Icon, { icon: 'mdi:phone' }) }), ` ${row.phone}`] }),
          row.email && h(NButton, {
            text: true,
            size: 'small',
            onClick: () => window.open(`mailto:${row.email}`)
          }, { default: () => [h(NIcon, {}, { default: () => h(Icon, { icon: 'mdi:email' }) }), ` ${row.email}`] })
        ]
      })
  },
  {
    title: 'Products',
    key: 'products',
    width: 150,
    render: (row: Supplier) => {
      const count = row.productIds.length;
      return count > 0
        ? h(NTag, { type: 'info', size: 'small' as const }, {
            default: () => [h(NIcon, {}, { default: () => h(Icon, { icon: 'mdi:package-variant' }) }), ` ${count} items`]
          })
        : h('span', { style: { color: '#999', fontSize: '12px' } }, 'No products');
    }
  },
  {
    title: 'Status',
    key: 'isActive',
    width: 120,
    render: (row: Supplier) => {
      const type = row.isActive ? 'success' : 'default';
      const icon = row.isActive ? 'mdi:check-circle' : 'mdi:cancel-circle';
      const label = row.isActive ? 'Active' : 'Inactive';

      return h(NTag, {
        type: type as any,
        size: 'small' as const,
        style: { cursor: 'pointer' },
        onClick: async () => {
          await store.toggleSupplierStatus(row.id)
        }
      }, {
        default: () => [h(NIcon, { style: { marginRight: '4px' } }, { default: () => h(Icon, { icon }) }), label]
      });
    }
  },
  {
    title: 'Actions',
    key: 'actions',
    width: 160,
    fixed: 'right' as const,
    render: (row: Supplier) =>
      h(NSpace, {}, {
        default: () => [
          h(NButton, {
            size: 'small',
            type: 'primary',
            ghost: true,
            onClick: () => openOrderModal(row)
          }, { default: () => [h(NIcon, {}, { default: () => h(Icon, { icon: 'mdi:cart-plus' }) }), ' Order'] }),
          h(NButton, {
            size: 'small',
            type: 'info',
            ghost: true,
            onClick: () => openEditModal(row)
          }, { default: () => h(NIcon, {}, { default: () => h(Icon, { icon: 'mdi:pencil' }) }) }),
          h(NPopconfirm, {
            onPositiveClick: () => handleDelete(row.id),
            negativeText: 'Cancel',
            positiveText: 'Delete'
          }, {
            trigger: () => h(NButton, {
              size: 'small',
              type: 'error',
              ghost: true
            }, { default: () => h(NIcon, {}, { default: () => h(Icon, { icon: 'mdi:delete' }) }) }),
            default: () => 'Delete this supplier?'
          })
        ]
      })
  }
]);

// Order Table Columns
const orderColumns = computed(() => [
  {
    title: 'Order ID',
    key: 'id',
    width: 100,
    render: (row: PurchaseOrder) =>
      h('code', { style: { fontSize: '11px', background: '#f5f5f5', padding: '2px 6px', borderRadius: '4px' } },
        row.id.slice(0, 8))
  },
  {
    title: 'Supplier',
    key: 'supplierName',
    width: 160,
    render: (row: PurchaseOrder) => {
      const supplier = store.getSupplierById(row.supplierId);
      return h('div', { style: { fontWeight: '500' } }, supplier?.name || 'Unknown');
    }
  },
  {
    title: 'Items',
    key: 'items',
    render: (row: PurchaseOrder) =>
      h(NSpace, { vertical: true, size: 2 }, {
        default: () => row.items.map(item =>
          h('div', { style: { fontSize: '12px' } }, [
            h('span', { style: { fontWeight: '500' } }, item.productName),
            ` x ${item.quantity} ${item.unit}`
          ])
        )
      })
  },
  {
    title: 'Date',
    key: 'createdAt',
    width: 140,
    render: (row: PurchaseOrder) =>
      h('span', { style: { fontSize: '12px', color: '#666' } },
        new Date(row.createdAt).toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }))
  },
  {
    title: 'Status',
    key: 'status',
    width: 120,
    render: (row: PurchaseOrder) => {
      const statusMap: Record<string, { type: string; icon: string; label: string }> = {
        draft: { type: 'default', icon: 'mdi:file-document', label: 'Draft' },
        sent: { type: 'warning', icon: 'mdi:send', label: 'Sent' },
        confirmed: { type: 'info', icon: 'mdi:check-circle', label: 'Confirmed' },
        delivered: { type: 'success', icon: 'mdi:package-check', label: 'Delivered' },
        cancelled: { type: 'error', icon: 'mdi:cancel', label: 'Cancelled' },
      };
      const s = statusMap[row.status] || statusMap.draft;
      return h(NTag, { type: s.type as any, size: 'small' as const }, {
        default: () => [h(NIcon, { style: { marginRight: '4px' } }, { default: () => h(Icon, { icon: s.icon }) }), s.label]
      });
    }
  },
  {
    title: 'Actions',
    key: 'actions',
    width: 160,
    render: (row: PurchaseOrder) => {
      const supplier = store.getSupplierById(row.supplierId);
      const isPending = row.status === 'sent';

      return h(NSpace, {}, {
        default: () => {
          const buttons: any[] = [];

          // Confirm delivery (only for pending orders)
          if (isPending) {
            buttons.push(
              h(NButton, {
                size: 'small',
                type: 'success',
                ghost: true,
                onClick: () => handleConfirmDelivery(row.id)
              }, { default: () => [h(NIcon, {}, { default: () => h(Icon, { icon: 'mdi:package-check' }) }), ' Received'] })
            );
          }

          // Cancel via WhatsApp (only for pending orders)
          if (supplier && isPending) {
            buttons.push(
              h(NPopconfirm, {
                onPositiveClick: () => handleCancelOrder(row.id, supplier, true),
                negativeText: 'Cancel',
                positiveText: 'Confirm'
              }, {
                trigger: () => h(NButton, {
                  size: 'small',
                  type: 'error',
                  ghost: true
                }, {
                  default: () => [h(NIcon, {}, { default: () => h(Icon, { icon: 'mdi:whatsapp' }) }), ' Cancel']
                }),
                default: () => 'Cancel this order and notify supplier via WhatsApp?'
              })
            );
          }

          // Delete button for non-pending orders
          if (!isPending) {
            buttons.push(
              h(NPopconfirm, {
                onPositiveClick: async () => {
                  const success = await store.deleteOrder(row.id);
                  if (success) message.success('Order removed');
                  else message.error('Failed to remove order');
                },
                negativeText: 'Cancel',
                positiveText: 'Delete'
              }, {
                trigger: () => h(NButton, { size: 'small', type: 'error', ghost: true }, {
                  default: () => h(NIcon, {}, { default: () => h(Icon, { icon: 'mdi:delete' }) })
                }),
                default: () => 'Remove this order record?'
              })
            );
          }

          return buttons;
        }
      });
    }
  }
]);

// Methods
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

const handleSupplierSubmit = async (data: Omit<Supplier, 'id' | 'createdAt' | 'updatedAt'> & { businessId?: string }) => {
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
      content: `Send order with ${items.length} item(s) to *${selectedSupplier.value.name}* via WhatsApp?`,
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

const handleCancelOrder = async (orderId: string, supplier: Supplier | undefined, viaWhatsApp: boolean) => {
  const order = store.purchaseOrders.find(o => o.id === orderId);
  if (!order || !supplier) return;

  const cancelLink = generateWhatsAppCancellationLink(
    supplier.phone,
    supplier.name,
    orderId.slice(0, 8),
    order.items.map(i => ({ productName: i.productName, quantity: i.quantity, unit: i.unit }))
  );
  const result = await store.cancelOrder(orderId);
  if (result) {
    sendViaWhatsApp(cancelLink);
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

const refreshData = () => {
  message.loading('Refreshing...', { duration: 800 });
};
</script>

<style scoped>
.supplier-view {
  padding: 24px;
  max-width: 1400px;
  margin: 0 auto;
}

.view-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 8px;
  border-bottom: 1px solid #eee;
}

.stats-grid {
  margin-bottom: 8px;
}

.stat-card :deep(.n-card__content) {
  padding: 0;
}

.stat-content {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
}

.stat-icon {
  flex-shrink: 0;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  color: #fff;
}

.stat-suppliers .stat-icon { background: linear-gradient(135deg, #18a058, #36ad6a); }
.stat-active .stat-icon { background: linear-gradient(135deg, #2080f0, #4098fc); }
.stat-products .stat-icon { background: linear-gradient(135deg, #f0a020, #fcb040); }
.stat-pending .stat-icon { background: linear-gradient(135deg, #d03050, #e88080); }
.stat-month .stat-icon { background: linear-gradient(135deg, #646cff, #8b8cf7); }

.stat-info {
  display: flex;
  flex-direction: column;
}

.stat-value {
  font-size: 22px;
  font-weight: 700;
  line-height: 1.2;
}

.stat-label {
  font-size: 12px;
  color: #999;
}

.orders-card :deep(.n-card-header) {
  padding-bottom: 12px;
}

:deep(.n-data-table) {
  --n-merged-th-color: #fafafa;
}
</style>
