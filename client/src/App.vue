<script setup lang="ts">
import { ref, provide, h, computed, onMounted, watch } from 'vue'
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router'
import {
  NMessageProvider,
  NDialogProvider,
  NButton,
  NIcon,
  NDropdown,
} from 'naive-ui'
import {
  People20Filled,
  Wallet20Filled,
  Receipt20Filled,
  CalendarLtr20Filled,
  Image20Filled,
} from '@vicons/fluent'
import SidebarDropdown from './components/SidebarDropdown.vue'
import { Icon } from '@iconify/vue'
import userApi from './api/user';
import { useAuthStore } from './stores/authStore';
import { useBranchStore } from './stores/branchStore'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const branchStore = useBranchStore()

const storedUser = localStorage.getItem('user')
const user = storedUser ? JSON.parse(storedUser).user : null

const isSidebarCollapsed = ref(false)
const showUserMenu = ref(false)

const selectBranch = (branchId: number) => {
  branchStore.setSelectedBranchId(branchId)
}

const toggleSidebar = () => {
  isSidebarCollapsed.value = !isSidebarCollapsed.value
}

const toggleUserMenu = () => {
  showUserMenu.value = !showUserMenu.value
}

const userMenuOptions = [
  {
    label: 'View profile',
    key: 'profile',
    icon: () =>
      h(NIcon, null, {
        default: () => h(Icon, { icon: 'gg:profile' }),
      }),
  },
  {
    label: 'Settings',
    key: 'settings',
    icon: () =>
      h(NIcon, null, {
        default: () => h(Icon, { icon: 'ph:gear-six' }),
      }),
  },
  {
    type: 'divider',
    key: 'divider',
  },
  {
    label: 'Logout',
    key: 'logout',
    icon: () =>
      h(NIcon, null, {
        default: () =>
          h(Icon, { icon: 'material-symbols:logout-rounded' }),
      }),
  },
]

const handleUserSelect = (key: string | number) => {
  showUserMenu.value = false

  if (key === 'profile') router.push('/profile')
  if (key === 'settings') router.push('/settings')

  if (key === 'logout') {
    console.log('Logging out...')
    userApi.logout().then(() => {
      authStore.logout()
      router.push('/login')
    }).catch((err) => {
      console.log(err)
    })
  }
}

const syncBranchFromRoute = () => {
  const raw = route.query.branchId
  const asNumber = typeof raw === 'string' ? Number(raw) : NaN
  if (Number.isFinite(asNumber)) branchStore.setSelectedBranchId(asNumber)
}

onMounted(async () => {
  if (authStore.isLoggedIn) {
    await branchStore.fetchBranches()
    syncBranchFromRoute()
  }
})

watch(
  () => authStore.isLoggedIn,
  async (isLoggedIn) => {
    if (!isLoggedIn) return
    await branchStore.fetchBranches()
    syncBranchFromRoute()
  },
)

watch(
  () => branchStore.selectedBranchId,
  (branchId) => {
    if (!authStore.isLoggedIn) return
    const current = route.query.branchId
    const currentAsNumber = typeof current === 'string' ? Number(current) : NaN
    if (branchId != null && Number.isFinite(currentAsNumber) && currentAsNumber === branchId) return

    router.replace({
      query: {
        ...route.query,
        branchId: branchId == null ? undefined : String(branchId),
      },
    })
  },
)

// provide
provide('isSidebarCollapsed', isSidebarCollapsed)
provide('toggleSidebar', toggleSidebar)
provide('selectedBranchId', computed(() => branchStore.selectedBranchId))
</script>

<template>
  <n-dialog-provider>
    <n-message-provider>
      <div id="app" :class="{ 'sidebar-collapsed': isSidebarCollapsed }" >
        <div class="sidebar" v-if="authStore.isLoggedIn">
          <div class="sidebar-header">
            <n-button text @click="toggleSidebar" class="sidebar-toggle-button">
              <Icon icon="mdi:menu-open" style="font-size: 2em;" />
            </n-button>
          </div>

          <div class="sidebar-content">
            <sidebar-dropdown icon="proicons:branch" title="Branches" :default-open="true">
              <div v-if="branchStore.loading" class="branches-loading">Loading branches...</div>
              <div v-else-if="branchStore.branches.length === 0" class="branches-empty">No branches found</div>

              <div v-else class="branches-list">
                <div v-for="b in branchStore.branches" :key="b.id" class="branch-item">
                  <button
                    type="button"
                    class="branch-item__header"
                    :class="{ 'branch-item__header--active': branchStore.selectedBranchId === b.id }"
                    @click="selectBranch(b.id)"
                  >
                    <span class="branch-item__name">{{ b.branchName }}</span>
                    <Icon
                      :icon="branchStore.selectedBranchId === b.id ? 'mdi:chevron-down' : 'mdi:chevron-right'"
                      class="branch-item__chevron"
                    />
                  </button>

                  <div v-show="branchStore.selectedBranchId === b.id" class="branch-item__links">
                    <RouterLink :to="{ path: '/patients', query: { ...route.query, branchId: String(b.id) } }">
                      <Icon style="font-size: 1.45em;" icon="medical-icon:inpatient" />
                      <span>Patients</span>
                    </RouterLink>
                    <RouterLink :to="{ path: '/employees', query: { ...route.query, branchId: String(b.id) } }">
                      <n-icon :size="24">
                        <People20Filled />
                      </n-icon>
                      <span>Employees</span>
                    </RouterLink>
                    <RouterLink :to="{ path: '/accounts', query: { ...route.query, branchId: String(b.id) } }">
                      <n-icon :size="24">
                        <Wallet20Filled />
                      </n-icon>
                      <span>Accounts</span>
                    </RouterLink>
                    <RouterLink :to="{ path: '/transactions', query: { ...route.query, branchId: String(b.id) } }">
                      <n-icon :size="24">
                        <Receipt20Filled />
                      </n-icon>
                      <span>Transactions</span>
                    </RouterLink>
                    <RouterLink :to="{ path: '/appointments', query: { ...route.query, branchId: String(b.id) } }">
                      <n-icon :size="24">
                        <CalendarLtr20Filled />
                      </n-icon>
                      <span>Appointments</span>
                    </RouterLink>
                    <RouterLink :to="{ path: '/xrays', query: { ...route.query, branchId: String(b.id) } }">
                      <n-icon :size="24">
                        <Image20Filled />
                      </n-icon>
                      <span>Dental Xrays</span>
                    </RouterLink>
                    <RouterLink :to="{ path: '/prescriptions', query: { ...route.query, branchId: String(b.id) } }">
                      <Icon style="font-size: 1.55em;" icon="majesticons:script-prescription" />
                      <span>Prescriptions</span>
                    </RouterLink>
                    <RouterLink :to="{ path: '/inventory', query: { ...route.query, branchId: String(b.id) } }">
                      <Icon style="font-size: 1.55em;" icon="si:ai-inventory-fill" />
                      <span>Inventory</span>
                    </RouterLink>
                    <RouterLink :to="{ path: '/suppliers', query: { ...route.query, branchId: String(b.id) } }">
                      <Icon style="font-size: 1.55em;" icon="fluent-emoji-high-contrast:police-officer" />
                      <span>Suppliers</span>
                    </RouterLink>
                  </div>
                </div>
              </div>
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

            <div class="sidebar-footer">
              <n-dropdown trigger="click" :options="userMenuOptions" @select="handleUserSelect" placement="top-start"
                :show-arrow="true" :z-index="9999" to="body">
                <div class="user-info">
                  <Icon icon="gg:profile" style="font-size: 1.9em;" />
                  <div class="details">
                    <p>{{ user ? `${user.employee.fName} ${user.employee.lName}` : 'Guest' }}</p>
                    <p class="gmail">{{ user ? user.email : 'guest@example.com' }}</p>
                  </div>
                </div>
              </n-dropdown>
            </div>
          </div>
        </div>

        <div class="content" >
          <RouterView />
        </div>
      </div>
    </n-message-provider>
  </n-dialog-provider>
</template>

<style scoped>
.branches-loading,
.branches-empty {
  padding: 6px 12px;
  opacity: 0.7;
  font-size: 0.85rem;
}

.branches-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding-right: 8px;
}

.branch-item__header {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  padding: 6px 10px;
  border-radius: 8px;
  border: none;
  background: rgba(15, 23, 42, 0.03);
  cursor: pointer;
  color: inherit;
  text-align: left;
  transition: background-color 0.16s ease, transform 0.12s ease;
}

.branch-item__header:hover {
  background: rgba(15, 23, 42, 0.06);
  transform: translateX(1px);
}

.branch-item__header--active {
  background: rgba(15, 23, 42, 0.08);
}

.branch-item__name {
  flex: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  font-weight: 600;
}

.branch-item__chevron {
  opacity: 0.8;
}

.branch-item__links {
  margin-top: 4px;
  margin-left: 10px;
  padding-left: 10px;
  border-left: 1px solid rgba(15, 23, 42, 0.12);
  display: flex;
  flex-direction: column;
}

.user-trigger {
  cursor: pointer;
  width: 100%;
  border-radius: 12px;
  padding: 10px 12px;
  transition: background-color 0.2s ease;
}

.user-trigger:hover {
  background: rgba(255, 255, 255, 0.06);
}
</style>
