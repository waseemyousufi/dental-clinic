<template>
  <n-modal v-model:show="showModal" preset="card" title="Create Purchase Order" style="width: 800px; max-width: 95vw"
    @after-leave="emit('cancel')">
    <n-alert type="info" :show-icon="false" style="margin-bottom: 16px">
      Creating order for: <strong>{{ supplier?.name }}</strong>
      <template v-if="supplier?.phone">
        • WhatsApp: <n-button text size="small" @click="openWhatsApp">{{ supplier.phone }}</n-button>
      </template>
    </n-alert>

    <!-- Product Selection Table -->
    <n-data-table :columns="columns" :data="tableData" :pagination="false" size="small" :bordered="false"
      :scroll-x="600" />

    <!-- Order Summary -->
    <n-divider />
    <n-space justify="space-between" align="center">
      <n-text depth="3">
        Total Items: <strong>{{ totalQuantity }}</strong> •
        Products: <strong>{{ selectedItems.length }}</strong>
      </n-text>
      <n-space>
        <n-button @click="closeModal">Cancel</n-button>
        <n-button type="primary" :disabled="selectedItems.length === 0" @click="handleSubmit">
          <n-icon>
            <Icon icon="mdi:whatsapp" />
          </n-icon> Send via WhatsApp
        </n-button>
      </n-space>
    </n-space>
  </n-modal>
</template>

<script setup lang="ts">
import { ref, computed, h, watch } from 'vue';
import { NModal, NAlert, NDataTable, NDivider, NSpace, NText, NTag, NButton, NInputNumber, NInput, NIcon, useMessage } from 'naive-ui';
import { Icon } from '@iconify/vue';
import type { Supplier, Product, PurchaseOrderItem } from '@/types/supplier';
import { generateWhatsAppOrderLink, sendViaWhatsApp } from '@/utils/whatsapp';

const props = defineProps<{
  supplier: Supplier | null;
  availableProducts: Product[];
}>();

const emit = defineEmits<{
  (e: 'submit', items: PurchaseOrderItem[]): void;
  (e: 'cancel'): void;
}>();

const message = useMessage();
const showModal = ref(true);

interface OrderItem {
  quantity: number;
  notes: string;
}

// Local state for order items
const orderItems = ref<Record<string, OrderItem>>({});

// Initialize with quantities based on low stock
const initializeQuantities = () => {
  const items: Record<string, OrderItem> = {};
  props.availableProducts.forEach(p => {
    if (p.stock <= p.minStock) {
      items[p.id] = {
        quantity: Math.ceil((p.minStock * 2 - p.stock) / 10) * 10,
        notes: 'Restock - below minimum'
      };
    }
  });
  orderItems.value = items;
};

// Reinitialize when products change (new supplier selected)
watch(
  () => props.availableProducts,
  () => {
    initializeQuantities();
  },
  { immediate: true }
);

// Table columns
const columns = [
  {
    title: 'Product',
    key: 'name',
    width: 220,
    render: (row: Product) => h('div', null, [
      h('strong', null, row.name),
      h('div', { style: 'font-size:12px;color:#666' }, `SKU: ${row.sku}`)
    ])
  },
  {
    title: 'Stock',
    key: 'stock',
    width: 100,
    render: (row: Product) => {
      const isLow = row.stock <= row.minStock;
      return h(NTag, {
        type: isLow ? 'error' : 'success',
        size: 'small' as const
      }, { default: () => `${row.stock} ${row.unit}` });
    }
  },
  {
    title: 'Order Qty',
    key: 'quantity',
    width: 120,
    render: (row: Product) => {
      const item = orderItems.value[row.id];
      return h(NInputNumber, {
        value: item?.quantity || 0,
        min: 0,
        step: 1,
        placeholder: '0',
        style: 'width: 100px',
        onUpdateValue: (val: number | null) => {
          if (val && val > 0) {
            orderItems.value[row.id] = {
              quantity: val,
              notes: orderItems.value[row.id]?.notes || ''
            };
          } else {
            delete orderItems.value[row.id];
          }
        }
      });
    }
  },
  {
    title: 'Notes',
    key: 'notes',
    render: (row: Product) => {
      const item = orderItems.value[row.id];
      return h(NInput, {
        value: item?.notes || '',
        placeholder: 'e.g., urgent, specific batch',
        size: 'small' as const,
        onUpdateValue: (val: string) => {
          if (orderItems.value[row.id]) {
            orderItems.value[row.id].notes = val;
          } else if (val) {
            orderItems.value[row.id] = { quantity: 1, notes: val };
          }
        }
      });
    }
  },
  {
    title: '',
    key: 'actions',
    width: 50,
    render: (row: Product) => {
      if (!orderItems.value[row.id]) return null;
      return h(NButton, {
        size: 'tiny' as const,
        type: 'error' as const,
        ghost: true,
        onClick: () => delete orderItems.value[row.id]
      }, { default: () => h(NIcon, null, { default: () => h(Icon, { icon: 'mdi:delete' }) }) });
    }
  }
];

const tableData = computed(() => props.availableProducts);

const selectedItems = computed<PurchaseOrderItem[]>(() =>
  Object.entries(orderItems.value)
    .filter(([_, val]) => val.quantity > 0)
    .map(([productId, { quantity, notes }]) => {
      const product = props.availableProducts.find(p => p.id === productId)!;
      return {
        productId,
        productName: product.name,
        quantity,
        unit: product.unit,
        notes: notes || undefined
      };
    })
);

const totalQuantity = computed(() =>
  selectedItems.value.reduce((sum, item) => sum + item.quantity, 0)
);

const openWhatsApp = () => {
  if (props.supplier?.phone) {
    const link = `https://wa.me/${props.supplier.phone.replace(/\D/g, '')}`;
    window.open(link, '_blank');
  }
};

const closeModal = () => {
  showModal.value = false;
};

const handleSubmit = () => {
  if (selectedItems.value.length === 0) {
    message.warning('Add at least one product to order');
    return;
  }
  emit('submit', selectedItems.value);
};
</script>
