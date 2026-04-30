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
import SidebarDropdown from './components/SidebarDropdown.vue'
import { Icon } from '@iconify/vue'
import userApi from './api/user';
import { useAuthStore } from './stores/authStore';
import { useBranchStore } from './stores/branchStore'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const branchStore = useBranchStore()

// Parse user from localStorage
const storedUser = localStorage.getItem('user')
const parsedUser = storedUser ? JSON.parse(storedUser) : null
const user = parsedUser?.user || null
const expandedBranchIds = ref<number[]>([])

// ✅ Get branchId from localStorage->user->employee->branchId
const userBranchId = computed(() => {
  const id = user?.employee?.branchId
  return typeof id === 'number' && Number.isFinite(id) ? id : null
})

// ✅ Check if user is admin (ADJUST THIS to match your auth system)
const isAdmin = computed(() => {
  // Examples - pick what matches your backend:
  // return user?.role === 'admin'
  // return user?.employee?.isAdmin === true
  // return user?.permissions?.includes('manage_branches')
  return user?.employee?.position === 'admin'
})

const isSidebarCollapsed = ref(false)
const showUserMenu = ref(false)

// ✅ Nav links defined HERE in the same script block (DRY)
const navLinks = [
  {
    path: '/overview',
    label: 'Overview',
    icon: () => h(Icon, { icon: 'mdi:view-dashboard-variant', style: 'font-size: 1.45em;' })
  },
  {
    path: '/patients',
    label: 'Patients',
    icon: () => h(Icon, { icon: 'mdi:account-multiple', style: 'font-size: 1.45em;' })
  },
  {
    path: '/employees',
    label: 'Employees',
    icon: () => h(Icon, { icon: 'mdi:badge-account-outline', style: 'font-size: 1.45em;' })
  },
  {
    path: '/accounts',
    label: 'Accounts',
    icon: () => h(Icon, { icon: 'mdi:wallet-outline', style: 'font-size: 1.45em;' })
  },
  {
    path: '/transactions',
    label: 'Transactions',
    icon: () => h(Icon, { icon: 'mdi:receipt-text-outline', style: 'font-size: 1.45em;' })
  },
  {
    path: '/appointments',
    label: 'Appointments',
    icon: () => h(Icon, { icon: 'mdi:calendar-clock', style: 'font-size: 1.45em;' })
  },
  // {
  //   path: '/xrays',
  //   label: 'Dental Xrays',
  //   icon: () => h(Icon, { icon: 'mdi:image-filter-hdr', style: 'font-size: 1.45em;' })
  // },
  {
    path: '/prescriptions',
    label: 'Prescriptions',
    icon: () => h(Icon, { icon: 'mdi:script-text-outline', style: 'font-size: 1.45em;' })
  },
  {
    path: '/inventory',
    label: 'Inventory',
    icon: () => h(Icon, { icon: 'mdi:package-variant-closed', style: 'font-size: 1.45em;' })
  },
  {
    path: '/suppliers',
    label: 'Suppliers',
    icon: () => h(Icon, { icon: 'mdi:truck-delivery', style: 'font-size: 1.45em;' })
  },
]

const selectBranch = (branchId: number) => {
  branchStore.setSelectedBranchId(branchId)
}

const toggleSidebar = () => {
  isSidebarCollapsed.value = !isSidebarCollapsed.value
}

const toggleUserMenu = () => {
  showUserMenu.value = !showUserMenu.value
}

const toggleBranchExpansion = (branchId: number) => {
  expandedBranchIds.value = []
  expandedBranchIds.value.push(branchId)
  selectBranch(branchId)
  // const index = expandedBranchIds.value.indexOf(branchId)
  // if (index > -1) {
  //   // Remove if already open
  //   expandedBranchIds.value.splice(index, 1)
  // } else {
  //   // Add if closed
  //   expandedBranchIds.value.push(branchId)
  // }
}

const isBranchExpanded = (branchId: number) => {
  return expandedBranchIds.value.includes(branchId)
}

// ... rest of your script

const userMenuOptions = [
  {
    label: 'View profile',
    key: 'profile',
    icon: () => h(NIcon, null, { default: () => h(Icon, { icon: 'gg:profile' }) }),
  },
  {
    label: 'Settings',
    key: 'settings',
    icon: () => h(NIcon, null, { default: () => h(Icon, { icon: 'ph:gear-six' }) }),
  },
  { type: 'divider', key: 'divider' },
  {
    label: 'Logout',
    key: 'logout',
    icon: () => h(NIcon, null, { default: () => h(Icon, { icon: 'material-symbols:logout-rounded' }) }),
  },
]

const handleUserSelect = (key: string | number) => {
  showUserMenu.value = false
  if (key === 'profile') router.push('/profile')
  if (key === 'settings') router.push('/settings')
  if (key === 'logout') {
    userApi.logout().then(() => {
      authStore.logout()
      router.push('/login')
    }).catch(console.log)
  }
}

// ✅ Only fetch branches & sync route for admins
onMounted(async () => {
  if (!authStore.isLoggedIn) return

  if (isAdmin.value) {
    await branchStore.fetchBranches()
    const raw = route.query.branchId
    const asNumber = typeof raw === 'string' ? Number(raw) : NaN
    if (Number.isFinite(asNumber)) branchStore.setSelectedBranchId(asNumber)
  }
  // ✅ For non-admins: ensure URL has user's branchId if query params are used
  else if (userBranchId.value != null) {
    const current = route.query.branchId
    if (String(current) !== String(userBranchId.value)) {
      router.replace({ query: { ...route.query, branchId: String(userBranchId.value) } })
    }
  }
})

// ✅ Watch auth state
watch(
  () => authStore.isLoggedIn,
  async (isLoggedIn) => {
    if (!isLoggedIn) return
    if (isAdmin.value) {
      await branchStore.fetchBranches()
      const raw = route.query.branchId
      const asNumber = typeof raw === 'string' ? Number(raw) : NaN
      if (Number.isFinite(asNumber)) branchStore.setSelectedBranchId(asNumber)
    } else if (userBranchId.value != null) {
      const current = route.query.branchId
      if (String(current) !== String(userBranchId.value)) {
        router.replace({ query: { ...route.query, branchId: String(userBranchId.value) } })
      }
    }
  },
)

// ✅ Only sync branchStore changes to URL for admins
watch(
  () => isAdmin.value ? branchStore.selectedBranchId : null,
  (branchId) => {
    if (!authStore.isLoggedIn || !isAdmin.value) return
    const current = route.query.branchId
    const currentAsNumber = typeof current === 'string' ? Number(current) : NaN
    if (branchId != null && Number.isFinite(currentAsNumber) && currentAsNumber === branchId) return
    router.replace({ query: { ...route.query, branchId: String(branchId) } })
  },
)

// ✅ Provide selectedBranchId from correct source
provide('isSidebarCollapsed', isSidebarCollapsed)
provide('toggleSidebar', toggleSidebar)
provide('selectedBranchId', computed(() =>
  isAdmin.value ? branchStore.selectedBranchId : userBranchId.value
))
</script>

<template>
  <n-dialog-provider>
    <n-message-provider>
      <div id="app" :class="{ 'sidebar-collapsed': isSidebarCollapsed }">
        <n-button v-if="isSidebarCollapsed" circle class="floating-menu-btn" @click="toggleSidebar">
          <Icon icon="mdi:menu" style="font-size: 1.8em;" />
        </n-button>
        <div class="sidebar" v-if="authStore.isLoggedIn">
          <div class="sidebar-header">
            <n-button text @click="toggleSidebar" class="sidebar-toggle-button">
              <Icon icon="mdi:menu-open" style="font-size: 2em;" />
            </n-button>
          </div>

          <div class="sidebar-content" v-if="!isSidebarCollapsed">
            <!-- ✅ Branches dropdown: ONLY for admins -->
            <sidebar-dropdown v-if="isAdmin" icon="proicons:branch" title="Branches" :default-open="true">
              <div v-if="branchStore.loading" class="branches-loading">Loading branches...</div>
              <div v-else-if="branchStore.branches.length === 0" class="branches-empty">
                branches not found
              </div>
              <!-- Inside your Admin Sidebar Dropdown -->
              <div v-else class="branches-list">
                <div v-for="b in branchStore.branches" :key="b.id" class="branch-item">

                  <!-- HEADER -->
                  <button type="button" class="branch-item__header"
                    :class="{ 'branch-item__header--active': branchStore.selectedBranchId === b.id }"
                    @click.stop="toggleBranchExpansion(b.id)">
                    <span class="branch-item__name">{{ b.branchName }}</span>
                    <Icon :icon="isBranchExpanded(b.id) ? 'mdi:chevron-down' : 'mdi:chevron-right'"
                      class="branch-item__chevron" />
                  </button>

                  <!-- LINKS -->
                  <!-- Use the helper function here instead of .has() -->
                  <div v-show="isBranchExpanded(b.id)" class="branch-item__links">
                    <RouterLink v-for="link in navLinks" :key="link.path"
                      :to="{ path: link.path, query: { ...route.query, branchId: String(b.id) } }"
                      @click="branchStore.setSelectedBranchId(b.id)" :class="[
                        'branch-link-item',
                        { 'is-active-branch-link': branchStore.selectedBranchId === b.id }
                      ]">
                      <component :is="link.icon" />
                      <span>{{ link.label }}</span>
                    </RouterLink>
                  </div>

                </div>
              </div>
            </sidebar-dropdown>

            <!-- ✅ Direct nav links for non-admins (single branch from localStorage) -->
            <div v-else-if="userBranchId" class="single-branch-nav">
              <RouterLink v-for="link in navLinks" :key="link.path"
                :to="{ path: link.path, query: { ...route.query, branchId: String(userBranchId) } }">
                <component :is="link.icon" />
                <span>{{ link.label }}</span>
              </RouterLink>
            </div>

            <hr style="opacity: .3; margin: .3em 1.45em;" />

            <sidebar-dropdown icon="ph:gear-six" title="Settings">
              <RouterLink to="/profile">Profile</RouterLink>
              <RouterLink to="/settings">Settings</RouterLink>
            </sidebar-dropdown>

            <hr style="opacity: .3; margin: .3em 1.45em;" />

            <sidebar-dropdown icon="" title="Reports">
              <RouterLink to="/employee-activity-log">Employee Activity Log</RouterLink>
              <RouterLink to="/finance-reports">Finance</RouterLink>
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

        <div class="content">
          <RouterView :key="$route.fullPath" />
        </div>
      </div>
    </n-message-provider>
  </n-dialog-provider>
</template>

<style scoped>
#app {
  display: flex;
  overflow-x: hidden;
  min-height: 100vh;

  .sidebar {
    padding: 0.2em;
    width: 200px;
    background: #f8f9fa;
    display: flex;
    flex-direction: column;
    height: 100vh;
    box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
    position: fixed;
    top: 0;
    left: 0;
    z-index: 200;
    box-sizing: border-box;
    transition:
      width 0.3s ease-in-out,
      min-width 0.3s ease-in-out;
    will-change: width, min-width;

    .sidebar-header {
      display: flex;
      justify-content: flex-end;
      padding: 8px;
      flex-shrink: 0;
    }

    .sidebar-toggle-button {
      transition: transform 0.3s ease-in-out;
    }

    .sidebar-content {
      flex: 1;
      min-height: 0;
      overflow-y: auto;
      overflow-x: hidden;
      -webkit-overflow-scrolling: touch;
      padding-bottom: 5em;

      &::-webkit-scrollbar {
        display: none;
        appearance: none;
      }
    }

    .sidebar-footer {
      padding: 8px;
      text-align: center;
      font-size: 0.9em;
      color: #666;
      display: flex;
      position: absolute;
      left: 0;
      bottom: 0;
      width: 100%;
      color: black;
      cursor: pointer;
      border-top: 1px solid #ccc;
      background-color: rgb(238, 237, 237);
      z-index: 300;

      &:hover {
        opacity: 0.9;
      }

      .user-info {
        display: flex;
        align-items: center;
        gap: 8px;
        justify-content: center;

        .details {
          display: flex;
          flex-direction: column;
          align-items: flex-start;

          p:first-child {
            text-transform: capitalize;
          }

          .gmail {
            font-size: 0.9em;
            color: #666;
          }
        }
      }
    }
  }

  .content {
    flex: 1;
    min-width: 0;
    min-height: 100vh;
    margin-left: 200px;
    min-width: calc(100% - 200px);
    overflow-x: hidden;
    box-sizing: border-box;
    transition:
      margin-left 0.3s ease-in-out,
      width 0.3s ease-in-out;
    will-change: margin-left, width;
  }

  &.sidebar-collapsed {
    .sidebar {
      width: 50px;
      min-width: 50px;

      .sidebar-toggle-button {
        transform: rotate(180deg);
      }

      .sidebar-dropdown__title,
      .sidebar-dropdown__icon {
        display: none;
      }

      .sidebar-dropdown__header {
        justify-content: center;
        padding: 8px 0;
      }

      .sidebar-dropdown__body {
        padding: 4px 0;
        border-left: none;

        ::v-deep(> *) {
          justify-content: center;
          padding: 4px 0;
        }
      }

      .sidebar-content a {
        display: flex;
        justify-content: center;
        padding: 8px 0;

        span {
          display: none;
        }
      }
    }

    .content {
      margin-left: 50px;
      width: calc(100% - 50px);
    }

    .sidebar-footer {
      display: none;
    }
  }

  /* Styles for RouterLinks */
  .sidebar-content a {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 8px;
    color: #333;
    text-decoration: none;
    transition:
      background-color 0.2s ease,
      transform 0.2s ease;

    &:hover {
      background-color: #e9ecef;
      transform: translateX(2px);
    }
  }

  .sidebar-content a.router-link-active {
    background-color: #007bff;

    &:hover {
      background-color: #0060cd;
      color: white !important;
    }
  }

  /* ✅ Single branch nav for non-admins */
  .single-branch-nav {
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding: 0 8px;
  }

  /* ✅ Ensure icons center when sidebar collapses (non-admin mode) */
  .sidebar-collapsed .single-branch-nav a {
    justify-content: center !important;
    padding: 10px 0 !important;
  }

  /* ✅ Hide branch dropdown header/chevron when collapsed (admin mode) */
  .sidebar-collapsed .branch-item__header {
    justify-content: center;
    padding: 8px 0;
  }

  .sidebar-collapsed .branch-item__name,
  .sidebar-collapsed .branch-item__chevron {
    display: none;
  }

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
    background-color: transparent;
  }

  .branches-list:hover {
    transform: translate(0px);
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
    /* font-weight: 600; */
    text-transform: capitalize;
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
    background-color: transparent;
  }
}

.n-dropdown {
  z-index: 9999 !important;
}

/* Base style for links inside branches */
.branch-link-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  border-radius: 8px;
  color: #333;
  text-decoration: none;
  transition: background-color 0.2s ease, transform 0.2s ease;
}

.branch-link-item:hover {
  background-color: #e9ecef;
  transform: translateX(2px);
}

/* ✅ OVERRIDE: Reset default router-link-active styles to prevent cross-branch highlighting */
.branch-link-item.router-link-active {
  background-color: transparent !important;
  color: #333 !important;
}

/* ✅ APPLY: Only highlight if it's the active branch AND the active route */
.branch-link-item.is-active-branch-link.router-link-active {
  background-color: #007bff !important;
  color: white !important;
}

.branch-link-item.is-active-branch-link.router-link-active:hover {
  background-color: #0060cd !important;
}

<style scoped>

/* Color Variables for easy customization */
:root {
  --sidebar-bg: #ffffff;
  --sidebar-width: 260px;
  --active-bg: #0959a9;
  /* Light slate */
  --active-text: #0f172a;
  /* Dark slate */
  --hover-bg: #f8fafc;
  --border-color: #e2e8f0;
}

#app {
  display: flex;
  min-height: 100vh;
  background-color: #fcfcfc;
}

/* Floating Button (Only shows when menu is hidden on small screens) */
.floating-menu-btn {
  position: fixed;
  top: 15px;
  left: 15px;
  z-index: 100;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  display: none;
  /* Hidden by default on desktop */
}

.sidebar {
  width: var(--sidebar-width);
  background: var(--sidebar-bg);
  display: flex;
  flex-direction: column;
  height: 100vh;
  border-right: 1px solid var(--border-color);
  position: fixed;
  top: 0;
  left: 0;
  z-index: 200;
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), width 0.3s ease;
}

.content {
  flex: 1;
  margin-left: var(--sidebar-width);
  transition: margin-left 0.3s ease;
  width: 100%;
}

/* --- RESPONSIVE LOGIC (< 1000px) --- */
@media (max-width: 1000px) {
  .floating-menu-btn {
    display: flex;
    /* Show floating button */
  }

  .sidebar {
    width: 280px;
    /* Fixed width for the overlay */
    box-shadow: 10px 0 30px rgba(0, 0, 0, 0.1);
  }

  /* When collapsed on mobile, slide it completely off-screen */
  #app.sidebar-collapsed .sidebar {
    transform: translateX(-100%);
  }

  /* Content takes full width, no margin */
  .content {
    margin-left: 0 !important;
  }
}

/* --- DESKTOP COLLAPSE LOGIC (> 1000px) --- */
@media (min-width: 1001px) {
  #app.sidebar-collapsed .sidebar {
    width: 60px;
  }

  #app.sidebar-collapsed .content {
    margin-left: 60px;
  }
}

/* --- NEUTRAL STYLING (No Green/Blue) --- */
.sidebar-content a {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  margin: 2px 8px;
  border-radius: 6px;
  color: #475569;
  text-decoration: none;
  transition: all 0.2s ease;
}

/* Professional Gray Hover */
.sidebar-content a:hover {
  background-color: var(--hover-bg);
  color: var(--active-text);
}

/* Active State: Neutral Bold */
.sidebar-content a.router-link-active,
.branch-link-item.is-active-branch-link.router-link-active {
  background-color: #2563EB !important;
  color: white !important;
  font-weight: 600;
}

.branch-item__header {
  margin: 2px 8px;
  background: transparent;
  border: 1px solid transparent;
}

.branch-item__header--active {
  background: var(--active-bg);
  border-color: var(--border-color);
}

.sidebar-footer {
  border-top: 1px solid var(--border-color);
  background: #fff;
  padding: 12px;
}

/* Update your CSS to this */
:deep(.sidebar-collapsed .sidebar-content span),
:deep(.sidebar-collapsed .details),
:deep(.sidebar-collapsed .branch-item__name),
:deep(.sidebar-collapsed .branch-item__chevron) {
  display: none !important;
  opacity: 0 !important;
  width: 0 !important;
  pointer-events: none;
}
</style>
