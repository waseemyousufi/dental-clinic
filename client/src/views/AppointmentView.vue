<script setup lang="ts">
import { ref, onMounted, computed, watch, inject, type Ref } from 'vue'
import { useI18n } from 'vue-i18n'
import type { EventClickArg, DateClickArg } from '@fullcalendar/core'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import interactionPlugin from '@fullcalendar/interaction'
import { useRoute } from 'vue-router'
import appointmentApi from '@api/appointment'
import employeeApi from '@api/employee'
import patientApi from '@api/patient'
import type AppointmentData from '@api/interfaces/Appointment'
import type EmployeeData from '@api/interfaces/Employee'
import type PatientData from '@api/interfaces/patient'
import { Icon } from '@iconify/vue'
import {
  useMessage,
  NInput,
  NButton,
  NSpace,
  NModal,
  NCard,
  NPopconfirm,
  NTag,
  NH3,
  NText,
  NDivider,
} from 'naive-ui'
import AppointmentFormModal from '@/components/AppointmentFormModal.vue'
import AppointmentsList from '@/components/AppointmentsList.vue'
import useUserStore from '@/stores/user'
import { formatWhatsAppPhone, sendViaWhatsApp } from '@/utils/whatsapp'

const message = useMessage()
const { t } = useI18n()
const calendarRef = ref<InstanceType<typeof FullCalendar> | null>(null)
const isSidebarCollapsed = inject<Ref<boolean>>('isSidebarCollapsed', ref(false))
const route = useRoute()
const userStore = useUserStore()

const getEffectiveBranchId = (): number | undefined => {
  const usr = JSON.parse(localStorage.getItem('user') || 'null')
  const userBranchId = usr?.user?.employee?.branchId
  if (typeof userBranchId === 'number' && Number.isFinite(userBranchId)) return userBranchId

  const raw = route.query.branchId
  const fromQuery = typeof raw === 'string' ? Number(raw) : NaN
  return Number.isFinite(fromQuery) ? fromQuery : undefined
}

// --- Types ---
type AppointmentWithNames = AppointmentData & {
  employeeName?: string
  patientName?: string
  patientPhone?: string
}

type EmployeeAbbr = {
  id: number
  name?: string
  fName?: string
  lName?: string
}

type PatientAbbr = {
  id: number
  name?: string
  fName?: string
  lName?: string
  phone?: string
}

// --- States ---
const appointments = ref<AppointmentWithNames[]>([])
const employeeOptions = ref<{ label: string; value: number }[]>([])
const patientOptions = ref<{ label: string; value: number }[]>([])
const patientContacts = ref<Record<number, { name: string; phone: string }>>({})
const searchQuery = ref('')

// Modals
const showAddEditModal = ref(false)
const showViewModal = ref(false)
const isEditMode = ref(false)
const selectedAppointment = ref<AppointmentWithNames | null>(null)

// Form State
const formModel = ref<Partial<AppointmentData> | null>(null)

const statusOptions = [
  { label: t('appointmentView.statusOptions.pending'), value: 'pending' },
  { label: t('appointmentView.statusOptions.confirmed'), value: 'confirmed' },
  { label: t('appointmentView.statusOptions.completed'), value: 'completed' },
  { label: t('appointmentView.statusOptions.cancelled'), value: 'cancelled' },
  { label: t('appointmentView.statusOptions.noShow'), value: 'no_show' },
]

const formatStatusLabel = (status: string) => {
  switch (status) {
    case 'pending':
      return t('appointmentView.statusOptions.pending')
    case 'confirmed':
      return t('appointmentView.statusOptions.confirmed')
    case 'completed':
      return t('appointmentView.statusOptions.completed')
    case 'cancelled':
      return t('appointmentView.statusOptions.cancelled')
    case 'no_show':
      return t('appointmentView.statusOptions.noShow')
    default:
      return t('appointmentView.fallbackName')
  }
}

// --- Helper Functions ---
const formatName = (obj: EmployeeAbbr | PatientAbbr) => {
  if (obj.name) return obj.name
  return `${obj.fName || ''} ${obj.lName || ''}`.trim() || t('appointmentView.fallbackName')
}

const getStatusType = (status: string) => {
  switch (status) {
    case 'pending': return 'info'
    case 'confirmed': return 'success'
    case 'completed': return 'default'
    case 'cancelled': return 'error'
    case 'no_show': return 'warning'
    default: return 'info'
  }
}

const getStatusColor = (status: string) => {
  switch (status) {
    case 'pending': return '#1890ff'
    case 'confirmed': return '#52c41a'
    case 'completed': return '#8c8c8c'
    case 'cancelled': return '#f5222d'
    case 'no_show': return '#faad14'
    default: return '#1890ff'
  }
}

const clinicName = computed(() => (userStore.settings as any)?.clinic_name ?? '')
const clinicAddress = computed(() => (userStore.settings as any)?.address ?? '')

const appointmentTemplates = computed(() => ({
  cancel: (userStore.settings as any)?.wa_patient_cancel ?? '',
  complete: (userStore.settings as any)?.wa_patient_complete ?? '',
  reminder: (userStore.settings as any)?.wa_patient_reminder ?? '',
}))

const formatAppointmentDateTime = (timestamp?: string) => {
  if (!timestamp) return ''
  try {
    return new Date(timestamp).toLocaleString()
  } catch {
    return ''
  }
}

const formatAppointmentDate = (timestamp?: string) => {
  if (!timestamp) return ''
  try {
    return new Date(timestamp).toLocaleDateString()
  } catch {
    return ''
  }
}

const formatAppointmentTime = (timestamp?: string) => {
  if (!timestamp) return ''
  try {
    return new Date(timestamp).toLocaleTimeString([], {
      hour: '2-digit',
      minute: '2-digit',
    })
  } catch {
    return ''
  }
}

const replaceTemplateVars = (template: string, vars: Record<string, string>) => {
  return (template || '')
    .replace(/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/g, (_, key: string) => vars[key] ?? '')
    .replace(/\{\s*([a-zA-Z0-9_]+)\s*\}/g, (_, key: string) => vars[key] ?? '')
}

const buildAppointmentMessage = (kind: 'cancel' | 'complete' | 'reminder') => {
  const apt = selectedAppointment.value
  if (!apt) return ''

  const template = appointmentTemplates.value[kind]
  const defaultLine =
    kind === 'cancel'
      ? 'Your appointment has been cancelled.'
      : kind === 'complete'
        ? 'Your appointment has been completed.'
        : 'This is a reminder for your upcoming appointment.'

  const vars = {
    patient_name: apt.patientName || '',
    patient_phone: apt.patientPhone || '',
    clinic_name: clinicName.value || '',
    clinic_address: clinicAddress.value || '',
    appointment_date: formatAppointmentDate(apt.appointment_timestamp),
    appointment_time: formatAppointmentTime(apt.appointment_timestamp),
    appointment_datetime: formatAppointmentDateTime(apt.appointment_timestamp),
    doctor_name: apt.employeeName || '',
    description: apt.description || '',
    message_line: defaultLine,
  }

  const fallback = [
    `Hello ${vars.patient_name},`,
    '',
    `This is ${vars.clinic_name}.`,
    defaultLine,
    vars.appointment_datetime ? `Appointment: ${vars.appointment_datetime}` : '',
    vars.clinic_address ? `Address: ${vars.clinic_address}` : '',
  ]
    .filter(Boolean)
    .join('\n')

  return replaceTemplateVars(template || fallback, vars)
}

const sendAppointmentWhatsAppMessage = (kind: 'cancel' | 'complete' | 'reminder') => {
  const apt = selectedAppointment.value
  if (!apt) return

  const rawPhone = apt.patientPhone || ''
  const phone = formatWhatsAppPhone(rawPhone)

  if (!phone) {
    message.error('Patient phone number is missing or invalid.')
    return
  }

  const text = buildAppointmentMessage(kind)
  console.log(JSON.stringify(text))
  const url = new URL(`https://wa.me/${phone}`)
  url.searchParams.set('text', text)
  console.log(url)
  sendViaWhatsApp(url.toString())
}

// --- API Calls ---
const loadData = async () => {
  try {
    const branchId = getEffectiveBranchId()
    const [empRes, patRes] = await Promise.all([
      employeeApi.getBranchEmployees(true, branchId),
      patientApi.getBranchPatients(true, branchId)
    ])

    const rawEmps = empRes.data?.data || empRes.data
    employeeOptions.value = (Array.isArray(rawEmps) ? rawEmps : (rawEmps?.employees || [])).map((emp: EmployeeData) => ({
      label: formatName(emp as EmployeeAbbr),
      value: emp.id
    }))

    const rawPats = patRes.data?.data || patRes.data
    const patients = Array.isArray(rawPats) ? rawPats : (rawPats?.patients || [])

    patientContacts.value = Object.fromEntries(
      patients.map((pat: PatientData) => [
        pat.id,
        {
          name: formatName(pat as PatientAbbr),
          phone: (pat as PatientAbbr).phone || '',
        },
      ]),
    )

    patientOptions.value = patients.map((pat: PatientData) => ({
      label: formatName(pat as PatientAbbr),
      value: pat.id
    }))

    await fetchAppointments()
  } catch (error) {
    console.error(error)
    message.error(t('appointmentView.messages.initializeDataError'))
  }
}

const fetchAppointments = async () => {
  try {
    const response = await appointmentApi.getBranchAppointments(getEffectiveBranchId())
    const raw = response.data?.data || response.data
    if (Array.isArray(raw)) {
      appointments.value = raw.map((apt: AppointmentData & { patientPhone?: string }) => {
        const emp = employeeOptions.value.find((e) => e.value === apt.employeeId)
        const pat = patientContacts.value[apt.patientId]
        return {
          ...apt,
          employeeName: emp?.label || apt.employee || 'N/A',
          patientName: pat?.name || (apt as any).patient || 'N/A',
          patientPhone: apt.patientPhone || pat?.phone || '',
        }
      })
    }
  } catch (error) {
    message.error(t('appointmentView.messages.fetchAppointmentsError'))
  }
}

// --- Calendar logic ---
const calendarEvents = computed(() => {
  const query = searchQuery.value.toLowerCase().trim()
  if (!query) {
    return appointments.value.map(apt => ({
      id: String(apt.id),
      title: apt.patientName,
      start: apt.appointment_timestamp,
      extendedProps: apt,
      backgroundColor: getStatusColor(apt.status),
      borderColor: getStatusColor(apt.status)
    }))
  }

  return appointments.value
    .filter((apt) => {
      const dateStr = apt.appointment_timestamp.split('T')[0]
      return (
        apt.description.toLowerCase().includes(query) ||
        apt.employeeName?.toLowerCase().includes(query) ||
        apt.patientName?.toLowerCase().includes(query) ||
        dateStr.includes(query)
      )
    })
    .map(apt => ({
      id: String(apt.id),
      title: `${apt.patientName}: ${apt.description}`,
      start: apt.appointment_timestamp,
      extendedProps: apt,
      backgroundColor: getStatusColor(apt.status),
      borderColor: getStatusColor(apt.status)
    }))
})

const handleSearch = () => {
  const query = searchQuery.value.trim()
  if (!query) return

  // If it's a date-like string, jump to it
  const datePattern = /^\d{4}-\d{2}-\d{2}$/
  if (datePattern.test(query)) {
    calendarRef.value?.getApi().gotoDate(query)
    return
  }

  // If we match an appointment's date, jump to the first match
  const match = appointments.value.find(apt =>
    apt.patientName?.toLowerCase().includes(query.toLowerCase()) ||
    apt.employeeName?.toLowerCase().includes(query.toLowerCase()) ||
    apt.description.toLowerCase().includes(query.toLowerCase())
  )

  if (match) {
    const targetDate = match.appointment_timestamp.split('T')[0]
    calendarRef.value?.getApi().gotoDate(targetDate)
  }
}

const calendarOptions = computed(() => ({
  plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  headerToolbar: {
    left: 'prev,next today',
    center: 'title',
    right: 'dayGridMonth,timeGridWeek,timeGridDay'
  },
  events: calendarEvents.value,
  eventClick: (info: EventClickArg) => {
    selectedAppointment.value = info.event.extendedProps as AppointmentWithNames
    showViewModal.value = true
  },
  height: 'auto',
  editable: false,
  selectable: true,
  dateClick: handleDateClick,
  businessHours: {
    // Saturday to Friday: 8 AM to 5 PM
    daysOfWeek: [6, 0, 1, 2, 3, 4, 5], // Saturday, Sunday, Monday, Tuesday, Wednesday, Thursday, Friday
    startTime: '06:00',
    endTime: '20:00',
  },
}))

const handleDateClick = (info: DateClickArg) => {
  isEditMode.value = false

  // Create LOCAL date safely (prevents timezone shifting)
  const clickedDate = new Date(info.date)

  // Optional default hour
  clickedDate.setHours(8, 0, 0, 0)

  // Convert to local ISO without UTC shift
  const year = clickedDate.getFullYear()
  const month = String(clickedDate.getMonth() + 1).padStart(2, '0')
  const day = String(clickedDate.getDate()).padStart(2, '0')
  const hours = String(clickedDate.getHours()).padStart(2, '0')
  const minutes = String(clickedDate.getMinutes()).padStart(2, '0')

  const localDateTime =
    `${year}-${month}-${day}T${hours}:${minutes}`

  formModel.value = {
    description: '',
    appointment_timestamp: localDateTime,
    status: 'pending',
  }

  showAddEditModal.value = true
}
// --- Actions ---
const openAddModal = () => {
  isEditMode.value = false
  formModel.value = {
    description: '',
    appointment_timestamp: new Date().toISOString(),
    status: 'pending',
  }
  showAddEditModal.value = true
}

const openEditModal = () => {
  if (!selectedAppointment.value) return
  isEditMode.value = true
  formModel.value = {
    id: selectedAppointment.value.id,
    description: selectedAppointment.value.description,
    appointment_timestamp: selectedAppointment.value.appointment_timestamp,
    status: selectedAppointment.value.status,
    employeeId: selectedAppointment.value.employeeId,
    patientId: selectedAppointment.value.patientId,
    appointmentCost: selectedAppointment.value.appointmentCost,
    clinicalNotes: selectedAppointment.value.clinical_notes,
    procedureId: selectedAppointment.value.procedureId,
    treatmentPlanId: selectedAppointment.value.treatmentPlanId,
  }
  showViewModal.value = false
  showAddEditModal.value = true
}

const handleSave = async (payload: AppointmentData) => {
  try {
    if (isEditMode.value && payload.id) {
      await appointmentApi.updateAppointment(payload.id, payload)
      message.success(t('appointmentView.messages.updateSuccess'))
    } else {
      await appointmentApi.postAppointment(payload)
      message.success(t('appointmentView.messages.createSuccess'))
    }
    showAddEditModal.value = false
    await fetchAppointments()
  } catch (error) {
    message.error(t('appointmentView.messages.saveError'))
  }
}

const handleDelete = async () => {
  if (!selectedAppointment.value) return
  try {
    await appointmentApi.deleteAppointment(selectedAppointment.value.id)
    message.success(t('appointmentView.messages.deleteSuccess'))
    showViewModal.value = false
    await fetchAppointments()
  } catch (error) {
    message.error(t('appointmentView.messages.deleteError'))
  }
}

// Watch for sidebar collapse/expand to update calendar size
watch(isSidebarCollapsed, () => {
  setTimeout(() => {
    calendarRef.value?.getApi().updateSize()
  }, 350)
})

onMounted(loadData)
</script>

<template>
  <div class="appointment-view">
    <div class="header" v-if="userStore.isReceptionist || userStore.isAdmin">
      <n-space justify="space-between" align="center">
        <n-space>
          <n-input v-model:value="searchQuery" :placeholder="t('appointmentView.searchPlaceholder')"
            style="max-width: 300px; flex-grow: 1;" clearable @keyup.enter="handleSearch" />
          <n-button type="primary" secondary @click="handleSearch">{{ t('common.searchButtonText') }}</n-button>
        </n-space>
        <n-button type="primary" @click="openAddModal">{{ t('common.addButtonText') }}</n-button>
      </n-space>
    </div>

    <n-card v-if="userStore.isReceptionist || userStore.isAdmin" class="calendar-card">
      <FullCalendar ref="calendarRef" :options="calendarOptions" />
    </n-card>

    <AppointmentFormModal v-if="userStore.isReceptionist || userStore.isAdmin" v-model:show="showAddEditModal"
      :appointment="formModel" @save="handleSave" />

    <!-- View Modal -->
    <n-modal v-model:show="showViewModal" transform-origin="center">
      <n-card :title="t('appointmentView.calendar.title')" bordered size="small" style="max-width: 600px;" role="dialog"
        aria-modal="true">
        <template #header-extra>
          <n-tag :type="getStatusType(selectedAppointment?.status || '')" round>
            {{ formatStatusLabel(selectedAppointment?.status || '') }}
          </n-tag>
        </template>

        <div v-if="selectedAppointment" class="details">
          <n-space vertical size="large">
            <div>
              <n-text depth="3">{{ t('appointmentView.calendar.detailsSection.patientLabel') }}</n-text>
              <n-h3 style="margin: 0">{{ selectedAppointment.patientName }}</n-h3>
            </div>
            <div>
              <n-text depth="3">{{ t('appointmentView.calendar.detailsSection.assignedToLabel') }}</n-text>
              <div>{{ selectedAppointment.employeeName }}</div>
            </div>
            <div>
              <n-text depth="3">{{ t('appointmentView.calendar.detailsSection.timeLabel') }}</n-text>
              <div>{{ new Date(selectedAppointment.appointment_timestamp).toLocaleString() }}</div>
            </div>

            <div v-if="selectedAppointment.patientPhone">
              <n-text depth="3">Patient phone</n-text>
              <div>{{ selectedAppointment.patientPhone }}</div>
            </div>

            <n-divider style="margin: 8px 0" />

            <div>
              <n-text depth="3">{{ t('appointmentView.calendar.detailsSection.descriptionLabel') }}</n-text>
              <div style="white-space: pre-wrap">{{ selectedAppointment.description || t('common.noDataAvailable') }}
              </div>
            </div>

            <n-divider style="margin: 8px 0" />

            <div>
              <n-text depth="3">WhatsApp messages</n-text>
              <div style="margin-top: 8px;">
                <n-space align="center" size="small" wrap>
                  <n-button circle quaternary type="error" :disabled="!selectedAppointment.patientPhone"
                    title="Send cancellation message" aria-label="Send cancellation message"
                    @click="sendAppointmentWhatsAppMessage('cancel')">
                    <Icon icon="mdi:calendar-remove" width="18" height="18" />
                  </n-button>

                  <n-button circle quaternary type="success" :disabled="!selectedAppointment.patientPhone"
                    title="Send completion message" aria-label="Send completion message"
                    @click="sendAppointmentWhatsAppMessage('complete')">
                    <Icon icon="mdi:check-circle-outline" width="18" height="18" />
                  </n-button>

                  <n-button circle quaternary type="warning" :disabled="!selectedAppointment.patientPhone"
                    title="Send reminder message" aria-label="Send reminder message"
                    @click="sendAppointmentWhatsAppMessage('reminder')">
                    <Icon icon="mdi:bell-outline" width="18" height="18" />
                  </n-button>
                </n-space>
              </div>
            </div>
          </n-space>
        </div>

        <template #footer>
          <n-space justify="space-between">
            <n-popconfirm @positive-click="handleDelete">
              <template #trigger>
                <n-button type="error" ghost :title="t('appointmentView.messages.deleteConfirmMessage')">
                  {{ t('common.deleteButtonText') }}
                </n-button>
              </template>
              {{ t('appointmentView.messages.deleteConfirmMessage') }}
            </n-popconfirm>
            <n-space>
              <n-button @click="showViewModal = false">{{ t('common.closeButtonText') }}</n-button>
              <n-button type="primary" :title="t('common.editTooltip')" @click="openEditModal">
                {{ t('common.editButtonText') }}
              </n-button>
            </n-space>
          </n-space>
        </template>
      </n-card>
    </n-modal>

    <AppointmentsList :appointments="appointments" />
  </div>
</template>

<style scoped lang="scss">
.appointment-view {
  padding: 1rem;
  /* Reduced padding */
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  height: 100%;
  background-color: #f9fafb;
  overflow-x: hidden;
  box-sizing: border-box;
  /* Include padding in the element's total width */

  .header {
    background: white;
    padding: 1rem;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }

  .calendar-card {
    flex-grow: 0;
    /* Allow calendar card to take up remaining space */
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);

    .n-card-content {
      padding: 0 !important;
    }

    :deep(.fc) {
      font-family: inherit;
      --fc-border-color: #e5e7eb;
      --fc-today-bg-color: rgba(59, 130, 246, 0.08);

      .fc-day-today {
        background-color: var(--fc-today-bg-color);
      }

      .fc-toolbar-title {
        font-size: 1.25rem;
        font-weight: 600;
      }

      .fc-button-primary {
        background-color: white;
        border-color: #d1d5db;
        color: #374151;
        text-transform: capitalize;

        &:hover {
          background-color: #f9fafb;
          border-color: #9ca3af;
        }

        &:disabled {
          background-color: #f3f4f6;
        }

        &.fc-button-active {
          background-color: #e5e7eb;
          border-color: #9ca3af;
          color: #111827;
        }
      }

      .fc-event {
        cursor: pointer;
        padding: 2px 4px;
        border-radius: 4px;
        font-size: 0.85rem;
      }
    }
  }
}

.details {
  line-height: 1.6;

  .n-button {
    width: 36px;
    height: 36px;
  }
}

:deep(.n-message-container) {
  z-index: 100000 !important;
}

:deep(.n-base-selection-menu),
:deep(.n-base-popup),
:deep(.n-modal-container) {
  z-index: 99999 !important;
}

/* Add/Edit modal: narrow width cap + tight horizontal padding */
.appointment-add-edit-modal {
  :deep(.n-modal-body-wrapper) {
    padding: 6px 8px;
  }

  :deep(.appointment-form-card.n-card) {
    width: 100%;
    max-width: min(calc(100vw - 20px), 380px);
    box-sizing: border-box;
  }

  @media (min-width: 420px) {
    :deep(.appointment-form-card.n-card) {
      max-width: min(calc(100vw - 24px), 420px);
    }
  }

  @media (min-width: 520px) {
    :deep(.appointment-form-card.n-card) {
      max-width: min(calc(100vw - 28px), 460px);
    }
  }

  :deep(.appointment-form-card .n-card-header) {
    padding: 10px 12px 6px;
  }

  :deep(.appointment-form-card .n-card-content) {
    padding: 0 12px 10px;
  }

  :deep(.appointment-form-card .n-card__footer) {
    padding: 6px 12px 10px;
  }

  :deep(.appointment-form .n-form-item-feedback-wrapper) {
    min-height: 0;
  }

  .appointment-form__rows {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }

  .appointment-form__pair {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 6px 8px;
    align-items: start;
  }

  @media (max-width: 479px) {
    .appointment-form__pair {
      grid-template-columns: 1fr;
    }
  }

  :deep(.appointment-form__field.n-form-item) {
    margin-bottom: 0;
  }

  :deep(.appointment-form__field .n-form-item-label) {
    padding-bottom: 2px;
    font-size: 12px;
  }
}
</style>
style>
