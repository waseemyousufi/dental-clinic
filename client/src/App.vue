<script setup lang="ts">
import { ref, provide, h, computed, onMounted, watch } from 'vue'
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  NConfigProvider,
  NMessageProvider,
  NDialogProvider,
  NButton,
  NIcon,
  unstableDialogRtl,
  unstableMessageRtl,
  unstablePopoverRtl,
  unstableSelectRtl,
  unstableTreeSelectRtl,
} from 'naive-ui'
import { Icon } from '@iconify/vue'
import userApi from './api/user'
import { useAuthStore } from './stores/authStore'
import { useBranchStore } from './stores/branchStore'
import useUserStore from './stores/user'

type AppLocale = 'en' | 'dr' | 'ps'
type AppSettings = {
  rec_show_kpi?: boolean
}

const LOCALE_STORAGE_KEY = 'app-locale'
const RTL_LOCALES = new Set<AppLocale>(['dr', 'ps'])

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const branchStore = useBranchStore()
const userStore = useUserStore()
const { t, locale } = useI18n({ useScope: 'global' })

const storedUser = localStorage.getItem('user')
const parsedUser = storedUser ? JSON.parse(storedUser) : null
const token = parsedUser?.token
const user = parsedUser?.user || null

const expandedBranchIds = ref<number[]>([])
const canGoBack = ref(false)
const isSidebarCollapsed = ref(false)
const showUserMenu = ref(false)
const showLanguageMenu = ref(false)

const isRtl = computed(() => RTL_LOCALES.has(locale.value as AppLocale))
const naiveRtl = computed(() =>
  isRtl.value
    ? [
      unstableSelectRtl,
      unstableTreeSelectRtl,
      unstablePopoverRtl,
      unstableDialogRtl,
      unstableMessageRtl,
    ]
    : undefined,
)

function normalizeLocale(value: string | null | undefined): AppLocale {
  if (value === 'en' || value === 'dr' || value === 'ps') return value
  if (value === 'fa' || value === 'fa-AF') return 'dr'
  return 'en'
}

function applyLocaleSideEffects(value: AppLocale) {
  if (typeof document === 'undefined') return
  const dir = RTL_LOCALES.has(value) ? 'rtl' : 'ltr'
  document.documentElement.lang = value
  document.documentElement.dir = dir
  document.body.dir = dir
  document.body.setAttribute('dir', dir)
}

const initialLocale = normalizeLocale(localStorage.getItem(LOCALE_STORAGE_KEY))

if (locale.value !== initialLocale) {
  locale.value = initialLocale
}
applyLocaleSideEffects(initialLocale)

watch(
  () => locale.value,
  (value) => {
    const normalized = normalizeLocale(value as string)
    if (normalized !== value) {
      locale.value = normalized
      return
    }
    localStorage.setItem(LOCALE_STORAGE_KEY, normalized)
    applyLocaleSideEffects(normalized)
  },
  { immediate: true },
)

const goBack = () => {
  if (typeof window !== 'undefined' && window.history.length > 1) {
    router.back()
  } else {
    router.push('/patients')
  }
}

const userBranchId = computed(() => {
  const rawId = user?.employee?.branchId ?? user?.employee?.branch_id
  const parsed = typeof rawId === 'number' ? rawId : typeof rawId === 'string' ? Number(rawId) : NaN
  return Number.isFinite(parsed) ? parsed : null
})

const isAdmin = computed(() => {
  return user?.employee?.position === 'admin'
})

const userSettings = computed(() => (userStore.settings ?? {}) as AppSettings)

const navLinks = [
  userStore.isAdmin && {
    path: '/dashboard',
    labelKey: 'app.navigation.dashboard',
    icon: () => h(Icon, { icon: 'mdi:view-dashboard-variant', style: 'font-size: 1.45em;' }),
  },
  {
    path: '/appointments',
    labelKey: 'app.navigation.appointments',
    icon: () => h(Icon, { icon: 'mdi:calendar-clock', style: 'font-size: 1.45em;' }),
  },
  {
    path: '/clinic-assets',
    labelKey: 'app.navigation.clinicAssets',
    icon: () => h(Icon, { icon: 'mdi:package-variant-closed', style: 'font-size: 1.45em;' }),
  },
  {
    path: '/treatments',
    labelKey: 'app.navigation.treatments',
    icon: () => h(Icon, { icon: 'fluent:patient-24-filled', style: 'font-size: 1.45em;' }),
  },
].filter(Boolean)

if (userStore.isReceptionist || userStore.isAdmin) {
  navLinks.push(
    {
      path: '/patients',
      labelKey: 'app.navigation.patients',
      icon: () => h(Icon, { icon: 'mdi:account-multiple', style: 'font-size: 1.45em;' }),
    },
    {
      path: '/expenses',
      labelKey: 'app.navigation.expenses',
      icon: () => h(Icon, { icon: 'arcticons:expense-register', style: 'font-size: 1.45em;' }),
    },
    {
      path: '/accounts',
      labelKey: 'app.navigation.accounts',
      icon: () => h(Icon, { icon: 'mdi:wallet-outline', style: 'font-size: 1.45em;' }),
    },
  )
}

if (userStore.isAdmin) {
  navLinks.push(
    {
      path: '/employees',
      labelKey: 'app.navigation.employees',
      icon: () => h(Icon, { icon: 'mdi:badge-account-outline', style: 'font-size: 1.45em;' }),
    },
    {
      path: '/settings',
      labelKey: 'app.navigation.settings',
      icon: () => h(Icon, { icon: 'mdi:gear', style: 'font-size: 1.45em;' }),
    },
    {
      path: '/reports',
      labelKey: 'app.navigation.reports',
      icon: () => h(Icon, { icon: 'mdi:file-chart', style: 'font-size: 1.45em;' }),
    },
  )
}

if (!userStore.isAdmin && userSettings.value.rec_show_kpi) {
  navLinks.push({
    path: '/dashboard',
    labelKey: 'app.navigation.overview',
    icon: () => h(Icon, { icon: 'mdi:view-dashboard-variant', style: 'font-size: 1.45em;' }),
  })
}


const languageOptions = [
  { label: 'English', key: 'en' },
  { label: 'دری', key: 'dr' },
  { label: 'پښتو', key: 'ps' },
]

const currentLanguageLabel = computed(() => {
  if (locale.value === 'dr') return 'دری'
  if (locale.value === 'ps') return 'پښتو'
  return 'English'
})

const backButtonIcon = computed(() => (isRtl.value ? 'mdi:arrow-right' : 'mdi:arrow-left'))
const closedBranchIcon = computed(() => (isRtl.value ? 'mdi:chevron-left' : 'mdi:chevron-right'))

const toggleLanguageMenu = () => {
  showLanguageMenu.value = !showLanguageMenu.value
}

const switchLanguage = (lang: string | number) => {
  const next = normalizeLocale(String(lang))
  showLanguageMenu.value = false
  locale.value = next
  localStorage.setItem(LOCALE_STORAGE_KEY, next)
  applyLocaleSideEffects(next)
}

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
  if (isBranchExpanded(branchId)) {
    expandedBranchIds.value = []
    return
  }

  expandedBranchIds.value = [branchId]
  selectBranch(branchId)
}

const isBranchExpanded = (branchId: number) => {
  return expandedBranchIds.value.includes(branchId)
}

const userMenuOptions = computed(() => [
  {
    label: t('app.userMenu.logout'),
    key: 'logout',
    icon: () =>
      h(NIcon, null, {
        default: () => h(Icon, { icon: 'material-symbols:logout-rounded' }),
      }),
  },
])

const handleUserSelect = (key: string | number) => {
  showUserMenu.value = false
  if (key === 'profile') router.push('/profile')
  if (key === 'settings') router.push('/settings')
  if (key === 'logout') {
    userApi
      .logout()
      .then(() => {
        authStore.logout()
        router.push('/login')
      })
      .catch(console.log)
  }
}

onMounted(async () => {
  canGoBack.value = typeof window !== 'undefined' && window.history.length > 1

  console.log('User branch ID:', userBranchId.value)

  if (!authStore.isLoggedIn) return

  if (isAdmin.value) {
    await branchStore.fetchBranches()
    const raw = route.query.branchId
    const asNumber = typeof raw === 'string' ? Number(raw) : NaN
    if (Number.isFinite(asNumber)) branchStore.setSelectedBranchId(asNumber)
  }
  else if (userBranchId.value != null) {
    console.log('user is not admin looks like!')
    router.replace({ query: { ...route.query, branchId: String(userBranchId.value) } })
  }

  if (token) {
    const settings = await userApi.getSettings()
    localStorage.setItem('settings', JSON.stringify(settings.data.data))
  }
})

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

watch(
  () => (isAdmin.value ? branchStore.selectedBranchId : null),
  (branchId) => {
    if (!authStore.isLoggedIn || !isAdmin.value) return
    const current = route.query.branchId
    const currentAsNumber = typeof current === 'string' ? Number(current) : NaN
    if (branchId != null && Number.isFinite(currentAsNumber) && currentAsNumber === branchId) return
    router.replace({ query: { ...route.query, branchId: String(branchId) } })
  },
)

provide('isSidebarCollapsed', isSidebarCollapsed)
provide('toggleSidebar', toggleSidebar)
provide(
  'selectedBranchId',
  computed(() => (isAdmin.value ? branchStore.selectedBranchId : userBranchId.value)),
)
</script>

<template>
  <n-config-provider abstract :rtl="naiveRtl">
    <n-dialog-provider>
      <n-message-provider>
        <div id="app" :dir="isRtl ? 'rtl' : 'ltr'" :class="{
          'sidebar-collapsed': isSidebarCollapsed,
          'has-sidebar': authStore.isLoggedIn,
          'is-rtl': isRtl,
          'is-ltr': !isRtl,
        }">
          <n-button v-if="isSidebarCollapsed && authStore.isLoggedIn" circle class="floating-menu-btn"
            @click="toggleSidebar">
            <Icon icon="mdi:menu" style="font-size: 1.8em" />
          </n-button>

          <div class="sidebar" v-if="authStore.isLoggedIn">
            <div class="sidebar-header">
              <div class="language-menu-wrapper" @keydown.escape="showLanguageMenu = false">
                <n-button text class="lang-switch-button" @click="toggleLanguageMenu">
                  <Icon icon="mdi:translate" style="font-size: 1.35em" />
                  <span>{{ currentLanguageLabel }}</span>
                  <Icon :icon="showLanguageMenu ? 'mdi:chevron-up' : 'mdi:chevron-down'"
                    class="lang-switch-button__chevron" />
                </n-button>

                <div v-if="showLanguageMenu" class="language-menu" role="menu">
                  <button v-for="option in languageOptions" :key="option.key" type="button" class="language-menu__item"
                    :class="{ 'language-menu__item--active': locale === option.key }" role="menuitem"
                    @click="switchLanguage(option.key)">
                    <span>{{ option.label }}</span>
                    <Icon v-if="locale === option.key" icon="mdi:check" />
                  </button>
                </div>
              </div>

              <n-button text @click="toggleSidebar" class="sidebar-toggle-button">
                <Icon icon="mdi:menu-open" style="font-size: 2em" />
              </n-button>
            </div>

            <div class="sidebar-content" v-show="!isSidebarCollapsed">
              <div v-if="isAdmin" class="branches-panel">
                <div v-if="branchStore.loading" class="branches-loading">
                  {{ t('app.branches.loading') }}
                </div>
                <div v-else-if="branchStore.branches.length === 0" class="branches-empty">
                  {{ t('app.branches.empty') }}
                </div>

                <div v-else class="branches-list">
                  <div v-for="b in branchStore.branches" :key="b.id" class="branch-item">
                    <button type="button" class="branch-item__header" :class="{
                      'branch-item__header--active': branchStore.selectedBranchId === b.id,
                    }" @click.stop="toggleBranchExpansion(b.id)">
                      <Icon icon="proicons:branch" class="branch-item__branch-icon" />
                      <span class="branch-item__name">{{ b.branchName }}</span>
                      <Icon :icon="isBranchExpanded(b.id) ? 'mdi:chevron-down' : closedBranchIcon"
                        class="branch-item__chevron" />
                    </button>

                    <div v-show="isBranchExpanded(b.id)" class="branch-item__links">
                      <RouterLink v-for="link in navLinks" :key="link.path"
                        :to="{ path: link.path, query: { ...route.query, branchId: String(b.id) } }"
                        @click="branchStore.setSelectedBranchId(b.id)" :class="[
                          'branch-link-item',
                          { 'is-active-branch-link': branchStore.selectedBranchId === b.id },
                        ]">
                        <component :is="link.icon" />
                        <span>{{ t(link.labelKey) }}</span>
                      </RouterLink>
                    </div>
                  </div>
                </div>
              </div>

              <div v-else-if="userBranchId" class="single-branch-nav">
                <RouterLink v-for="link in navLinks" :key="link.path"
                  :to="{ path: link.path, query: { ...route.query, branchId: String(userBranchId) } }">
                  <component :is="link.icon" />
                  <span>{{ t(link.labelKey) }}</span>
                </RouterLink>
              </div>

              <hr class="sidebar-divider" />

              <div class="sidebar-footer">
                <div class="user-menu-wrapper" @keydown.escape="showUserMenu = false">
                  <button type="button" class="user-info user-info-button" @click="toggleUserMenu">
                    <Icon icon="gg:profile" style="font-size: 1.9em" />
                    <div class="details">
                      <p>
                        {{
                          user ? `${user.employee.fName} ${user.employee.lName}` : t('app.user.guest')
                        }}
                      </p>
                      <p class="gmail">{{ user ? user.email : t('app.user.guestEmail') }}</p>
                    </div>
                    <Icon :icon="showUserMenu ? 'mdi:chevron-up' : 'mdi:chevron-down'"
                      class="user-info-button__chevron" />
                  </button>

                  <div v-if="showUserMenu" class="user-menu" role="menu">
                    <button v-for="option in userMenuOptions" :key="option.key" type="button" class="user-menu__item"
                      role="menuitem" @click="handleUserSelect(option.key)">
                      <span class="user-menu__item-icon">
                        <component :is="option.icon" />
                      </span>
                      <span>{{ option.label }}</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="content">
            <RouterView :key="$route.fullPath" />
          </div>

          <!-- <n-button v-if="authStore.isLoggedIn" circle size="large" class="floating-back-btn" :disabled="!canGoBack"
            @click="goBack">
            <Icon :icon="backButtonIcon" style="font-size: 1.5em" />
          </n-button> -->
        </div>
      </n-message-provider>
    </n-dialog-provider>
  </n-config-provider>
</template>

<style scoped>
#app {
  --sidebar-width: 232px;
  --sidebar-collapsed-width: 64px;
  --sidebar-bg: #ffffff;
  --sidebar-border: rgba(15, 23, 42, 0.1);
  --sidebar-text: #172033;
  --sidebar-muted: #64748b;
  --sidebar-hover: #f1f5f9;
  --sidebar-active: #0967d2;
  --sidebar-active-soft: rgba(9, 103, 210, 0.1);
  display: flex;
  overflow-x: hidden;
  min-height: 100vh;
  background:
    radial-gradient(circle at top left, rgba(14, 165, 233, 0.08), transparent 32rem),
    linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
  color: #0f172a;
}

#app.is-ltr.has-sidebar .content {
  margin-left: var(--sidebar-width);
  margin-right: 0;
  width: calc(100% - var(--sidebar-width));
}

#app.is-rtl.has-sidebar .content {
  margin-right: var(--sidebar-width);
  margin-left: 0;
  width: calc(100% - var(--sidebar-width));
}

#app.sidebar-collapsed.is-ltr.has-sidebar .content {
  margin-left: var(--sidebar-collapsed-width);
  margin-right: 0;
  width: calc(100% - var(--sidebar-collapsed-width));
}

#app.sidebar-collapsed.is-rtl.has-sidebar .content {
  margin-right: var(--sidebar-collapsed-width);
  margin-left: 0;
  width: calc(100% - var(--sidebar-collapsed-width));
}

.sidebar {
  width: var(--sidebar-width);
  background: var(--sidebar-bg);
  display: flex;
  flex-direction: column;
  height: 100vh;
  box-sizing: border-box;
  position: fixed;
  top: 0;
  z-index: 900;
  transition:
    width 0.26s ease,
    transform 0.26s ease,
    box-shadow 0.26s ease;
  will-change: width, transform;
  border: 1px solid var(--sidebar-border);
  box-shadow: 0 20px 48px rgba(15, 23, 42, 0.08);
  overflow: visible;
}

#app.sidebar-collapsed .sidebar {
  width: var(--sidebar-collapsed-width);
}

#app.is-ltr .sidebar {
  left: 0;
  right: auto;
  border-right: 1px solid var(--sidebar-border);
  border-left: none;
}

#app.is-rtl .sidebar {
  right: 0;
  left: auto;
  border-left: 1px solid var(--sidebar-border);
  border-right: none;
}

.sidebar-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  min-height: 64px;
  padding: 10px;
  flex-shrink: 0;
  gap: 8px;
  overflow: visible;
  border-bottom: 1px solid rgba(148, 163, 184, 0.22);
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

#app.sidebar-collapsed .sidebar-header {
  justify-content: center;
  padding: 10px 6px;
}

#app.sidebar-collapsed .language-menu-wrapper {
  display: none;
}

.sidebar-toggle-button {
  width: 40px;
  height: 40px;
  color: var(--sidebar-text);
  border-radius: 10px;
  transition:
    background-color 0.18s ease,
    transform 0.22s ease;
}

.sidebar-toggle-button:hover {
  background: var(--sidebar-hover);
}

#app.is-rtl .sidebar-toggle-button {
  transform: scaleX(-1);
}

.sidebar-content {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  overflow-x: hidden;
  -webkit-overflow-scrolling: touch;
  padding: 10px 0 88px;
}

.sidebar-content::-webkit-scrollbar {
  display: none;
  appearance: none;
}

.sidebar-footer {
  padding: 10px;
  display: flex;
  position: absolute;
  left: 0;
  bottom: 0;
  width: 100%;
  box-sizing: border-box;
  color: var(--sidebar-text);
  cursor: pointer;
  border-top: 1px solid rgba(148, 163, 184, 0.24);
  background: rgba(248, 250, 252, 0.96);
  z-index: 3;
  backdrop-filter: blur(10px);
}

.sidebar-footer:hover {
  background: #f1f5f9;
}

.user-menu-wrapper {
  position: relative;
  width: 100%;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 8px;
  justify-content: flex-start;
  width: 100%;
  min-width: 0;
}

.user-info-button {
  width: 100%;
  border: 0;
  background: transparent;
  padding: 0;
  text-align: start;
  color: inherit;
  cursor: pointer;
}

.user-info-button__chevron {
  flex: 0 0 auto;
  font-size: 1.1rem;
  opacity: 0.7;
}

.details {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  min-width: 0;
}

.details p:first-child {
  text-transform: capitalize;
  margin: 0;
  color: var(--sidebar-text);
  font-size: 0.9rem;
  font-weight: 700;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 160px;
}

.gmail {
  font-size: 0.76rem;
  color: var(--sidebar-muted);
  margin: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 160px;
}

.user-menu {
  position: absolute;
  bottom: calc(100% + 8px);
  left: 0;
  z-index: 10030;
  width: min(220px, calc(100vw - 28px));
  padding: 6px;
  border: 1px solid rgba(148, 163, 184, 0.22);
  border-radius: 12px;
  background: #ffffff;
  box-shadow: 0 18px 42px rgba(15, 23, 42, 0.2);
}

#app.is-rtl .user-menu {
  right: 0;
  left: auto;
}

.user-menu__item {
  width: 100%;
  min-height: 38px;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 10px;
  border: 0;
  border-radius: 9px;
  background: transparent;
  color: var(--sidebar-text);
  cursor: pointer;
  font: inherit;
  text-align: start;
}

.user-menu__item:hover {
  background: var(--sidebar-hover);
}

.user-menu__item-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 auto;
}

.content {
  flex: 1;
  min-width: 0;
  min-height: 100vh;
  overflow-x: hidden;
  box-sizing: border-box;
  transition:
    margin-left 0.26s ease,
    margin-right 0.26s ease,
    width 0.26s ease;
  will-change: margin-left, margin-right, width;
  width: 100%;
}

.sidebar-content a {
  display: flex;
  align-items: center;
  gap: 8px;
  min-height: 38px;
  padding: 8px 10px;
  border-radius: 10px;
  color: #344256;
  text-decoration: none;
  transition:
    background-color 0.18s ease,
    transform 0.18s ease,
    color 0.18s ease;
  margin: 2px 8px;
  background: transparent;
}

.sidebar-content a:hover {
  background-color: var(--sidebar-hover);
  color: #0f172a;
  transform: translateX(var(--hover-shift, 2px));
}

#app.is-rtl .sidebar-content a:hover {
  --hover-shift: -2px;
}

.sidebar-content a.router-link-active {
  background-color: var(--sidebar-active);
  color: white !important;
  box-shadow: 0 8px 18px rgba(9, 103, 210, 0.2);
}

.sidebar-content a.router-link-active:hover {
  background-color: #075bb8;
  color: white !important;
}

.single-branch-nav {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 0 8px;
}

.sidebar-divider {
  opacity: 0.3;
  margin: 8px 20px;
  border: none;
  border-top: 1px solid rgba(148, 163, 184, 0.45);
}

.branches-panel {
  padding: 2px 0;
}

.branches-loading,
.branches-empty {
  margin: 8px;
  padding: 10px 12px;
  border-radius: 10px;
  background: #f8fafc;
  color: var(--sidebar-muted);
  font-size: 0.85rem;
  text-align: start;
}

.branches-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 0 8px;
  background-color: transparent;
}

.branch-item__header {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: 8px;
  min-height: 42px;
  padding: 8px 10px;
  margin: 0;
  border-radius: 10px;
  border: 1px solid transparent;
  background: transparent;
  cursor: pointer;
  color: var(--sidebar-text);
  text-align: start;
  transition:
    background-color 0.16s ease,
    transform 0.12s ease,
    border-color 0.16s ease;
}

.branch-item__header:hover {
  background: var(--sidebar-hover);
  transform: translateX(var(--hover-shift, 2px));
}

#app.is-rtl .branch-item__header:hover {
  --hover-shift: -2px;
}

.branch-item__header--active {
  background: var(--sidebar-active-soft);
  border-color: rgba(9, 103, 210, 0.2);
  color: #075bb8;
}

.branch-item__branch-icon {
  flex: 0 0 auto;
  color: #0f766e;
  font-size: 1.2em;
}

.branch-item__name {
  flex: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  text-transform: capitalize;
  text-align: start;
  font-size: 0.93rem;
  font-weight: 700;
}

.branch-item__chevron {
  flex: 0 0 auto;
  opacity: 0.8;
}

.branch-item__links {
  margin-top: 4px;
  margin-inline-start: 16px;
  padding-inline-start: 10px;
  border-inline-start: 1px solid rgba(15, 23, 42, 0.12);
  display: flex;
  flex-direction: column;
  background-color: transparent;
}

.branch-link-item {
  background: transparent;
}

.branch-link-item:hover {
  background-color: var(--sidebar-hover);
  transform: translateX(var(--hover-shift, 2px));
}

.branch-link-item.router-link-active {
  background-color: transparent !important;
  color: #344256 !important;
  box-shadow: none;
}

.branch-link-item.is-active-branch-link.router-link-active {
  background-color: var(--sidebar-active) !important;
  color: white !important;
  box-shadow: 0 8px 18px rgba(9, 103, 210, 0.2);
}

.branch-link-item.is-active-branch-link.router-link-active:hover {
  background-color: #075bb8 !important;
}

:global(.n-dropdown) {
  z-index: 9999 !important;
}

:global(.n-popover),
:global(.n-tooltip),
:global(.n-select-menu),
:global(.n-tree-select-menu),
:global(.n-dropdown-menu),
:global(.n-date-panel),
:global(.n-time-picker-panel) {
  direction: inherit;
}

:global([dir='rtl'] .n-select-menu),
:global([dir='rtl'] .n-tree-select-menu),
:global([dir='rtl'] .n-dropdown-menu),
:global([dir='rtl'] .n-popover),
:global([dir='rtl'] .n-tooltip) {
  text-align: right;
}

:global([dir='rtl'] .n-base-select-menu__option),
:global([dir='rtl'] .n-dropdown-menu .n-dropdown-menu-option),
:global([dir='rtl'] .n-tooltip__content),
:global([dir='rtl'] .n-popover__content) {
  direction: rtl;
}

.floating-menu-btn {
  position: fixed;
  top: 14px;
  z-index: 850;
  box-shadow: 0 10px 26px rgba(15, 23, 42, 0.16);
  display: none;
  background: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(8px);
}

#app.is-ltr .floating-menu-btn {
  left: 14px;
  right: auto;
}

#app.is-rtl .floating-menu-btn {
  right: 14px;
  left: auto;
}

.floating-back-btn {
  position: fixed;
  bottom: 16px;
  z-index: 820;
  width: 54px;
  height: 54px;
  box-shadow: 0 16px 34px rgba(15, 23, 42, 0.2);
  backdrop-filter: blur(7px);
  background-color: rgba(255, 255, 255, 0.86);
  border: 1px solid rgba(148, 163, 184, 0.2);
  transition:
    transform 0.18s ease,
    box-shadow 0.18s ease;
}

#app.is-ltr .floating-back-btn {
  right: 20px;
  left: auto;
}

#app.is-rtl .floating-back-btn {
  left: 20px;
  right: auto;
}

.floating-back-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 20px 38px rgba(15, 23, 42, 0.24);
}

.language-menu-wrapper {
  position: relative;
  min-width: 0;
  flex: 1;
  z-index: 10020;
}

.lang-switch-button {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  color: var(--sidebar-text);
  white-space: nowrap;
  flex-shrink: 0;
  max-width: 100%;
  justify-content: flex-start;
  padding-inline: 10px;
  border-radius: 10px;
}

.lang-switch-button:hover {
  background: var(--sidebar-hover);
}

.lang-switch-button span {
  overflow: hidden;
  text-overflow: ellipsis;
}

.lang-switch-button__chevron {
  flex: 0 0 auto;
  font-size: 1.05em;
  opacity: 0.7;
}

.language-menu {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  z-index: 10030;
  width: min(172px, calc(100vw - 28px));
  padding: 6px;
  border: 1px solid rgba(148, 163, 184, 0.22);
  border-radius: 12px;
  background: #ffffff;
  box-shadow: 0 18px 42px rgba(15, 23, 42, 0.2);
}

#app.is-rtl .language-menu {
  right: 0;
  left: auto;
}

.language-menu__item {
  width: 100%;
  min-height: 38px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 8px 10px;
  border: 0;
  border-radius: 9px;
  background: transparent;
  color: var(--sidebar-text);
  cursor: pointer;
  font: inherit;
  text-align: start;
}

.language-menu__item:hover {
  background: var(--sidebar-hover);
}

.language-menu__item--active {
  background: var(--sidebar-active-soft);
  color: #075bb8;
  font-weight: 700;
}

@media (max-width: 1000px) {
  #app {
    --sidebar-width: min(84vw, 304px);
  }

  .floating-menu-btn {
    display: flex;
  }

  .sidebar {
    width: var(--sidebar-width);
    box-shadow: 0 24px 56px rgba(15, 23, 42, 0.2);
  }

  #app.is-ltr .sidebar {
    left: 0;
    right: auto;
    border-right: 1px solid var(--sidebar-border);
    border-left: none;
  }

  #app.is-rtl .sidebar {
    right: 0;
    left: auto;
    border-left: 1px solid var(--sidebar-border);
    border-right: none;
  }

  #app.sidebar-collapsed.is-ltr .sidebar {
    transform: translateX(-100%);
  }

  #app.sidebar-collapsed.is-rtl .sidebar {
    transform: translateX(100%);
  }

  #app.has-sidebar .content,
  #app.sidebar-collapsed.has-sidebar .content {
    margin-left: 0 !important;
    margin-right: 0 !important;
    width: 100% !important;
  }

  #app.sidebar-collapsed .sidebar {
    width: var(--sidebar-width);
  }
}

@media (max-width: 768px) {
  #app.is-ltr .floating-back-btn {
    right: 12px;
    left: auto;
  }

  #app.is-rtl .floating-back-btn {
    left: 12px;
    right: auto;
  }

  .floating-back-btn {
    bottom: 12px;
    width: 48px;
    height: 48px;
  }

  .sidebar-header {
    min-height: 58px;
  }

  .branches-list {
    gap: 4px;
  }
}
</style>
