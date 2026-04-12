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

        <!-- Products Supplied - Full Width -->
        <n-form-item-gi :span="2" label="Products">
          <n-select v-model:value="form.productIds" :options="productOptions" multiple filterable
            placeholder="Select products this supplier provides" :render-tag="renderTag" />
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
import { ref, computed, h, watch } from 'vue';
import { NModal, NForm, NFormItemGi, NInput, NButton, NIcon, NSpace, NSwitch, NGrid, NSelect, NTag, useMessage } from 'naive-ui';
import type { FormInst, FormRules, SelectOption, CSSProperties } from 'naive-ui';
import { Icon } from '@iconify/vue';
import type { Supplier } from '@/types/supplier';

const props = defineProps<{
  productOptions: Array<{ label: string; value: string; sku: string }>;
  initialData?: Supplier | null;
}>();

const emit = defineEmits<{
  (e: 'submit', data: Omit<Supplier, 'id' | 'createdAt' | 'updatedAt'> & { businessId?: string }): void;
  (e: 'cancel'): void;
}>();

const message = useMessage();
const formRef = ref<FormInst>();
const showModal = ref(true);
const submitting = ref(false);

const defaultForm = (): Omit<Supplier, 'id' | 'createdAt' | 'updatedAt'> & { businessId?: string } => ({
  name: '',
  contactPerson: '',
  phone: '',
  email: '',
  address: '',
  notes: '',
  productIds: [],
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
        productIds: [...data.productIds],
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

const renderTag = ({ option }: { option: SelectOption }) => {
  return h(NTag, { type: 'info', size: 'small' as const }, {
    default: () => `${option.label} (${option.sku})`
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
