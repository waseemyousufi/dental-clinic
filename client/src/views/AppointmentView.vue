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
import type PatientData from '@api/interfaces/Patient'
import {
  useMessage,
  NInput,
  NButton,
  NSelect,
  NForm,
  NFormItem,
  NSpace,
  NModal,
  NCard,
  NDatePicker,
  NPopconfirm,
  NTag,
  NH3,
  NText,
  NDivider,
} from 'naive-ui'

const message = useMessage()
const calendarRef = ref<InstanceType<typeof FullCalendar> | null>(null)
const isSidebarCollapsed = inject<Ref<boolean>>('isSidebarCollapsed', ref(false))
const route = useRoute()

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
const formModel = ref({
  id: undefined as number | undefined,
  description: '',
  appointment_timestamp: null as number | null,
  status: 'pending',
  employeeId: undefined as number | undefined,
  patientId: undefined as number | undefined,
})

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
    id: undefined,
    description: '',
    appointment_timestamp: info.date.getTime(),
    status: 'pending',
    employeeId: undefined,
    patientId: undefined,
  }
  showAddEditModal.value = true
}

// --- Actions ---
const openAddModal = () => {
  isEditMode.value = false
  formModel.value = {
    id: undefined,
    description: '',
    appointment_timestamp: Date.now(),
    status: 'pending',
    employeeId: undefined,
    patientId: undefined,
  }
  showAddEditModal.value = true
}

const openEditModal = () => {
  if (!selectedAppointment.value) return
  isEditMode.value = true
  formModel.value = {
    id: selectedAppointment.value.id,
    description: selectedAppointment.value.description,
    appointment_timestamp: new Date(selectedAppointment.value.appointment_timestamp).getTime(),
    status: selectedAppointment.value.status,
    employeeId: selectedAppointment.value.employeeId,
    patientId: selectedAppointment.value.patientId,
  }
  showViewModal.value = false
  showAddEditModal.value = true
}

// a helper function to make the date sql compatible.
function dateTime(dateStr: string) {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  if (isNaN(date.getTime())) return dateStr

  const Y = date.getFullYear()
  const M = String(date.getMonth() + 1).padStart(2, '0')
  const D = String(date.getDate()).padStart(2, '0')
  const h = String(date.getHours()).padStart(2, '0')
  const m = String(date.getMinutes()).padStart(2, '0')

  return `${Y}/${M}/${D} ${h}:${m}`
}

const handleSave = async () => {
  if (!formModel.value.description || !formModel.value.appointment_timestamp || formModel.value.employeeId === undefined || formModel.value.patientId === undefined) {
    message.warning('Please fill in all required fields')
    return
  }


  const payload: AppointmentData = {
    id: formModel.value.id || 0,
    description: formModel.value.description,
    appointment_timestamp: dateTime(formModel.value.appointment_timestamp),
    status: formModel.value.status,
    employeeId: Number(formModel.value.employeeId),
    patientId: Number(formModel.value.patientId)
  }
  console.log(formModel.value.employeeId, formModel.value.patientId);

  console.log(payload)

  try {
    if (isEditMode.value && formModel.value.id) {
      await appointmentApi.updateAppointment(formModel.value.id, payload)
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

function handleEmployeeChange(value: number) {
  formModel.value.employeeId = value
}

function handlePatientChange(value: number) {
  console.log(formModel.value.patientId, value)
  formModel.value.patientId = value
  console.log(formModel.value.patientId, value)
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
    <div class="header">
      <n-space justify="space-between" align="center">
        <n-space>
          <n-input v-model:value="searchQuery" placeholder="Search patient, employee or date..."
            style="max-width: 300px; flex-grow: 1;" clearable @keyup.enter="handleSearch" />
          <n-button type="primary" secondary @click="handleSearch">Search</n-button>
        </n-space>
        <n-button type="primary" @click="openAddModal">Add Appointment</n-button>
      </n-space>
    </div>

    <n-card class="calendar-card">
      <FullCalendar ref="calendarRef" :options="calendarOptions" />
    </n-card>

    <!-- Add/Edit Modal -->
    <n-modal v-model:show="showAddEditModal" class="appointment-add-edit-modal" transform-origin="center"
      :mask-closable="false">
      <n-card :title="isEditMode ? 'Edit Appointment' : 'New Appointment'" class="appointment-form-card" bordered
        size="medium" style="max-width: 600px;" role="dialog" aria-modal="true">
        <n-form :model="formModel" class="appointment-form" size="small" :show-require-mark="false">
          <div class="appointment-form__rows">
            <div class="appointment-form__pair">
              <n-form-item label="Patient" path="patientId" class="appointment-form__field">
                <n-select @update:value="handlePatientChange"
                  :value="patientOptions.filter(e => e.value === formModel.patientId)[0]?.label as string"
                  :options="patientOptions" placeholder="Patient" filterable size="small" />
              </n-form-item>
              <n-form-item label="Employee" path="employeeId" class="appointment-form__field">
                <n-select :options="employeeOptions" @update:value="handleEmployeeChange" :value="employeeOptions.filter(e => e.value === formModel.employeeId)[0]?.label as string
                  " />
              </n-form-item>
            </div>
            <div class="appointment-form__pair">
              <n-form-item label="Date & time" path="appointment_timestamp" class="appointment-form__field">
                <n-date-picker v-model:value="formModel.appointment_timestamp" type="datetime" clearable size="small"
                  style="width: 100%" />
              </n-form-item>
              <n-form-item label="Status" path="status" class="appointment-form__field">
                <n-select v-model:value="formModel.status" :options="statusOptions" size="small" />
              </n-form-item>
            </div>
            <n-form-item label="Description" path="description"
              class="appointment-form__field appointment-form__field--full">
              <n-input v-model:value="formModel.description" type="textarea" placeholder="Details…" size="small"
                :autosize="{ minRows: 2, maxRows: 6 }" />
            </n-form-item>
          </div>
        </n-form>

        <template #footer>
          <n-space justify="end" size="small">
            <n-button size="small" @click="showAddEditModal = false">Cancel</n-button>
            <n-button size="small" type="primary" @click="handleSave">{{ isEditMode ? 'Update' : 'Save' }}</n-button>
          </n-space>
        </template>
      </n-card>
    </n-modal>

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
