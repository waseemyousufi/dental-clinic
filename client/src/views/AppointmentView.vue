<script setup lang="ts">
import { ref, onMounted, computed, watch, inject, type Ref } from 'vue'
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

const message = useMessage()
const calendarRef = ref<InstanceType<typeof FullCalendar> | null>(null)
const isSidebarCollapsed = inject<Ref<boolean>>('isSidebarCollapsed', ref(false))
const route = useRoute()
const userStore = useUserStore();

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
}

// --- States ---
const appointments = ref<AppointmentWithNames[]>([])
const employeeOptions = ref<{ label: string; value: number }[]>([])
const patientOptions = ref<{ label: string; value: number }[]>([])
const searchQuery = ref('')

// Modals
const showAddEditModal = ref(false)
const showViewModal = ref(false)
const isEditMode = ref(false)
const selectedAppointment = ref<AppointmentWithNames | null>(null)

// Form State
const formModel = ref<Partial<AppointmentData> | null>(null)

const statusOptions = [
  { label: 'Pending', value: 'pending' },
  { label: 'Confirmed', value: 'confirmed' },
  { label: 'Completed', value: 'completed' },
  { label: 'Cancelled', value: 'cancelled' }
]

// --- Helper Functions ---
const formatName = (obj: EmployeeAbbr | PatientAbbr) => {
  if (obj.name) return obj.name
  return `${obj.fName || ''} ${obj.lName || ''}`.trim() || 'Unknown'
}

const getStatusType = (status: string) => {
  switch (status) {
    case 'pending': return 'info'
    case 'confirmed': return 'success'
    case 'completed': return 'default'
    case 'cancelled': return 'error'
    default: return 'info'
  }
}

const getStatusColor = (status: string) => {
  switch (status) {
    case 'pending': return '#1890ff'
    case 'confirmed': return '#52c41a'
    case 'completed': return '#8c8c8c'
    case 'cancelled': return '#f5222d'
    default: return '#1890ff'
  }
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
    patientOptions.value = (Array.isArray(rawPats) ? rawPats : (rawPats?.patients || [])).map((pat: PatientData) => ({
      label: formatName(pat),
      value: pat.id
    }))

    await fetchAppointments()
  } catch (error) {
    console.error(error)
    message.error('Failed to initialize data')
  }
}

const fetchAppointments = async () => {
  try {
    const response = await appointmentApi.getBranchAppointments(getEffectiveBranchId())
    const raw = response.data?.data || response.data
    if (Array.isArray(raw)) {
      appointments.value = raw.map((apt: AppointmentData) => {
        const emp = employeeOptions.value.find((e) => e.value === apt.employeeId)
        const pat = patientOptions.value.find((p) => p.value === apt.patientId)
        return {
          ...apt,
          employeeName: emp?.label || apt.employee || 'N/A',
          patientName: pat?.label || apt.patient || 'N/A'
        }
      })
    }
  } catch (error) {
    message.error('Failed to fetch appointments')
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
    // Saturday to Thursday: 8 AM to 5 PM
    daysOfWeek: [6, 0, 1, 2, 3, 4], // Saturday, Sunday, Monday, Tuesday, Wednesday, Thursday
    startTime: '08:00',
    endTime: '17:00',
  },
}))

const handleDateClick = (info: DateClickArg) => {
  isEditMode.value = false
  formModel.value = {
    description: '',
    appointment_timestamp: info.dateStr, // Default to 8 AM on the selected date
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
      message.success('Appointment updated successfully')
    } else {
      await appointmentApi.postAppointment(payload)
      message.success('Appointment created successfully')
    }
    showAddEditModal.value = false
    await fetchAppointments()
  } catch (error) {
    message.error('Operation failed')
  }
}

const handleDelete = async () => {
  if (!selectedAppointment.value) return
  try {
    await appointmentApi.deleteAppointment(selectedAppointment.value.id)
    message.success('Appointment deleted successfully')
    showViewModal.value = false
    await fetchAppointments()
  } catch (error) {
    message.error('Failed to delete appointment')
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
          <n-input v-model:value="searchQuery" placeholder="Search patient, employee or date..."
            style="max-width: 300px; flex-grow: 1;" clearable @keyup.enter="handleSearch" />
          <n-button type="primary" secondary @click="handleSearch">Search</n-button>
        </n-space>
        <n-button type="primary" @click="openAddModal">Add Appointment</n-button>
      </n-space>
    </div>

    <n-card v-if="userStore.isReceptionist || userStore.isAdmin" class="calendar-card">
      <FullCalendar ref="calendarRef" :options="calendarOptions" />
    </n-card>

    <AppointmentFormModal v-if="userStore.isReceptionist || userStore.isAdmin" v-model:show="showAddEditModal" :appointment="formModel" @save="handleSave" />

    <!-- View Modal -->
    <n-modal v-model:show="showViewModal" transform-origin="center">
      <n-card title="Appointment Details" bordered size="small" style="max-width: 600px;" role="dialog"
        aria-modal="true">
        <template #header-extra>
          <n-tag :type="getStatusType(selectedAppointment?.status || '')" round>
            {{ selectedAppointment?.status.toUpperCase() }}
          </n-tag>
        </template>

        <div v-if="selectedAppointment" class="details">
          <n-space vertical size="large">
            <div>
              <n-text depth="3">Patient</n-text>
              <n-h3 style="margin: 0">{{ selectedAppointment.patientName }}</n-h3>
            </div>
            <div>
              <n-text depth="3">Assigned To</n-text>
              <div>{{ selectedAppointment.employeeName }}</div>
            </div>
            <div>
              <n-text depth="3">Time</n-text>
              <div>{{ new Date(selectedAppointment.appointment_timestamp).toLocaleString() }}</div>
            </div>
            <n-divider style="margin: 8px 0" />
            <div>
              <n-text depth="3">Description</n-text>
              <div style="white-space: pre-wrap">{{ selectedAppointment.description }}</div>
            </div>
          </n-space>
        </div>

        <template #footer>
          <n-space justify="space-between">
            <n-popconfirm @positive-click="handleDelete">
              <template #trigger>
                <n-button type="error" ghost title="Delete">
                  Delete
                </n-button>
              </template>
              Are you sure you want to delete this appointment?
            </n-popconfirm>
            <n-space>
              <n-button @click="showViewModal = false">Close</n-button>
              <n-button type="primary" title="Edit" @click="openEditModal">
                Edit
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
