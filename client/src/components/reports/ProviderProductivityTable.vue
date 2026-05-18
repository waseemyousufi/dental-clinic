<script setup lang="ts">
import { defineProps } from 'vue'

interface ProviderData {
  name: string
  patientsTreated: number
  hoursLogged: number
  revenueInvoiced: number
  cashCollected: number
}

defineProps<{ data: ProviderData[] }>()

const formatCurrency = (value: number) => {
  return `AFN ${value.toLocaleString()}`
}
</script>

<template>
  <div class="provider-productivity-table">
    <div class="table-title">Doctor Productivity</div>
    <table>
      <thead>
        <tr>
          <th scope="col">
            Doctor Name
          </th>
          <th scope="col">
            Patients Treated
          </th>
          <!-- <th scope="col">
            Hours Logged
          </th> -->
          <th scope="col">
            Revenue Invoiced
          </th>
          <!-- <th scope="col">
            Cash Collected
          </th> -->
        </tr>
      </thead>
      <tbody>
        <tr v-for="provider in data" :key="provider.name">
          <td data-label="Provider Name">
            {{ provider.name }}
          </td>
          <td data-label="Patients Treated">
            {{ provider.patientsTreated }}
          </td>
          <!-- <td data-label="Hours Logged">
            {{ provider.hoursLogged }}h
          </td> -->
          <td data-label="Revenue Invoiced">
            {{ formatCurrency(provider.revenueInvoiced) }}
          </td>
          <!-- <td data-label="Cash Collected">
            {{ formatCurrency(provider.cashCollected) }}
          </td> -->
        </tr>
        <tr v-if="!data.length">
          <td colspan="5" class="empty">No provider data for this range.</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped>
.provider-productivity-table {
  background: #ffffff;
  border-radius: 0.9rem;
  padding: 1rem;
  overflow: auto;
}

.table-title {
  margin-bottom: 0.75rem;
  font-size: 0.8rem;
  font-weight: 800;
  text-transform: uppercase;
  color: #374151;
}

table {
  width: 100%;
  border-collapse: collapse;
  min-width: 640px;
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

@media (max-width: 720px) {
  .provider-productivity-table {
    padding: 0.85rem;
  }

  table,
  thead,
  tbody,
  tr,
  td {
    display: block;
    width: 100%;
  }

  thead {
    display: none;
  }

  tbody tr {
    margin-bottom: 0.75rem;
    padding: 0.7rem 0.75rem;
    border: 1px solid #eef2f7;
    border-radius: 0.95rem;
    background: #f8fafc;
  }

  td {
    border: 0;
    padding: 0.35rem 0;
    font-size: 0.88rem;
  }

  td:not(.empty)::before {
    content: attr(data-label);
    display: block;
    margin-bottom: 0.15rem;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #6b7280;
  }

  td.empty::before {
    display: none;
  }
}
</style>
