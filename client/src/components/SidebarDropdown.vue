<script setup lang="ts">
import { ref, computed } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  defaultOpen: {
    type: Boolean,
    default: false,
  },
  icon: {
    type: String,
    default: '',
  },
})

const isOpen = ref(props.defaultOpen)

</script>

<template>
  <div class="sidebar-dropdown" :class="{ 'sidebar-dropdown--open': isOpen }">
    <button type="button" class="sidebar-dropdown__header" @click="isOpen = !isOpen">
      <Icon :icon="icon" style="margin-right: 0.5em; font-size: 1.25em;" />
      <span class="sidebar-dropdown__title">
        {{ title }}
      </span>
      <Icon :icon="isOpen ? 'mdi:chevron-down' : 'mdi:chevron-right'" class="sidebar-dropdown__icon" />
    </button>

    <transition name="sidebar-dropdown-fade">
      <div v-show="isOpen" class="sidebar-dropdown__body">
        <slot />
      </div>
    </transition>
  </div>
</template>

<style scoped>
.sidebar-dropdown {
  display: flex;
  flex-direction: column;
  border-radius: 8px;
  padding: 4px 0;
  color: #111827;
  /* dark text for light theme */
}

.sidebar-dropdown__header {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 12px;
  border-radius: 8px;
  background: transparent;
  border: none;
  color: inherit;
  font-size: 0.9rem;
  font-weight: 500;
  text-align: left;
  cursor: pointer;
  transition:
    background-color 0.18s ease,
    color 0.18s ease,
    transform 0.12s ease;
}

.sidebar-dropdown__header:hover {
  background: rgba(15, 23, 42, 0.04);
  color: #020617;
  transform: translateX(1px);
}

.sidebar-dropdown--open .sidebar-dropdown__header {
  background: rgba(15, 23, 42, 0.06);
  color: #020617;
}

.sidebar-dropdown__title {
  flex: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.sidebar-dropdown__icon {
  margin-left: 8px;
  transition: transform 0.18s ease;
}

.sidebar-dropdown__body {
  margin-top: 4px;
  padding: 4px 0 4px 12px;
  border-left: 1px solid rgba(15, 23, 42, 0.12);
  display: flex;
  flex-direction: column;
  gap: 4px;
  font-size: 0.85rem;
}

.sidebar-dropdown__body ::v-deep(> *) {
  padding: 4px 4px;
  border-radius: 6px;
  cursor: pointer;
  transition:
    background-color 0.16s ease,
    transform 0.12s ease;
}

.sidebar-dropdown__body ::v-deep(> *:hover) {
  background: rgba(15, 23, 42, 0.06);
  transform: translateX(2px);
}

.sidebar-dropdown-fade-enter-active,
.sidebar-dropdown-fade-leave-active {
  transition:
    opacity 0.16s ease,
    transform 0.16s ease;
}

.sidebar-dropdown-fade-enter-from,
.sidebar-dropdown-fade-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
