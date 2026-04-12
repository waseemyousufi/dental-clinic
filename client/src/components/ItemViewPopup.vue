<script setup lang="ts">
import { defineProps, ref, computed } from 'vue';
import { NModal, NCard, NDescriptions, NDescriptionsItem } from 'naive-ui';

const props = defineProps<{
  show: boolean;
  itemData: Record<string, any> | null;
  title: string;
}>();

const emit = defineEmits(['update:show']);

const showModal = computed({
  get: () => props.show,
  set: (value) => emit('update:show', value),
});

const formattedItemData = computed(() => {
  if (!props.itemData) return {};
  const data: Record<string, string> = {};
  for (const key in props.itemData) {
    if (Object.prototype.hasOwnProperty.call(props.itemData, key)) {
      const value = props.itemData[key];
      // Recursively handle nested objects like 'experience'
      if (typeof value === 'object' && value !== null && !Array.isArray(value)) {
        for (const subKey in value) {
            if (Object.prototype.hasOwnProperty.call(value, subKey)) {
                data[`${key}.${subKey}`] = String(value[subKey]);
            }
        }
      } else {
        data[key] = String(value);
      }
    }
  }
  return data;
});

const formatKey = (key: string) => {
  return key
    .replace(/([A-Z])/g, ' $1') // Add space before capital letters
    .replace(/^./, (str) => str.toUpperCase()) // Capitalize the first letter
    .replace(/\.([a-z])/g, (match, letter) => ` (${letter.toUpperCase()}`) + (key.includes('.') ? ')' : ''); // Handle nested object keys
};
</script>

<template>
  <n-modal v-model:show="showModal" preset="card" :title="title" style="max-width: 800px; max-height: 90vh; overflow: auto;">
    <n-descriptions
      label-placement="top"
      :column="2"
      bordered
      v-if="itemData"
    >
      <n-descriptions-item v-for="(value, key) in formattedItemData" :key="key" :label="formatKey(key)">
        {{ value || 'N/A' }}
      </n-descriptions-item>
    </n-descriptions>
    <div v-else>
      No data to display.
    </div>
  </n-modal>
</template>

<style scoped>
</style>
