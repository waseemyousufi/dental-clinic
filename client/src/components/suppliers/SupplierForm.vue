<template>
  <n-modal v-model:show="showModal" preset="card" :title="isEdit ? 'Edit Supplier' : 'New Supplier'"
    style="width: 750px; max-width: 90vw" @after-leave="emit('cancel')">
    <n-form ref="formRef" :model="form" :rules="rules" label-placement="left" label-width="140"
      require-mark-placement="right-hanging">
      <n-grid :cols="2" :x-gap="20" :y-gap="16">
        <!-- Name & Business ID -->
        <n-form-item-gi label="Name" path="name">
          <n-input v-model:value="form.name" placeholder="Supplier name" />
        </n-form-item-gi>

        <n-form-item-gi label="Business ID" path="businessId">
          <n-input v-model:value="form.businessId" placeholder="Tax / registration number" />
        </n-form-item-gi>

        <!-- Contact Person & Phone -->
        <n-form-item-gi label="Contact Person" path="contactPerson">
          <n-input v-model:value="form.contactPerson" placeholder="Full name" />
        </n-form-item-gi>

        <n-form-item-gi label="Phone" path="phone">
          <n-input v-model:value="form.phone" placeholder="+1234567890" @blur="formatPhone">
            <template #prefix><n-icon><Icon icon="mdi:phone" /></n-icon></template>
          </n-input>
        </n-form-item-gi>

        <!-- Email -->
        <n-form-item-gi label="Email" path="email">
          <n-input v-model:value="form.email" placeholder="contact@supplier.com" type="email" />
        </n-form-item-gi>

        <!-- Status Toggle with Active/Inactive Labels -->
        <n-form-item-gi label="Status">
          <n-space align="center" :size="8">
            <n-tag :type="form.isActive ? 'success' : 'default'" :size="'small'">
              {{ form.isActive ? 'Active' : 'Inactive' }}
            </n-tag>
            <n-switch v-model:value="form.isActive" :rail-style="railStyle">
              <template #checked>✓</template>
              <template #unchecked>✕</template>
            </n-switch>
          </n-space>
        </n-form-item-gi>

        <!-- Address - Full Width -->
        <n-form-item-gi :span="2" label="Address">
          <n-input v-model:value="form.address" type="textarea" :autosize="{ minRows: 2, maxRows: 3 }"
            placeholder="Street, City, State, ZIP" />
        </n-form-item-gi>

        <!-- Items Supplied - Full Width -->
        <n-form-item-gi :span="2" label="Items">
          <n-select v-model:value="form.itemIds" :options="itemOptions" multiple filterable
            placeholder="Select items this supplier provides" :loading="loadingItems"
            :render-tag="renderItemTag" />
        </n-form-item-gi>

        <!-- Notes - Full Width -->
        <n-form-item-gi :span="2" label="Notes">
          <n-input v-model:value="form.notes" type="textarea" :autosize="{ minRows: 2 }"
            placeholder="Payment terms, delivery notes, etc." />
        </n-form-item-gi>
      </n-grid>
    </n-form>

    <template #footer>
      <n-space justify="end">
        <n-button @click="closeModal">Cancel</n-button>
        <n-button type="primary" :loading="submitting" @click="handleSubmit">
          {{ isEdit ? 'Update' : 'Create' }} Supplier
        </n-button>
      </n-space>
    </template>
  </n-modal>
</template>

<script setup lang="ts">
import { ref, computed, h, watch, onMounted } from 'vue';
import { NModal, NForm, NFormItemGi, NInput, NButton, NIcon, NSpace, NSwitch, NGrid, NSelect, NTag, useMessage, useLoadingBar } from 'naive-ui';
import type { FormInst, FormRules, SelectOption, CSSProperties } from 'naive-ui';
import { Icon } from '@iconify/vue';
import type { Supplier } from '@/types/supplier';
import type ItemData from '@/api/interfaces/Item';
import itemApi from '@/api/item';

const props = defineProps<{
  initialData?: Supplier | null;
}>();

const emit = defineEmits<{
  (e: 'submit', data: Omit<Supplier, 'id' | 'createdAt' | 'updatedAt'>): void;
  (e: 'cancel'): void;
}>();

const message = useMessage();
const formRef = ref<FormInst>();
const showModal = ref(true);
const submitting = ref(false);

// Items from API
const items = ref<ItemData[]>([]);
const loadingItems = ref(false);

const loadItems = async () => {
  loadingItems.value = true;
  try {
    const { data } = await itemApi.getItems();
    console.log('Items API response:', data);
    items.value = data.data ?? [];
    console.log('Loaded items:', items.value.length, items.value);
  } catch (err) {
    console.error('Failed to load items:', err);
  } finally {
    loadingItems.value = false;
  }
};

onMounted(() => loadItems());

const itemOptions = computed(() =>
  items.value.map(i => ({
    label: i.name,
    value: i.id,
    category: i.category,
  }))
);

const defaultForm = (): Omit<Supplier, 'id' | 'createdAt' | 'updatedAt'> => ({
  name: '',
  contactPerson: '',
  phone: '',
  email: '',
  address: '',
  notes: '',
  itemIds: [],
  isActive: true,
  businessId: '',
});

const form = ref(defaultForm());

const isEdit = computed(() => !!props.initialData?.id);

// Reset form when modal opens with different data
watch(
  () => props.initialData,
  (data) => {
    if (data) {
      form.value = {
        name: data.name,
        contactPerson: data.contactPerson,
        phone: data.phone,
        email: data.email || '',
        address: data.address || '',
        notes: data.notes || '',
        itemIds: [...data.itemIds],
        isActive: data.isActive,
        businessId: (data as any).businessId || '',
      };
    } else {
      form.value = defaultForm();
    }
  },
  { immediate: true }
);

const rules: FormRules = {
  name: { required: true, message: 'Name is required', trigger: 'blur' },
  contactPerson: { required: true, message: 'Contact person is required', trigger: 'blur' },
  phone: [
    { required: true, message: 'Phone is required', trigger: 'blur' },
    { pattern: /^\+?\d{10,15}$/, message: 'Enter valid phone number', trigger: 'blur' }
  ]
};

const railStyle = ({ checked }: { checked: boolean }): CSSProperties => ({
  background: checked ? '#18a058' : '#d03050',
});

const formatPhone = () => {
  form.value.phone = form.value.phone.replace(/[^\d+]/g, '');
  if (form.value.phone.startsWith('00')) {
    form.value.phone = form.value.phone.replace('00', '+');
  } else if (form.value.phone.startsWith('0') && !form.value.phone.startsWith('+')) {
    form.value.phone = '+55' + form.value.phone.slice(1);
  } else if (!form.value.phone.startsWith('+') && form.value.phone.length >= 10) {
    form.value.phone = '+' + form.value.phone;
  }
};

const renderItemTag = ({ option }: { option: SelectOption }) => {
  return h(NTag, { type: 'warning', size: 'small' as const }, {
    default: () => `${option.label} [${option.category ?? 'item'}]`
  });
};

const closeModal = () => {
  showModal.value = false;
};

const handleSubmit = async () => {
  try {
    await formRef.value?.validate();
    submitting.value = true;
    emit('submit', { ...form.value });
  } catch {
    message.error('Please fix form errors');
  } finally {
    submitting.value = false;
  }
};
</script>
