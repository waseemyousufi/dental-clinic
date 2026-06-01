<template>
  <div class="appointment-board">
    <n-card class="shell" :bordered="false" size="large">
      <div class="hero">
        <div class="hero__copy">
          <div class="eyebrow">
            <n-icon size="18" class="eyebrow__icon">
              <Icon icon="mdi:calendar-star" />
            </n-icon>
            <span>{{ t('appointmentsList.eyebrow') }}</span>
          </div>
          <h2 class="title">{{ t('appointmentsList.title') }}</h2>
          <p class="subtitle" v-if="userStore.isReceptionist">
            {{ t('appointmentsList.subtitle') }}
          </p>
        </div>

        <div class="hero__stats">
          <n-statistic :label="t('appointmentsList.stats.total')" :value="filteredAppointments.length" />
          <n-statistic :label="t('appointmentsList.stats.today')" :value="todayCount" />
          <n-statistic :label="t('appointmentsList.stats.upcoming')" :value="upcomingCount" />
        </div>
      </div>

      <n-space vertical size="large" class="toolbar">
        <div class="toolbar__row">
          <n-input
            v-model:value="query"
            clearable
            round
            size="large"
            :placeholder="t('appointmentsList.searchPlaceholder')"
          >
            <template #prefix>
              <n-icon size="18"><Icon icon="mdi:magnify" /></n-icon>
            </template>
          </n-input>

          <n-select
            :to="false"
            v-model:value="statusFilter"
            :options="statusOptions"
            clearable
            :placeholder="t('appointmentsList.statusFilterPlaceholder')"
            size="large"
            class="status-select"
          />
        </div>
      </n-space>

      <n-empty v-if="!filteredAppointments.length" :description="t('appointmentsList.emptyDescription')">
        <template #icon>
          <n-icon size="44" class="empty-icon">
            <Icon icon="mdi:calendar-remove" />
          </n-icon>
        </template>
      </n-empty>

      <div v-else>
        <div class="mobile-view">
          <transition-group name="fade-slide" tag="div" class="cards">
            <n-card
              v-for="apt in filteredAppointments"
              :key="getKey(apt)"
              class="apt-card"
              size="large"
              :bordered="false"
            >
              <div class="card-top">
                <div class="avatar">
                  <n-icon size="22">
                    <Icon icon="mdi:account-heart" />
                  </n-icon>
                </div>
                <div class="card-title-block">
                  <div class="card-title">
                    <router-link
                      v-if="shouldLinkPatient(apt)"
                      class="patient-link"
                      :to="getPatientLink(apt)"
                    >
                      {{ getPatientName(apt) }}
                    </router-link>
                    <span v-else>
                      {{ getPatientName(apt) }}
                    </span>
                  </div>
                  <div class="card-subtitle">
                    {{ t('appointmentsList.cardSubtitle', { name: getEmployeeName(apt) }) }}
                  </div>
                </div>
                <n-tag :type="getStatusType(apt)" round size="small">
                  {{ getStatusLabel(apt) }}
                </n-tag>
              </div>

              <div class="meta-grid">
                <div class="meta-item">
                  <n-icon size="16"><Icon icon="mdi:calendar-month" /></n-icon>
                  <span>{{ formatDateTime(apt) }}</span>
                </div>
                <div class="meta-item" v-if="getService(apt)">
                  <n-icon size="16"><Icon icon="mdi:medical-bag" /></n-icon>
                  <span>{{ getService(apt) }}</span>
                </div>
                <div class="meta-item" v-if="getLocation(apt)">
                  <n-icon size="16"><Icon icon="mdi:map-marker-radius" /></n-icon>
                  <span>{{ getLocation(apt) }}</span>
                </div>
              </div>

              <div v-if="getNotes(apt)" class="notes">
                {{ getNotes(apt) }}
              </div>

              <div class="card-footer">
                <span class="id-pill">#{{ getKey(apt) }}</span>
                <n-button quaternary circle type="primary" size="small">
                  <template #icon>
                    <n-icon><Icon icon="mdi:chevron-right" /></n-icon>
                  </template>
                </n-button>
              </div>
            </n-card>
          </transition-group>
        </div>

        <div class="desktop-view">
          <n-card
            v-for="apt in filteredAppointments"
            :key="getKey(apt)"
            class="row-card"
            :bordered="false"
            size="large"
          >
            <div class="row-layout">
              <div class="row-main">
                <div class="row-title-line">
                  <div>
                    <div class="row-title">{{ getPatientName(apt) }}</div>
                    <div class="row-subtitle">{{ getEmployeeName(apt) }}</div>
                  </div>
                  <n-tag :type="getStatusType(apt)" round>
                    {{ getStatusLabel(apt) }}
                  </n-tag>
                </div>

                <div class="row-meta">
                  <span>
                    <n-icon size="16"><Icon icon="mdi:clock-outline" /></n-icon>
                    {{ formatDateTime(apt) }}
                  </span>
                  <span v-if="getService(apt)">
                    <n-icon size="16"><Icon icon="mdi:medical-bag" /></n-icon>
                    {{ getService(apt) }}
                  </span>
                  <span v-if="getLocation(apt)">
                    <n-icon size="16"><Icon icon="mdi:map-marker-outline" /></n-icon>
                    {{ getLocation(apt) }}
                  </span>
                </div>

                <div v-if="getNotes(apt)" class="row-notes">
                  {{ getNotes(apt) }}
                </div>
              </div>

              <div class="row-side">
                <div class="side-block">
                  <span class="side-label">{{ t('appointmentsList.patientLabel') }}</span>
                  <span class="side-value">
                    <router-link
                      v-if="shouldLinkPatient(apt)"
                      class="patient-link"
                      :to="getPatientLink(apt)"
                    >
                      {{ getPatientName(apt) }}
                    </router-link>
                    <span v-else>
                      {{ getPatientName(apt) }}
                    </span>
                  </span>
                </div>
                <div class="side-block">
                  <span class="side-label">{{ t('appointmentsList.doctorLabel') }}</span>
                  <span class="side-value">{{ getEmployeeName(apt) }}</span>
                </div>
              </div>
            </div>
          </n-card>
        </div>
      </div>
    </n-card>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { NBadge, NButton, NCard, NEmpty, NIcon, NInput, NSelect, NSpace, NStatistic, NTag } from 'naive-ui'
import { Icon } from '@iconify/vue'
import { useI18n } from 'vue-i18n'
import useUserStore from '@/stores/user'
import { useBranchStore } from '@/stores/branchStore'

export type AppointmentStatus =
  | 'pending'
  | 'confirmed'
  | 'completed'
  | 'cancelled'
  | 'rescheduled'
  | 'no-show'
  | string

export type AppointmentData = Record<string, any> & {
  id?: string | number
  appointmentId?: string | number
  patientName?: string
  employeeName?: string
  patient?: string
  employee?: string
  serviceName?: string
  service?: string
  status?: AppointmentStatus
  startAt?: string | Date
  date?: string | Date
  time?: string
  scheduledAt?: string | Date
  branchName?: string
  location?: string
  notes?: string
}

const props = defineProps<{
  appointments: AppointmentData[]
}>()

const { t } = useI18n()
const query = ref('')
const statusFilter = ref<string | null>(null)
const userStore = useUserStore()
const branchStore = useBranchStore()

const statusOptions = computed(() => [
  { label: t('appointmentsList.statusLabels.pending'), value: 'pending' },
  { label: t('appointmentsList.statusLabels.confirmed'), value: 'confirmed' },
  { label: t('appointmentsList.statusLabels.completed'), value: 'completed' },
  { label: t('appointmentsList.statusLabels.cancelled'), value: 'cancelled' },
  { label: t('appointmentsList.statusLabels.rescheduled'), value: 'rescheduled' },
  { label: t('appointmentsList.statusLabels.no-show'), value: 'no-show' }
])

const normalized = computed(() =>
  (props.appointments ?? []).map((apt) => ({
    raw: apt,
    patientName: getPatientName(apt),
    employeeName: getEmployeeName(apt),
    service: getService(apt),
    location: getLocation(apt),
    status: getStatusValue(apt),
    searchBlob: [
      getPatientName(apt),
      getEmployeeName(apt),
      getService(apt),
      getLocation(apt),
      getNotes(apt),
      getStatusLabel(apt),
      formatDateTime(apt)
    ]
      .filter(Boolean)
      .join(' ')
      .toLowerCase()
  }))
)

const filteredAppointments = computed(() => {
  const q = query.value.trim().toLowerCase()
  return normalized.value
    .filter((item) => !q || item.searchBlob.includes(q))
    .filter((item) => !statusFilter.value || item.status === statusFilter.value)
    .map((item) => item.raw)
})

const todayCount = computed(() =>
  normalized.value.filter((item) => isSameDay(getAppointmentDate(item.raw), new Date())).length
)

const upcomingCount = computed(() =>
  normalized.value.filter((item) => {
    const date = getAppointmentDate(item.raw)
    return !!date && date.getTime() > new Date().getTime()
  }).length
)

function getKey(apt: AppointmentData): string {
  return String(apt.id ?? apt.appointmentId ?? `${getPatientName(apt)}-${formatDateTime(apt)}`)
}

function getPatientName(apt: AppointmentData): string {
  return apt.patientName || apt.patient || apt.customerName || apt.clientName || t('common.noDataAvailable')
}

function getPatientId(apt: AppointmentData): string | number | null {
  return apt.patientId ?? apt.patient_id ?? apt.patient?.id ?? null
}

function getPatientLink(apt: AppointmentData) {
  const patientId = getPatientId(apt)
  const branchId = branchStore.selectedBranchId

  return {
    path: `/dentist/patient/${patientId}/`,
    query: branchId == null ? {} : { branchId: String(branchId) },
  }
}

function shouldLinkPatient(apt: AppointmentData): boolean {
  return !userStore.isReceptionist && getPatientId(apt) != null
}

function getEmployeeName(apt: AppointmentData): string {
  return apt.employeeName || apt.employee || apt.staffName || apt.providerName || t('common.noDataAvailable')
}

function getService(apt: AppointmentData): string {
  return apt.serviceName || apt.service || apt.treatmentName || apt.procedureName || ''
}

function getLocation(apt: AppointmentData): string {
  return apt.branchName || apt.location || apt.branch || apt.room || ''
}

function getNotes(apt: AppointmentData): string {
  return apt.notes || apt.comment || apt.description || ''
}

function getStatusValue(apt: AppointmentData): string {
  return String(apt.status || apt.state || '').toLowerCase()
}

function getStatusLabel(apt: AppointmentData): string {
  const value = getStatusValue(apt)
  if (!value) return t('appointmentsList.statusLabels.unknown')

  const key = `appointmentsList.statusLabels.${value}`
  const translation = t(key)

  return translation === key
    ? value.replace(/[-_]/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
    : translation
}

function getStatusType(apt: AppointmentData): 'success' | 'warning' | 'error' | 'info' | 'default' {
  const value = getStatusValue(apt)
  if (['completed', 'done', 'confirmed'].includes(value)) return 'success'
  if (['pending', 'rescheduled', 'processing'].includes(value)) return 'warning'
  if (['cancelled', 'canceled', 'no-show', 'noshow', 'failed'].includes(value)) return 'error'
  if (!value) return 'default'
  return 'info'
}

function parseDateMaybe(value: unknown): Date | null {
  if (!value) return null
  if (value instanceof Date) return Number.isNaN(value.getTime()) ? null : value
  const date = new Date(value as string)
  return Number.isNaN(date.getTime()) ? null : date
}

function getAppointmentDate(apt: AppointmentData): Date | null {
  return (
    parseDateMaybe(apt.appointment_timestamp) ||
    parseDateMaybe(apt.scheduledAt) ||
    parseDateMaybe(apt.date) ||
    (apt.date && apt.time ? parseDateMaybe(`${apt.date} ${apt.time}`) : null)
  )
}

function formatDateTime(apt: AppointmentData): string {
  const date = getAppointmentDate(apt)
  if (!date) return t('appointmentsList.noDateSet')

  return new Intl.DateTimeFormat(undefined, {
    weekday: 'short',
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(date)
}

function isSameDay(a: Date | null, b: Date): boolean {
  if (!a) return false
  return (
    a.getFullYear() === b.getFullYear() &&
    a.getMonth() === b.getMonth() &&
    a.getDate() === b.getDate()
  )
}
</script>

<style scoped>
.appointment-board {
  width: 100%;
}

.shell {
  border-radius: 24px;
  background:
    radial-gradient(circle at top left, rgba(92, 124, 250, 0.12), transparent 28%),
    radial-gradient(circle at top right, rgba(34, 197, 94, 0.1), transparent 24%),
    linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(250, 251, 255, 0.95));
  box-shadow: 0 24px 80px rgba(35, 46, 79, 0.08);
}

.hero {
  display: grid;
  grid-template-columns: 1.5fr 1fr;
  gap: 20px;
  align-items: end;
  margin-bottom: 20px;
}

.eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  border-radius: 999px;
  background: rgba(92, 124, 250, 0.08);
  color: #3558e3;
  font-weight: 600;
  font-size: 0.92rem;
  width: fit-content;
}

.eyebrow__icon,
.empty-icon {
  color: inherit;
}

.title {
  margin: 12px 0 8px;
  font-size: clamp(1.5rem, 3vw, 2.4rem);
  line-height: 1.1;
  letter-spacing: -0.03em;
  color: #15213a;
}

.subtitle {
  margin: 0;
  max-width: 62ch;
  color: #62708f;
  font-size: 0.98rem;
}

.hero__stats {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
}

.toolbar {
  margin-bottom: 12px;
}

.toolbar__row {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 220px;
  gap: 12px;
}

.status-select {
  min-width: 0;
}

.cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 14px;
}

.apt-card,
.row-card {
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.88);
  border: 1px solid rgba(103, 119, 156, 0.12);
  box-shadow: 0 12px 36px rgba(31, 41, 55, 0.06);
  backdrop-filter: blur(10px);
}

.card-top {
  display: grid;
  grid-template-columns: auto minmax(0, 1fr) auto;
  gap: 12px;
  align-items: center;
}

.avatar {
  display: grid;
  place-items: center;
  width: 46px;
  height: 46px;
  border-radius: 16px;
  background: linear-gradient(135deg, rgba(92, 124, 250, 0.14), rgba(34, 197, 94, 0.12));
  color: #3558e3;
}

.card-title,
.row-title,
.side-value {
  color: #16223d;
  font-weight: 700;
}

.card-title {
  font-size: 1.02rem;
}

.patient-link {
  color: inherit;
  text-decoration: none;
}

.patient-link:hover {
  text-decoration: underline;
}

.card-subtitle,
.row-subtitle,
.side-label,
.meta-item,
.row-meta,
.row-notes,
.notes,
.id-pill {
  color: #66738f;
}

.card-subtitle {
  margin-top: 2px;
  font-size: 0.92rem;
}

.meta-grid {
  display: grid;
  gap: 10px;
  margin-top: 16px;
}

.meta-item,
.row-meta span {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 0.92rem;
}

.notes,
.row-notes {
  margin-top: 12px;
  padding: 12px 14px;
  border-radius: 16px;
  background: rgba(92, 124, 250, 0.06);
  line-height: 1.55;
}

.card-footer {
  margin-top: 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.id-pill {
  font-size: 0.82rem;
  padding: 6px 10px;
  border-radius: 999px;
  background: rgba(103, 119, 156, 0.08);
}

.row-layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 240px;
  gap: 18px;
  align-items: start;
}

.row-title-line {
  display: flex;
  align-items: start;
  justify-content: space-between;
  gap: 12px;
}

.row-title {
  font-size: 1.08rem;
}

.row-subtitle {
  margin-top: 4px;
}

.row-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 14px;
  margin-top: 14px;
}

.row-side {
  display: grid;
  gap: 12px;
  padding: 14px;
  border-radius: 18px;
  background: linear-gradient(180deg, rgba(245, 247, 252, 0.9), rgba(255, 255, 255, 0.9));
  border: 1px solid rgba(103, 119, 156, 0.12);
}

.side-block {
  display: grid;
  gap: 4px;
}

.side-label {
  font-size: 0.78rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.mobile-view {
  display: none;
}

.desktop-view {
  display: grid;
  gap: 14px;
}

.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.24s ease;
}

.fade-slide-enter-from,
.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(10px);
}

@media (max-width: 991px) {
  .hero {
    grid-template-columns: 1fr;
  }

  .toolbar__row {
    grid-template-columns: 1fr;
  }

  .hero__stats {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }

  .row-layout {
    grid-template-columns: 1fr;
  }

  .row-side {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 767px) {
  .hero__stats {
    grid-template-columns: 1fr;
  }

  .desktop-view {
    display: none;
  }

  .mobile-view {
    display: block;
  }

  .cards {
    grid-template-columns: 1fr;
  }

  .row-side {
    grid-template-columns: 1fr;
  }

  .shell {
    border-radius: 20px;
  }
}
</style>
