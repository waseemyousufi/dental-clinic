<script setup lang="ts">
import { defineProps } from 'vue'

interface AssistantData {
  name: string
  task: string
  count: number
  speed?: string // Optional for sterilization
  itemsProcessed?: number // Optional for inventory distribution
}

defineProps<{ data: AssistantData[] }>()
</script>

<template>
  <div class="assistant-ledger">
    <h3>Assistant Ledger</h3>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th scope="col">
              Assistant Name
            </th>
            <th scope="col">
              Task
            </th>
            <th scope="col">
              Count
            </th>
            <th scope="col">
              Details
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="assistant in data" :key="assistant.name + assistant.task">
            <td>
              {{ assistant.name }}
            </td>
            <td>
              {{ assistant.task }}
            </td>
            <td>
              {{ assistant.count }}
            </td>
            <td>
              <span v-if="assistant.speed">Speed: {{ assistant.speed }}</span>
              <span v-else-if="assistant.itemsProcessed">Items Processed: {{ assistant.itemsProcessed }}</span>
              <span v-else>-</span>
            </td>
          </tr>
          <tr v-if="!data.length">
            <td colspan="4" class="empty">No assistant ledger entries for this range.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.assistant-ledger {
  background: #ffffff;
  border-radius: 0.9rem;
  padding: 1rem;
}

h3 {
  margin: 0 0 0.75rem;
  font-size: 0.8rem;
  font-weight: 800;
  text-transform: uppercase;
  color: #374151;
}

.table-wrap {
  overflow: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
  min-width: 520px;
}

th,
td {
  text-align: left;
  padding: 0.55rem 0.45rem;
  font-size: 0.82rem;
  border-bottom: 1px solid #f3f4f6;
  color: #1f2937;
}

th {
  font-size: 0.7rem;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: #6b7280;
}

.empty {
  text-align: center;
  color: #6b7280;
}
</style>
