<script setup lang="ts">
import { ref, provide } from 'vue'
import { RouterLink, RouterView } from 'vue-router'
import { NMessageProvider, NDialogProvider, NButton, NIcon } from 'naive-ui'
import {
  People20Filled,
  Wallet20Filled,
  Receipt20Filled,
  CalendarLtr20Filled,
  Image20Filled,
} from '@vicons/fluent'
import SidebarDropdown from './components/SidebarDropdown.vue'
import { Icon } from '@iconify/vue'

const storedUser = localStorage.getItem('user')
const user = storedUser ? JSON.parse(storedUser).user : null
const isSidebarCollapsed = ref(false)

const toggleSidebar = () => {
  isSidebarCollapsed.value = !isSidebarCollapsed.value
}

console.log(user)

// Provide the state and toggle function for children components
provide('isSidebarCollapsed', isSidebarCollapsed)
provide('toggleSidebar', toggleSidebar)
</script>

<template>
  <n-dialog-provider>
    <n-message-provider>
      <div id="app" :class="{ 'sidebar-collapsed': isSidebarCollapsed }">
      <div class="sidebar">
        <div class="sidebar-header">
          <n-button text @click="toggleSidebar" class="sidebar-toggle-button">
            <Icon icon="mdi:menu-open" style="font-size: 2em;" />
          </n-button>
        </div>
        <div class="sidebar-content">
          <sidebar-dropdown icon="proicons:branch" title="Branches" :default-open="true">
            <RouterLink to="/patients">
              <Icon style="font-size: 1.45em;" icon="medical-icon:inpatient" />
              <span>Patients</span>
            </RouterLink>
            <RouterLink to="/employees">
              <n-icon :size="24">
                <People20Filled />
              </n-icon>
              <span>Employees</span>
            </RouterLink>
            <RouterLink to="/accounts">
              <n-icon :size="24">
                <Wallet20Filled />
              </n-icon>
              <span>Accounts</span>
            </RouterLink>
            <RouterLink to="/transactions">
              <n-icon :size="24">
                <Receipt20Filled />
              </n-icon>
              <span>Transactions</span>
            </RouterLink>
            <RouterLink to="/appointments">
              <n-icon :size="24">
                <CalendarLtr20Filled />
              </n-icon>
              <span>Appointments</span>
            </RouterLink>
            <RouterLink to="/xrays">
              <n-icon :size="24">
                <Image20Filled />
              </n-icon>
              <span>Dental Xrays</span>
            </RouterLink>
            <RouterLink to="/prescriptions">
              <Icon style="font-size: 1.55em;" icon="majesticons:script-prescription" />
              <span>Prescriptions</span>
            </RouterLink>
            <RouterLink to="/inventory">
              <Icon style="font-size: 1.55em;" icon="si:ai-inventory-fill" />
              <span>Inventory</span>
            </RouterLink>
            <RouterLink to="/suppliers">
              <Icon style="font-size: 1.55em;" icon="fluent-emoji-high-contrast:police-officer" />
              <span>Suppliers</span>
            </RouterLink>
          </sidebar-dropdown>
          <hr style="opacity: .3; margin: .3em 1.45em;" />
          <sidebar-dropdown icon="ph:gear-six" title="Settings">
            <RouterLink to="/profile">Profile</RouterLink>
            <RouterLink to="/settings">Settings</RouterLink>
          </sidebar-dropdown>
          <hr style="opacity: .3; margin: .3em 1.45em;" />
          <sidebar-dropdown icon="ph:question" title="Support">
            <RouterLink to="/help">Help center</RouterLink>
            <RouterLink to="/contact">Contact support</RouterLink>
          </sidebar-dropdown>
          <!-- <hr style="opacity: .3; margin: .3em 1.45em;" /> -->

          <div class="sidebar-footer" style="margin-top: auto;">
            <div class="user-info">
              <Icon icon="gg:profile" style="font-size: 1.9em;" />
              <div class="details">
                <p>{{ user ? `${user.employee.f_name} ${user.employee.l_name} ` : 'Guest' }}</p>
                <p class="gmail">{{ user ? user.email : 'guest@example.com' }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="content">
        <RouterView />
      </div>
    </div>
    </n-message-provider>
  </n-dialog-provider>
</template>
