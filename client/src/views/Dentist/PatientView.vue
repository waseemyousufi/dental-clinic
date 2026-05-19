<script lang="ts" setup>
import { useMessage } from 'naive-ui'
import { ref, computed, nextTick, onBeforeUnmount, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import Odontogram from '@/components/Odontogram.vue'
import PrimaryOdontogram from '@/components/PrimaryOdontogram.vue'
import PatientService from '@api/patient'
import OdontogramService from '@api/odontogram'
import type PatientData from '@api/interfaces/patient'
import type { ConditionLibrary } from '@api/interfaces/Odontogram'
import TreatmentPlanApi from '@api/treatmentPlan'
import appointmentApi from '@api/appointment'
import type AppointmentData from '@api/interfaces/Appointment'
import EditTreatment from '@/components/EditTreatment.vue'
import AppointmentFormModal from '@/components/AppointmentFormModal.vue'
import procedureApi from '@api/procedure'
import prescriptionApi from '@api/prescription'
import settingsApi from '@api/settings'
import PrescriptionTemplate from '@/components/PrescriptionTemplate.vue'
import type { SettingsData } from '@api/interfaces/Settings'

import {
  NCard,
  NButton,
  NSpace,
  NSelect,
  NPopconfirm,
  NEmpty,
  NTag,
  NDivider
} from 'naive-ui'
import { Icon } from '@iconify/vue'
import useUserStore from '@/stores/user'
type OdontogramState = Record<number, Record<string, any>>
type PrescriptionOption = {
  label: string
  value: number
}

type PrintMedication = {
  name: string
  dosage: string
}

type PrescriptionPrintPayload = {
  clinicPrimary: string
  clinicSecondary: string
  clinicTertiary?: string
  address: string
  phone: string
  patientName: string
  date: string
  medications: PrintMedication[]
}

const route = useRoute()
const patientId = computed(() => Number(route.params.id))

const loading = ref(false)

const patient = ref<(PatientData & { f_name: string; l_name: string }) | null>(null)
const conditionLibrary = ref<ConditionLibrary[]>([])
const activeFinding = ref<ConditionLibrary | null>(null)
const userStore = useUserStore()

const odontogramData = ref<OdontogramState>({})

const message = useMessage()
const { t } = useI18n()

// treatment plan state
const treatmentPlans = ref<any[]>([])
const procedures = ref<any[]>([])
const appointments = ref<any[]>([])
const selectedAppointmentByPlan = ref<Record<number, number | null>>({})
const linkingPlanId = ref<number | null>(null)
const isTreatmentModalVisible = ref(false)
const planLoading = ref(false)

const editingPlan = ref<any | null>(null)
const editPlanLoading = ref(false)
const isAppointmentModalVisible = ref(false)
const editingAppointment = ref<Partial<AppointmentData> | null>(null)
const appointmentSaving = ref(false)
const prescriptions = ref<Array<{ id: number; drug_name: string }>>([])
const prescriptionOptions = ref<PrescriptionOption[]>([])
const selectedPrescriptionIds = ref<number[]>([])
const prescribing = ref(false)
const clinicSettings = ref<Partial<SettingsData> | null>(null)
const prescriptionPrintData = ref<PrescriptionPrintPayload | null>(null)
let prescriptionCleanupTimer: ReturnType<typeof setTimeout> | null = null

const treatmentPlanStats = computed(() => {
  const total = treatmentPlans.value.length
  const accepted = treatmentPlans.value.filter((p) => p.status === 'accepted').length
  const proposed = treatmentPlans.value.filter((p) => p.status === 'proposed').length
  const completed = treatmentPlans.value.filter((p) => p.status === 'completed').length

  const outstanding = treatmentPlans.value.reduce((sum, plan) => {
    const estimated = Number(plan.total_estimated_cost || 0)
    const paid = Number(plan.total_amount_paid || 0)
    return sum + Math.max(0, estimated - paid)
  }, 0)

  return { total, accepted, proposed, completed, outstanding }
})

function getStatusMeta(status: string) {
  switch (status) {
    case 'accepted':
      return { type: 'success' as const, label: t('dentistPatientView.statusLabels.accepted') }
    case 'proposed':
      return { type: 'warning' as const, label: t('dentistPatientView.statusLabels.proposed') }
    case 'completed':
      return { type: 'info' as const, label: t('dentistPatientView.statusLabels.completed') }
    case 'cancelled':
    case 'rejected':
      return { type: 'error' as const, label: t('dentistPatientView.statusLabels.cancelled') }
    default:
      return { type: 'default' as const, label: status || t('dentistPatientView.statusLabels.unknown') }
  }
}

function remainingBalance(plan: any) {
  const estimated = Number(plan.total_estimated_cost || 0)
  const paid = Number(plan.total_amount_paid || 0)
  return Math.max(0, estimated - paid)
}

function formatMoney(value: any) {
  if (value === null || value === undefined || value === '') return '—'
  const num = Number(value)
  if (Number.isNaN(num)) return '—'
  return `${num.toLocaleString()} AFN`
}

function createAppointment(plan: any) {
  editingAppointment.value = {
    description: plan.procedure?.name ? t('dentistPatientView.treatmentPlan.followUp', { name: plan.procedure.name }) : t('dentistPatientView.treatmentPlan.appointmentFallback'),
    appointment_timestamp: new Date().toISOString(),
    status: 'pending',
    patientId: patientId.value,
    treatment_plan_id: Number(plan.id),
  }
  isAppointmentModalVisible.value = true
}

function formatDate(dateValue: string | number | null | undefined) {
  if (!dateValue) return '—'
  const d = new Date(dateValue)
  if (Number.isNaN(d.getTime())) return '—'
  return d.toLocaleDateString()
}

function normalizeResponse<T>(response: any): T {
  return (response?.data?.data ?? response?.data ?? []) as T
}

function normalizePrescription(prescription: any) {
  return {
    id: Number(prescription?.id),
    drug_name: prescription?.drug_name || prescription?.name || '',
  }
}

function cleanupPrintedPrescription() {
  prescriptionPrintData.value = null

  if (prescriptionCleanupTimer) {
    clearTimeout(prescriptionCleanupTimer)
    prescriptionCleanupTimer = null
  }
}

function printPrescriptionFromHost() {
  const iframe = document.createElement('iframe')
  iframe.style.position = 'fixed'
  iframe.style.right = '0'
  iframe.style.bottom = '0'
  iframe.style.width = '0'
  iframe.style.height = '0'
  iframe.style.border = '0'
  document.body.appendChild(iframe)

  const doc = iframe.contentDocument || iframe.contentWindow?.document
  if (!doc) {
    document.body.removeChild(iframe)
    throw new Error('Unable to access print iframe')
  }

  const data = prescriptionPrintData.value
  if (!data) {
    document.body.removeChild(iframe)
    throw new Error('Prescription data not ready')
  }

  const medicationsMarkup = data.medications.length
    ? data.medications
      .map((medication) => `
        <div class="med-item">
          <div class="name">${medication.name}</div>
          <div class="dosage">${medication.dosage}</div>
        </div>
      `)
      .join('')
    : `
      <div class="med-item">
        <div class="name">${t('dentistPatientView.print.noMedications')}</div>
        <div class="dosage">${t('dentistPatientView.print.selectBeforePrinting')}</div>
      </div>
    `

  doc.open()
  doc.write(`
    <!DOCTYPE html>
    <html>
      <head>
        <style>
          @page { margin: 0; }
          html, body {
            margin: 0;
            padding: 0;
            background: #fff;
          }

          body {
            font-family: 'Inter', Arial, sans-serif;
            color: #000;
          }

          .print-preview-wrapper {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            background: #fff;
          }

          .paper-sheet {
            width: 210mm;
            height: 297mm;
            padding: 20mm;
            box-sizing: border-box;
            background: white;
            display: flex;
            flex-direction: column;
          }

          .prescription-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            border-bottom: 5px solid black;
            padding-bottom: 25px;
            margin-bottom: 50px;
          }

          .h1-brand { font-size: 3.5rem; font-weight: 900; margin: 0; line-height: 0.9; letter-spacing: -2px; }
          .h2-sub-brand { font-size: 1.4rem; font-weight: 700; margin: 5px 0 0; text-transform: uppercase; }
          .h3-tagline { font-size: 1rem; font-weight: 400; color: #444; margin: 2px 0 0; white-space: pre-wrap; }

          .contact-block { text-align: right; }
          .contact-row {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            margin-bottom: 5px;
            font-weight: 500;
          }

          .patient-section {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 40px;
            margin-bottom: 60px;
          }

          .field-box {
            border-bottom: 1.5px solid black;
            padding-bottom: 8px;
          }

          .field-box label {
            display: block;
            font-size: 0.75rem;
            font-weight: 900;
            text-transform: uppercase;
            margin-bottom: 4px;
          }

          .field-value { font-size: 1.2rem; }

          .prescription-main {
            display: flex;
            gap: 30px;
            flex: 1;
          }

          .rx-indicator {
            font-size: 5rem;
            line-height: 1;
            font-weight: 900;
          }

          .medication-area {
            flex: 1;
          }

          .med-item { margin-bottom: 35px; }
          .med-item .name { font-size: 1.3rem; font-weight: 800; }
          .med-item .dosage { font-size: 1.1rem; font-style: italic; margin-top: 5px; color: #333; white-space: pre-wrap; }

          .prescription-footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 40px;
          }

          .legal-notice { font-size: 0.8rem; max-width: 250px; line-height: 1.4; color: #666; }
          .signature-line { width: 250px; border-top: 2px solid black; margin-bottom: 8px; }
          .signature-label { font-size: 0.85rem; font-weight: 700; text-align: center; }
        </style>
      </head>
      <body>
        <div class="print-preview-wrapper">
          <article class="paper-sheet">
            <header class="prescription-header">
              <div class="identity">
                <h1 class="h1-brand">${data.clinicPrimary}</h1>
                <h2 class="h2-sub-brand">${data.clinicSecondary}</h2>
                ${data.clinicTertiary ? `<h3 class="h3-tagline">${data.clinicTertiary}</h3>` : ''}
              </div>

              <div class="contact-block">
                <div class="contact-row">
                  <span>${data.address}</span>
                </div>
                <div class="contact-row">
                  <span>${data.phone}</span>
                </div>
              </div>
            </header>

            <section class="patient-section">
              <div class="field-box">
                <label>${t('dentistPatientView.print.patientName')}</label>
                <div class="field-value">${data.patientName}</div>
              </div>
              <div class="field-box">
                <label>${t('dentistPatientView.print.date')}</label>
                <div class="field-value">${data.date}</div>
              </div>
            </section>

            <main class="prescription-main">
              <div class="rx-indicator">Rx</div>
              <div class="medication-area">
                ${medicationsMarkup}
              </div>
            </main>

            <footer class="prescription-footer">
              <div class="legal-notice">
                ${t('dentistPatientView.print.legalNotice')}
              </div>
              <div class="signature-column">
                <div class="signature-line"></div>
                <div class="signature-label">${t('dentistPatientView.print.signatureLabel')}</div>
              </div>
            </footer>
          </article>
        </div>
      </body>
    </html>
  `)
  doc.close()

  setTimeout(() => {
    iframe.contentWindow?.focus()
    iframe.contentWindow?.print()

    setTimeout(() => {
      if (iframe.parentNode) {
        iframe.parentNode.removeChild(iframe)
      }
    }, 1000)
  }, 250)
}

async function fetchTreatmentData() {
  if (!patientId.value) return
  planLoading.value = true
  try {
    const [plansRes, procRes, appointmentsRes] = await Promise.all([
      TreatmentPlanApi.getBranchTreatmentPlans(patientId.value),
      procedureApi.getProcedures(),
      appointmentApi.getBranchAppointments()
    ])

    treatmentPlans.value = plansRes.data?.data ?? plansRes.data ?? []
    procedures.value = procRes.data?.data ?? procRes.data ?? []
    const allAppointments = appointmentsRes.data?.data ?? appointmentsRes.data ?? []
    console.log(allAppointments)
    appointments.value = allAppointments.filter((a: any) => Number(a.patientId) === patientId.value)
  } catch (err) {
    console.log(err)
      message.error(t('dentistPatientView.messages.loadTreatmentPlansError'))
  } finally {
    planLoading.value = false
  }
}

async function fetchPrescriptionData() {
  try {
    const [settingsResponse, prescriptionsResponse] = await Promise.all([
      settingsApi.getSettings(),
      prescriptionApi.getBranchPrescriptions(),
    ])

    clinicSettings.value = normalizeResponse<any>(settingsResponse) ?? {}

    const rawPrescriptions = normalizeResponse<any[]>(prescriptionsResponse)
      .map(normalizePrescription)
      .filter((entry) => entry.id && entry.drug_name)

    prescriptions.value = rawPrescriptions
    prescriptionOptions.value = rawPrescriptions.map((entry) => ({
      label: entry.drug_name,
      value: entry.id,
    }))
  } catch (error) {
    console.error(error)
    message.error(t('dentistPatientView.messages.loadPrescriptionsError'))
  }
}

async function handlePrescribe() {
  if (!patient.value) return
  if (!selectedPrescriptionIds.value.length) {
    message.warning(t('dentistPatientView.messages.selectPrescriptionWarning'))
    return
  }

  try {
    prescribing.value = true

    const medications = selectedPrescriptionIds.value
      .map((id) => prescriptions.value.find((entry) => entry.id === id))
      .filter(Boolean)
      .map((entry) => ({
        name: entry!.drug_name,
        dosage: 'Use as directed by the dentist.',
      }))

    prescriptionPrintData.value = {
      clinicPrimary: userStore.settings.clinic_name || clinicSettings.value?.clinic_name || 'Clinic',
      clinicSecondary: t('dentistPatientView.prescription.templateSecondary'),
      clinicTertiary: clinicSettings.value?.prescription_template?.footer || undefined,
      address: clinicSettings.value?.address || t('dentistPatientView.prescription.addressUnavailable'),
      phone: userStore.settings.clinic_phone || clinicSettings.value?.phone || t('dentistPatientView.prescription.phoneUnavailable'),
      patientName: `${patient.value.fName || patient.value.f_name || ''} ${patient.value.lName || patient.value.l_name || ''}`.trim(),
      date: new Date().toLocaleDateString(),
      medications,
    }

    await nextTick()

    prescriptionCleanupTimer = setTimeout(cleanupPrintedPrescription, 1500)
    printPrescriptionFromHost()
  } catch (error) {
    console.error(error)
    cleanupPrintedPrescription()
    message.error(t('dentistPatientView.messages.preparePrescriptionPrintError'))
  } finally {
    prescribing.value = false
  }
}

function appointmentOptionsForPlan(plan: any) {
  return appointments.value
    .filter((appointment: any) => {
      const linkedPlanId = appointment.treatment_plan_id
      return !linkedPlanId || Number(linkedPlanId) === Number(plan.id)
    })
    .map((appointment: any) => ({
      label: `${formatDate(appointment.appointment_timestamp)} - ${appointment.status || t('dentistPatientView.statusLabels.pending')}`,
      value: Number(appointment.id),
    }))
}

async function addAppointmentToPlan(planId: number) {
  const appointmentId = selectedAppointmentByPlan.value[planId]
  if (!appointmentId) {
    message.warning(t('dentistPatientView.messages.selectAppointmentWarning'))
    return
  }

  linkingPlanId.value = planId
  try {
    await TreatmentPlanApi.addAppointment(planId, appointmentId)
    message.success(t('dentistPatientView.messages.appointmentLinkedSuccess'))
    selectedAppointmentByPlan.value[planId] = null
    await fetchTreatmentData()
  } catch (error) {
    message.error(t('dentistPatientView.messages.linkAppointmentError'))
  } finally {
    linkingPlanId.value = null
  }
}

async function updatePlanStatus(planId: number, newStatus: string) {
  try {
    await TreatmentPlanApi.updateStatus(planId, { status: newStatus })
    message.success(t('dentistPatientView.messages.planStatusUpdated', { status: newStatus }))
    await fetchTreatmentData()
  } catch (err) {
    message.error(t('dentistPatientView.messages.updatePlanError'))
  }
}

async function deletePlan(planId: number) {
  try {
    await TreatmentPlanApi.deleteTreatmentPlan(planId)
    message.success(t('dentistPatientView.messages.planDeletedSuccess'))
    await fetchTreatmentData()
  } catch (err) {
    message.error(t('dentistPatientView.messages.deletePlanError'))
  }
}

function openEditTreatment(plan: any) {
  editingPlan.value = { ...plan }
  isTreatmentModalVisible.value = true
}

function openNewTreatmentPlan() {
  editingPlan.value = null
  isTreatmentModalVisible.value = true
}

async function saveTreatmentPlan(payload: any) {
  editPlanLoading.value = true

  try {
    const body = {
      ...payload,
      patient_id: patientId.value,
    }

    if (editingPlan.value?.id) {
      await TreatmentPlanApi.updateTreatmentPlan(editingPlan.value.id, body)
      message.success(t('dentistPatientView.messages.treatmentUpdatedSuccess'))
    } else {
      await TreatmentPlanApi.postTreatmentPlan(body)
      message.success(t('dentistPatientView.messages.treatmentPlanCreatedSuccess'))
    }

    isTreatmentModalVisible.value = false
    editingPlan.value = null
    await fetchTreatmentData()
  } catch (err) {
    message.error(t('dentistPatientView.messages.saveTreatmentError'))
  } finally {
    editPlanLoading.value = false
  }
}

async function handleAppointmentSave(payload: AppointmentData) {
  appointmentSaving.value = true
  try {
    await appointmentApi.postAppointment(payload)
    message.success(t('dentistPatientView.messages.appointmentCreatedSuccess'))
    isAppointmentModalVisible.value = false
    editingAppointment.value = null
    await fetchTreatmentData()
  } catch (error) {
    message.error(t('dentistPatientView.messages.createAppointmentError'))
  } finally {
    appointmentSaving.value = false
  }
}

function buildOdontogramState(teethArray: any[]): OdontogramState {
  const state: OdontogramState = {}

  teethArray.forEach((tooth: any) => {
    const fdi = Number(tooth.fdi_code)

    state[fdi] = {
      symbols: []
    }

      ; (tooth.active_conditions || []).forEach((cond: any) => {
        const lib = cond.condition_library
        if (!lib) return

        const isSymbol = !!lib.svg_path

        if (isSymbol) {
          state[fdi].symbols!.push({
            id: cond.id,
            condition_id: lib.id,
            slug: lib.slug,
            svg: lib.svg_path,
            color: lib.ui_color
          })

          return
        }

        cond.surfaces?.forEach((surface: string) => {
          state[fdi][surface.toLowerCase()] = {
            color: lib.ui_color,
            id: cond.id
          }
        })
      })
  })

  return state
}

function clearLocalSlot(toothFdi: number, surfaceKey: string) {
  if (!odontogramData.value[toothFdi]) return

  const nextToothState = { ...odontogramData.value[toothFdi] }
  delete nextToothState[surfaceKey]

  odontogramData.value = {
    ...odontogramData.value,
    [toothFdi]: nextToothState,
  }
}

function setLocalSlot(toothFdi: number, key: string, value: any) {
  odontogramData.value = {
    ...odontogramData.value,
    [toothFdi]: {
      ...(odontogramData.value[toothFdi] || {}),
      [key]: value,
    },
  }
}

async function refreshOdontogram() {
  if (!patientId.value) return false

  try {
    const res = await OdontogramService.getPatientOdontogram(patientId.value)
    const teethArray = res.data?.data ?? res.data ?? []

    odontogramData.value = buildOdontogramState(teethArray)

    activeFinding.value = null

    return true
  } catch (err) {
    console.error('Refresh failed:', err)
    return false
  }
}

async function loadPatientData() {
  if (!patientId.value) return
  loading.value = true

  try {
    const [patientRes, odontogramRes, libraryRes] = await Promise.all([
      PatientService.getPatient(patientId.value),
      OdontogramService.getPatientOdontogram(patientId.value),
      OdontogramService.getConditionLibrary(),
    ])

    patient.value = {
      ...patientRes.data.data,
      f_name: patientRes.data.data.fName,
      l_name: patientRes.data.data.lName,
    }

    const raw = odontogramRes.data

    const teethArray = Array.isArray(raw?.data)
      ? raw.data
      : Array.isArray(raw)
        ? raw
        : Array.isArray(raw?.teeth)
          ? raw.teeth
          : []

    if (!Array.isArray(teethArray)) {
      console.warn('Invalid odontogram payload:', teethArray)
      return
    }
    odontogramData.value = buildOdontogramState(teethArray)

    const libArr = libraryRes.data?.data ?? libraryRes.data ?? []
    conditionLibrary.value = libArr.map((item: any) => ({
      id: item.id,
      label: item.label,
      slug: item.slug,
      ui_color: item.ui_color,
      svg_icon_path: item.svg_icon_path,
      svg_path: item.svg_path
    }))
    console.log("Fresh Loaded Condition Library: ", conditionLibrary.value)

    activeFinding.value = null

    const state: any = {}

    teethArray.forEach((tooth: any) => {
      const fdi = Number(tooth?.fdi_code)

      if (!fdi) return

      state[fdi] = {
        symbols: [],
        surfaces: {},
      }

      const conditions = Array.isArray(tooth?.active_conditions)
        ? tooth.active_conditions
        : []

      conditions.forEach((cond: any) => {
        const lib = cond?.condition_library

        if (!lib) return

        const svg = lib.svg_path ?? null

        if (typeof svg === 'string' && svg.includes('<svg')) {
          state[fdi].symbols.push({
            id: cond.id,
            condition_id: cond.condition_library.id,
            svg: cond.condition_library.svg_path,
            slug: cond.condition_library.slug,
            color: cond.condition_library.ui_color
          })
          return
        }

        const surfaces = Array.isArray(cond?.surfaces)
          ? cond.surfaces
          : []

        surfaces.forEach((surface: string) => {
          state[fdi].surfaces[surface.toLowerCase()] = {
            color: lib.ui_color,
            id: cond.id,
          }
        })
      })
    })

    message.success('Records synchronized')
  } catch (error) {
    console.error('Load error:', error)
    message.error('Failed to load data')
  } finally {
    loading.value = false
  }
}

function selectCondition(condition: ConditionLibrary) {
  if (!condition?.id) {
    message.error(t('dentistPatientView.messages.invalidConditionError'))
    return
  }

  if (activeFinding.value?.id === condition.id) {
    activeFinding.value = null
    message.info(t('dentistPatientView.messages.toolDeselected'))
    return
  }

  activeFinding.value = condition
  message.success(t('dentistPatientView.messages.conditionSelected', { label: condition.label }))
}

async function handleToothClick(toothFdi: number, surface: string) {
  console.log(patient.value)
  if (!patientId.value) return

  const surfKey = surface.toLowerCase()
  const toothState = odontogramData.value[toothFdi] || {}

  const existingSurface = toothState[surfKey]
  const symbols = toothState.symbols || []

  const isSymbolTool = !!activeFinding.value?.svg_path

  if (symbols.length > 0 && (!activeFinding.value || isSymbolTool)) {
    try {
      const symbolToDelete = symbols[0]

      odontogramData.value[toothFdi].symbols =
        symbols.filter((s: any) => s.id !== symbolToDelete.id)

      await OdontogramService.deleteToothCondition(symbolToDelete.id)

      message.success(t('dentistPatientView.messages.conditionRemoved'))
      await refreshOdontogram()
      return
    } catch (err) {
      console.error('Symbol delete failed', err)
      message.error(t('dentistPatientView.messages.removeConditionError'))
      await refreshOdontogram()
      return
    }
  }

  if (existingSurface?.id) {
    try {
      clearLocalSlot(toothFdi, surfKey)

      await OdontogramService.deleteToothCondition(existingSurface.id)

      message.success(t('dentistPatientView.messages.conditionRemoved'))
      await refreshOdontogram()
      return
    } catch (err) {
      console.error('Surface delete failed', err)
      message.error(t('dentistPatientView.messages.removeConditionError'))
      await refreshOdontogram()
      return
    }
  }

  if (!activeFinding.value?.id) {
    message.warning(t('dentistPatientView.messages.selectConditionWarning'))
    return
  }

  if (isSymbolTool) {
    try {
      const payload = {
        tooth_id: toothFdi,
        condition_id: activeFinding.value.id,
        surfaces: ['CENTER']
      }

      if (!odontogramData.value[toothFdi]) {
        odontogramData.value[toothFdi] = { symbols: [] }
      }

      if (!odontogramData.value[toothFdi].symbols) {
        odontogramData.value[toothFdi].symbols = []
      }

      odontogramData.value[toothFdi].symbols.push({
        id: 'temp-' + Date.now(),
        condition_id: activeFinding.value.id,
        svg: activeFinding.value.svg_path,
        color: activeFinding.value.ui_color,
        slug: activeFinding.value.slug
      })

      await OdontogramService.saveToothCondition(
        Number(patientId.value),
        payload
      )

      message.success(t('dentistPatientView.messages.conditionSaved'))
      await refreshOdontogram()
    } catch (err) {
      console.error('Symbol save failed', err)
      message.error(t('dentistPatientView.messages.saveConditionError'))
      await refreshOdontogram()
    }

    return
  }

  try {
    const payload = {
      tooth_id: toothFdi,
      condition_id: activeFinding.value.id,
      surfaces: [surface.toUpperCase()]
    }

    setLocalSlot(toothFdi, surfKey, {
      color: activeFinding.value.ui_color || '#999',
      id: 'pending'
    })

    await OdontogramService.saveToothCondition(
      Number(patientId.value),
      payload
    )

    message.success(t('dentistPatientView.messages.conditionSaved'))
    await refreshOdontogram()
  } catch (err) {
    console.error('Surface save failed', err)
    message.error(t('dentistPatientView.messages.saveConditionError'))
    await refreshOdontogram()
  }
}

onMounted(async () => {
  await loadPatientData()
  await fetchTreatmentData()
  await fetchPrescriptionData()
})

onBeforeUnmount(() => {
  cleanupPrintedPrescription()
})
</script>

<template>
  <div class="view-patient-container">
    <div v-if="loading" class="loading-state">{{ t('dentistPatientView.loadingState') }}</div>

    <template v-else-if="patient">
      <header class="patient-header">
        <div class="patient-info">
          <div class="patient-name-block">
            <h1>{{ patient.fName || patient.f_name }} {{ patient.lName || patient.l_name }}</h1>
              <span class="patient-badge">{{ t('dentistPatientView.patientIdLabel', { id: patient.id }) }}</span>
          </div>

          <div class="patient-summary">
            <div class="summary-chip">
              <Icon icon="mdi:gender-male-female" />
            <span>{{ patient.gender || '—' }}</span>
            </div>
            <div class="summary-chip">
              <Icon icon="healthicons:blood-ab-p" />
            <span>{{ patient.bloodType || '—' }}</span>
            </div>
            <div class="summary-chip">
              <Icon icon="tabler:phone" />
            <span>{{ patient.phone || '—' }}</span>
            </div>
          </div>
        </div>

        <div class="patient-profile-card">
          <div class="profile-card-header">
            <div class="profile-card-title">
              <Icon icon="mdi:account-heart-outline" />
              <span>{{ t('dentistPatientView.profile.title') }}</span>
            </div>
            <span class="profile-card-subtitle">{{ t('dentistPatientView.profile.subtitle') }}</span>
          </div>

          <div class="profile-grid">
            <div class="profile-item">
              <div class="profile-icon blood">
                <Icon icon="healthicons:blood-ab-p" />
              </div>
              <div class="profile-content">
                <span>{{ t('dentistPatientView.profile.bloodType') }}</span>
                <strong>{{ patient.bloodType || '—' }}</strong>
              </div>
            </div>

            <div class="profile-item">
              <div class="profile-icon gender">
                <Icon icon="mdi:gender-male-female" />
              </div>
              <div class="profile-content">
                <span>{{ t('dentistPatientView.profile.gender') }}</span>
                <strong>{{ patient.gender || '—' }}</strong>
              </div>
            </div>

            <div class="profile-item">
              <div class="profile-icon phone">
                <Icon icon="tabler:phone" />
              </div>
              <div class="profile-content">
                <span>{{ t('dentistPatientView.profile.phone') }}</span>
                <strong>{{ patient.phone || '—' }}</strong>
              </div>
            </div>

            <div class="profile-item">
              <div class="profile-icon emergency">
                <Icon icon="mdi:ambulance" />
              </div>
              <div class="profile-content">
                <span>{{ t('dentistPatientView.profile.emergencyContact') }}</span>
                <strong>{{ patient.emgContact || '—' }}</strong>
              </div>
            </div>

            <div class="profile-item profile-item-wide">
              <div class="profile-icon date">
                <Icon icon="tabler:calendar-stats" />
              </div>
              <div class="profile-content">
                <span>{{ t('dentistPatientView.profile.registrationDate') }}</span>
                <strong>
                  {{
                    patient.registerationDate
                      ? new Date(patient.registerationDate).toLocaleDateString()
                      : '—'
                  }}
                </strong>
              </div>
            </div>
          </div>
        </div>
      </header>

      <main class="content-grid">
        <section class="chart-section">
          <div class="odontogram-card">
            <div class="legend-panel">
              <div class="legend-panel-header">
                <div>
                  <span class="legend-panel-title">{{ t('dentistPatientView.odontogram.title') }}</span>
                  <span class="legend-panel-subtitle">{{ t('dentistPatientView.odontogram.subtitle') }}</span>
                </div>
              </div>

              <div class="legend">
                <div v-if="conditionLibrary.length === 0" class="legend-hint">
                  {{ t('dentistPatientView.odontogram.emptyConditions') }}
                </div>

                <div v-for="(f, idx) in conditionLibrary" :key="f.id || idx" class="legend-pill"
                  :class="{ active: activeFinding?.id === f.id }" @click="selectCondition(f)">
                  <span class="dot"
                    :style="{ backgroundColor: f.svg_icon_path || f.svg_path ? '#ffffff' : f.ui_color }"></span>
                  {{ f.label }}
                </div>
              </div>
            </div>

            <div class="chart-box">
              <span class="chart-label">{{ t('dentistPatientView.odontogram.permanentTeeth') }}</span>

              <div class="odontogram-shell">
                <Odontogram v-model="odontogramData" ref="odontogramRef" :slug="activeFinding?.slug || ''"
                  :active-finding="activeFinding?.ui_color || '#ffffff'" @tooth-click="handleToothClick" />
              </div>

              <div class="odontogram-shell">
                <PrimaryOdontogram v-model="odontogramData" ref="odontogramRef" :slug="activeFinding?.slug || ''"
                  :active-finding="activeFinding?.ui_color || '#ffffff'" @tooth-click="handleToothClick" />
              </div>
            </div>

            <div class="chart-divider"></div>
          </div>

          <section class="treatment-plans">
            <n-card class="treatment-board" :segmented="{ content: true }">
              <template #header>
                <div class="treatment-board__header">
                  <div class="treatment-board__title-block">
                    <p class="section-kicker">{{ t('dentistPatientView.treatmentPlan.kicker') }}</p>
                    <h3>{{ t('dentistPatientView.treatmentPlan.title') }}</h3>
                    <p class="section-subtitle">
                      {{ t('dentistPatientView.treatmentPlan.subtitle') }}
                    </p>
                  </div>

                  <n-space align="center" :wrap="false">
                    <n-button type="primary" size="small" @click="openNewTreatmentPlan">
                      <template #icon>
                        <Icon icon="material-symbols:add-notes-outline" />
                      </template>
                      {{ t('dentistPatientView.treatmentPlan.newPlanButton') }}
                    </n-button>
                  </n-space>
                </div>
              </template>

              <div class="treatment-stats">
                <div class="stat-card">
                  <span class="stat-label">{{ t('dentistPatientView.treatmentPlan.stats.plans') }}</span>
                  <strong>{{ treatmentPlanStats.total }}</strong>
                </div>

                <div class="stat-card">
                  <span class="stat-label">{{ t('dentistPatientView.treatmentPlan.stats.accepted') }}</span>
                  <strong>{{ treatmentPlanStats.accepted }}</strong>
                </div>

                <div class="stat-card">
                  <span class="stat-label">{{ t('dentistPatientView.treatmentPlan.stats.proposed') }}</span>
                  <strong>{{ treatmentPlanStats.proposed }}</strong>
                </div>

                <!-- <div class="stat-card">
                  <span class="stat-label">Outstanding</span>
                  <strong>{{ formatMoney(treatmentPlanStats.outstanding) }}</strong>
                </div> -->
              </div>

              <n-divider />

              <div v-if="planLoading" class="plan-loading">
                {{ t('dentistPatientView.treatmentPlan.loading') }}
              </div>

              <div v-else-if="treatmentPlans.length === 0" class="empty-state-mini">
                <n-empty :description="t('dentistPatientView.treatmentPlan.empty')" />
              </div>

              <div v-else class="plan-grid">
                <n-card v-for="plan in treatmentPlans" :key="plan.id" class="plan-card" size="small" :bordered="true">
                  <template #header>
                    <div class="plan-card__header">
                      <div class="plan-card__heading">
                        <h4>{{ plan.procedure?.name || t('dentistPatientView.treatmentPlan.procedureFallback', { id: plan.procedure_id }) }}</h4>
                        <p>{{ plan.procedure?.category || t('dentistPatientView.treatmentPlan.uncategorized') }}</p>
                      </div>

                      <n-tag round size="small" :type="getStatusMeta(plan.status).type">
                        {{ getStatusMeta(plan.status).label }}
                      </n-tag>
                    </div>
                  </template>

                  <div class="plan-card__body">
                     <div class="plan-pill-row">
                      <div class="plan-pill">
                        <span class="plan-pill__label">{{ t('dentistPatientView.treatmentPlan.startDate') }}</span>
                        <span class="plan-pill__value">{{ formatDate(plan.start_date) }}</span>
                      </div>

                      <div class="plan-pill">
                        <span class="plan-pill__label">{{ t('dentistPatientView.treatmentPlan.appointments') }}</span>
                        <span class="plan-pill__value">{{ plan.appointments.length }}/{{ plan.appointments_needed ?? '—' }}</span>
                      </div>

                      <div class="plan-pill">
                        <span class="plan-pill__label">{{ t('dentistPatientView.treatmentPlan.accepted') }}</span>
                        <span class="plan-pill__value">
                          {{ plan.is_accepted ? t('dentistPatientView.common.yes') : t('dentistPatientView.common.no') }}
                        </span>
                      </div>
                    </div>

                    <div class="billing-grid">
                      <!-- <div class="billing-box">
                        <span class="billing-box__label">Estimated cost</span>
                        <strong class="billing-box__value">
                          {{ formatMoney(plan.total_estimated_cost) }}
                        </strong>
                      </div>

                      <div class="billing-box">
                        <span class="billing-box__label">{{ t('dentistPatientView.treatmentPlan.paid') }}</span>
                        <strong class="billing-box__value">
                          {{ formatMoney(plan.total_amount_paid) }}
                        </strong>
                      </div>

                      <div class="billing-box billing-box--accent">
                        <span class="billing-box__label">Remaining</span>
                        <strong class="billing-box__value">
                          {{ formatMoney(remainingBalance(plan)) }}
                        </strong>
                      </div> -->
                    </div>

                    <div class="plan-note">
                      <Icon icon="ph:stethoscope-light" />
                      <span>
                        {{ plan.is_accepted ? t('dentistPatientView.treatmentPlan.readyForScheduling') : t('dentistPatientView.treatmentPlan.pendingReview') }}
                      </span>
                    </div>

                    <div class="plan-note">
                      <Icon icon="mdi:calendar-check-outline" />
                      <span>
                        {{ t('dentistPatientView.treatmentPlan.linkedAppointments', { count: Array.isArray(plan.appointments) ? plan.appointments.length : 0 }) }}
                      </span>
                    </div>

                    <div v-if="Array.isArray(plan.appointments) && plan.appointments.length"
                      class="linked-appointments">
                      <div v-for="appointment in plan.appointments" :key="appointment.id"
                        class="linked-appointment-row">
                        <div class="appp">
                          <span>{{ formatDate(appointment.appointment_timestamp) }}</span>
                          <div>
                            <span>{{ appointment.status || t('dentistPatientView.statusLabels.pending') }}</span>
                            <span> / </span>
                            <span>{{ appointment.employee || t('dentistPatientView.treatmentPlan.unassigned') }}</span>
                          </div>
                        </div>

                        <div v-if="userStore.isDoctor || userStore.isAdmin" class="clinical-notes">
                          <span>{{ appointment.clinical_notes }}</span>
                        </div>

                        <div class="app-actions" v-if="userStore.isReceptionist">
                          <n-button>{{ t('dentistPatientView.treatmentPlan.paidButton') }}</n-button>
                        </div>

                      </div>
                    </div>
                  </div>

                  <template #footer>
                    <div class="plan-actions">
                      <!-- <n-select :to="false" v-model:value="selectedAppointmentByPlan[plan.id]" size="small" clearable filterable
                        placeholder="Link existing appointment" :options="appointmentOptionsForPlan(plan)"
                        style="min-width: 220px" /> -->
<!--
                      <n-button size="small" secondary :loading="linkingPlanId === plan.id"
                        @click.stop="addAppointmentToPlan(plan.id)">
                        Link Appointment
                      </n-button> -->

                      <n-button size="small" tertiary @click.stop="openEditTreatment(plan)">
                        <template #icon>
                          <Icon icon="tabler:pencil" />
                        </template>
                        {{ t('dentistPatientView.common.edit') }}
                      </n-button>
<!--
                      <n-button size="small" type="primary" @click.stop="createAppointment(plan)">
                        <template #icon>
                          <Icon icon="mdi:calendar-plus" />
                        </template>
                        Add Appointment
                      </n-button> -->

                      <n-popconfirm @positive-click="deletePlan(plan.id)">
                        <template #trigger>
                          <n-button type="error" ghost size="small" @click.stop>
                            <template #icon>
                              <Icon icon="tabler:trash" />
                            </template>
                          </n-button>
                        </template>
                        {{ t('dentistPatientView.treatmentPlan.deleteConfirm') }}
                      </n-popconfirm>
                    </div>
                  </template>
                </n-card>
              </div>
            </n-card>
          </section>

          <section class="prescription-section">
            <n-card class="prescription-board" :segmented="{ content: true }">
              <template #header>
                <div class="treatment-board__header">
                  <div class="treatment-board__title-block">
                    <p class="section-kicker">{{ t('dentistPatientView.prescription.kicker') }}</p>
                    <h3>{{ t('dentistPatientView.prescription.title') }}</h3>
                    <p class="section-subtitle">
                      {{ t('dentistPatientView.prescription.subtitle') }}
                    </p>
                  </div>
                </div>
              </template>

              <div class="prescription-board__controls">
                <n-select
                  v-model:value="selectedPrescriptionIds"
                  multiple
                  filterable
                  clearable
                  :placeholder="t('dentistPatientView.prescription.placeholder')"
                  :options="prescriptionOptions"
                  class="prescription-select"
                />

                <n-button type="primary" :loading="prescribing" @click="handlePrescribe">
                  <template #icon>
                    <Icon icon="mdi:printer-outline" />
                  </template>
                  {{ t('dentistPatientView.prescription.button') }}
                </n-button>
              </div>

              <div v-if="!prescriptionOptions.length" class="empty-state-mini">
                <n-empty :description="t('dentistPatientView.prescription.empty')" />
              </div>
            </n-card>
          </section>
        </section>

        <EditTreatment v-model:show="isTreatmentModalVisible" :plan="editingPlan" :procedures="procedures"
          :loading="editPlanLoading" @save="saveTreatmentPlan" />

        <AppointmentFormModal :is-doctor-using="true" v-model:show="isAppointmentModalVisible"
          :appointment="editingAppointment" :patient-id="patientId" :lock-patient="true" :loading="appointmentSaving"
          @save="handleAppointmentSave" />

        <div v-if="prescriptionPrintData" class="prescription-print-host">
          <PrescriptionTemplate
            :clinic-primary="prescriptionPrintData.clinicPrimary"
            :clinic-secondary="prescriptionPrintData.clinicSecondary"
            :clinic-tertiary="prescriptionPrintData.clinicTertiary"
            :address="prescriptionPrintData.address"
            :phone="prescriptionPrintData.phone"
            :patient-name="prescriptionPrintData.patientName"
            :date="prescriptionPrintData.date"
            :medications="prescriptionPrintData.medications"
            paper-size="A5"
          />
        </div>
      </main>
    </template>

    <div v-else-if="!loading" class="empty-state">
      <p>{{ t('dentistPatientView.emptyState') }}</p>
    </div>
  </div>
</template>

<style scoped>
.view-patient-container {
  min-height: 100vh;
  position: relative;
  background: #fafafa;
  padding: clamp(1rem, 2vw, 2rem);
  box-sizing: border-box;
  font-size: 1rem;
}

.view-patient-container * {
  box-sizing: border-box;
}

.loading-state,
.empty-state {
  text-align: center;
  padding: 6.25em;
  color: #8c8c8c;
}

.patient-header {
  display: grid;
  grid-template-columns: minmax(18rem, 20rem) minmax(0, 1fr);
  gap: 1rem 1.25rem;
  align-items: stretch;
  margin-bottom: 1.5rem;
}

.patient-info {
  background: linear-gradient(180deg, #ffffff 0%, #fbfcfe 100%);
  padding: clamp(1rem, 1.5vw, 1.5rem);
  border-radius: 1em;
  border: 0.0625em solid #eef2f7;
  box-shadow: 0 0.125em 0.75em rgba(15, 23, 42, 0.05);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  gap: 1rem;
}

.patient-name-block {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.patient-info h1 {
  margin: 0;
  font-size: clamp(1.35rem, 1rem + 1vw, 1.85rem);
  color: #1f2937;
  line-height: 1.15;
  letter-spacing: -0.02em;
}

.patient-badge {
  display: inline-flex;
  width: fit-content;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.82rem;
  color: #64748b;
  background: #f8fafc;
  border: 0.0625em solid #e2e8f0;
  border-radius: 999px;
  padding: 0.35em 0.75em;
}

.patient-summary {
  display: flex;
  flex-wrap: wrap;
  gap: 0.6rem;
}

.summary-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.45em 0.8em;
  border-radius: 999px;
  background: #ffffff;
  border: 0.0625em solid #e6ebf2;
  color: #334155;
  font-size: 0.86rem;
  box-shadow: 0 0.125em 0.4em rgba(15, 23, 42, 0.04);
  white-space: nowrap;
}

.summary-chip svg {
  font-size: 1rem;
}

.patient-profile-card {
  background: linear-gradient(180deg, #ffffff 0%, #fcfdff 100%);
  border: 0.0625em solid #eef2f7;
  border-radius: 1em;
  box-shadow: 0 0.125em 0.75em rgba(15, 23, 42, 0.05);
  padding: 1rem;
}

.profile-card-header {
  display: flex;
  justify-content: space-between;
  gap: 0.75rem;
  align-items: baseline;
  margin-bottom: 0.9rem;
}

.profile-card-title {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.98rem;
  font-weight: 700;
  color: #1f2937;
}

.profile-card-subtitle {
  font-size: 0.78rem;
  color: #94a3b8;
  text-align: right;
}

.profile-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.75rem;
}

.profile-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background: #f8fafc;
  border: 0.0625em solid #e9eef5;
  border-radius: 0.95em;
  padding: 0.85rem;
  min-width: 0;
}

.profile-item-wide {
  grid-column: 1 / -1;
}

.profile-icon {
  width: 2.4rem;
  height: 2.4rem;
  border-radius: 999px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 auto;
  background: #ffffff;
  border: 0.0625em solid #e6eaf0;
  box-shadow: 0 0.125em 0.4em rgba(15, 23, 42, 0.04);
  color: #334155;
  font-size: 1.05rem;
}

.profile-icon.blood {
  color: #ef4444;
}

.profile-icon.gender {
  color: #8b5cf6;
}

.profile-icon.phone {
  color: #0ea5e9;
}

.profile-icon.emergency {
  color: #f97316;
}

.profile-icon.date {
  color: #22c55e;
}

.profile-content {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.profile-content span {
  font-size: 0.76rem;
  color: #64748b;
  line-height: 1.1;
}

.profile-content strong {
  font-size: 0.95rem;
  color: #0f172a;
  font-weight: 700;
  line-height: 1.2;
  word-break: break-word;
}

.content-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: clamp(1rem, 1.8vw, 1.5rem);
  align-items: start;
  width: 100%;
  max-width: 96rem;
  margin: 0 auto;
}

.chart-section {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  align-items: stretch;
}

.odontogram-card {
  background: #ffffff;
  border-radius: 1em;
  padding: clamp(1rem, 1.8vw, 1.75rem);
  border: 0.0625em solid #f0f0f0;
  box-shadow: 0 0.125em 0.75em rgba(0, 0, 0, 0.04);
  min-width: 0;
}

.legend-panel {
  background: linear-gradient(180deg, #ffffff 0%, #fbfcfe 100%);
  border: 0.0625em solid #eef2f7;
  border-radius: 1em;
  padding: 1rem;
  margin-bottom: 1rem;
}

.legend-panel-header {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  justify-content: space-between;
  gap: 0.5rem 1rem;
  margin-bottom: 0.85rem;
}

.legend-panel-title {
  display: block;
  font-size: 0.95rem;
  font-weight: 700;
  color: #1f2937;
}

.legend-panel-subtitle {
  display: block;
  margin-top: 0.2rem;
  font-size: 0.8rem;
  color: #94a3b8;
}

.legend {
  display: flex;
  gap: 0.75em;
  flex-wrap: wrap;
  align-items: center;
}

.legend-hint {
  padding: 0.5em 1em;
  background: #fff7e6;
  border: 0.0625em solid #ffd591;
  border-radius: 1.5625em;
  color: #d48806;
  font-size: 0.875em;
}

.legend-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.625em;
  padding: 0.5em 1em;
  background: #fff;
  border: 0.125em solid #d9d9d9;
  border-radius: 1.5625em;
  cursor: pointer;
  font-size: 0.875em;
  transition: all 0.2s ease;
  user-select: none;
  flex: 0 0 auto;
  white-space: nowrap;
}

.legend-pill:hover {
  border-color: #40a9ff;
  transform: translateY(-0.0625em);
  box-shadow: 0 0.125em 0.5em rgba(0, 0, 0, 0.08);
}

.legend-pill.active {
  border-color: #1890ff;
  background: #e6f7ff;
  color: #1890ff;
  font-weight: 600;
  box-shadow: 0 0.125em 0.5em rgba(24, 144, 255, 0.18);
}

.dot {
  width: 0.75em;
  height: 0.75em;
  border-radius: 50%;
  border: 0.0625em solid rgba(0, 0, 0, 0.1);
  flex-shrink: 0;
}

.chart-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 100%;
  gap: 1rem;
}

.chart-label {
  font-size: 0.7em;
  color: #bfbfbf;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  line-height: 1;
}

.odontogram-shell {
  width: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  overflow-x: auto;
  overflow-y: hidden;
  padding: 0.75rem 0 0.5rem;
  scrollbar-gutter: stable both-edges;
  -webkit-overflow-scrolling: touch;
}

.print-controls {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 0.6em;
  margin-top: 0.25rem;
}

.print-controls button {
  padding: 0.45em 0.85em;
  border-radius: 0.55em;
  border: 0.0625em solid #d9d9d9;
  background: #ffffff;
  color: #262626;
  cursor: pointer;
  font: inherit;
  transition: all 0.2s ease;
}

.print-controls button:hover {
  border-color: #1890ff;
  color: #1890ff;
  box-shadow: 0 0.125em 0.5em rgba(24, 144, 255, 0.1);
}

.chart-divider {
  height: 0.0625em;
  margin-top: 1.25rem;
  background: linear-gradient(to right, transparent, #e8e8e8, transparent);
}

.treatment-plans {
  min-width: 0;
}

.treatment-card {
  width: 100%;
  border-radius: 1em;
  box-shadow: 0 0.125em 0.75em rgba(0, 0, 0, 0.04);
}

.treatment-card :deep(.n-card-header) {
  padding-bottom: 0.75rem;
}

.treatment-card :deep(.n-card-header__main) {
  font-size: 1.1rem;
  font-weight: 700;
  color: #1f2937;
}

.treatment-card :deep(.n-card-header__extra) {
  align-self: center;
}

.treatment-card :deep(.n-card__content) {
  padding-top: 0.9rem;
}

.treatment-card :deep(.n-list) {
  background: transparent;
}

.treatment-card :deep(.n-list-item) {
  padding-top: 0.9rem;
  padding-bottom: 0.9rem;
}

.treatment-card :deep(.n-list-item__suffix) {
  display: flex;
  align-items: center;
}

.plan-status-select {
  width: 7.5rem;
}

.empty-state-mini {
  padding: 1rem 0 0;
}

.voice-fab,
.voice-overlay {
  display: none;
}

@media (min-width: 75rem) {
  .odontogram-shell {
    justify-content: center;
  }
}

@media (max-width: 75rem) {
  .content-grid {
    grid-template-columns: 1fr;
  }

  .patient-header {
    grid-template-columns: 1fr;
  }

  .legend {
    justify-content: flex-start;
  }
}

@media (max-width: 48rem) {
  .view-patient-container {
    padding: 0.75rem;
  }

  .patient-info,
  .patient-profile-card,
  .odontogram-card,
  .treatment-card {
    border-radius: 0.85em;
  }

  .patient-header {
    gap: 0.9rem;
  }

  .patient-summary {
    gap: 0.5rem;
  }

  .summary-chip {
    font-size: 0.8rem;
  }

  .profile-grid {
    grid-template-columns: 1fr;
  }

  .profile-item-wide {
    grid-column: auto;
  }

  .legend {
    flex-wrap: nowrap;
    overflow-x: auto;
    overflow-y: hidden;
    justify-content: flex-start;
    padding-bottom: 0.25rem;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
  }

  .legend::-webkit-scrollbar {
    display: none;
  }

  .legend-pill {
    font-size: 0.82em;
  }

  .odontogram-card {
    padding: 1rem;
  }

  .chart-box {
    gap: 0.75rem;
  }

  .print-controls {
    width: 100%;
  }

  .print-controls button {
    flex: 1 1 10rem;
  }

  .treatment-plans {
    min-width: 0;
  }
}

@media (max-width: 30rem) {
  .patient-info h1 {
    font-size: 1.2rem;
  }

  .odontogram-shell {
    padding-inline: 0;
  }

  .print-controls button {
    flex: 1 1 100%;
  }
}

.treatment-board {
  width: 100%;
  border-radius: 1.15rem;
  background: linear-gradient(180deg, #ffffff 0%, #fbfcfe 100%);
  box-shadow: 0 0.125rem 0.9rem rgba(15, 23, 42, 0.06);
  border: 1px solid #eef2f7;
}

.treatment-board :deep(.n-card-header) {
  padding-bottom: 0.9rem;
}

.treatment-board :deep(.n-card__content) {
  padding-top: 1rem;
}

.prescription-section {
  margin-top: 1rem;
}

.prescription-board {
  width: 100%;
  border-radius: 1.15rem;
  background: linear-gradient(180deg, #ffffff 0%, #fbfcfe 100%);
  box-shadow: 0 0.125rem 0.9rem rgba(15, 23, 42, 0.06);
  border: 1px solid #eef2f7;
}

.prescription-board :deep(.n-card-header) {
  padding-bottom: 0.9rem;
}

.prescription-board :deep(.n-card__content) {
  padding-top: 1rem;
}

.prescription-board__controls {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 0.75rem;
  align-items: center;
}

.prescription-select {
  min-width: 0;
}

.prescription-print-host {
  position: fixed;
  inset: 0;
  z-index: -1;
  opacity: 0;
  pointer-events: none;
  overflow: auto;
  background: #fff;
}

.treatment-board__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
}

.treatment-board__title-block {
  min-width: 0;
}

.section-kicker {
  margin: 0 0 0.2rem;
  font-size: 0.76rem;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: #64748b;
}

.treatment-board__title-block h3 {
  margin: 0;
  font-size: clamp(1.05rem, 0.95rem + 0.5vw, 1.35rem);
  font-weight: 800;
  color: #0f172a;
  line-height: 1.2;
}

.section-subtitle {
  margin: 0.35rem 0 0;
  max-width: 52rem;
  color: #64748b;
  font-size: 0.92rem;
  line-height: 1.55;
}

.treatment-stats {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.stat-card {
  background: #f8fafc;
  border: 1px solid #e8eef5;
  border-radius: 1rem;
  padding: 0.9rem 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  min-width: 0;
}

.stat-label {
  font-size: 0.76rem;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.stat-card strong {
  font-size: 1.2rem;
  color: #0f172a;
  line-height: 1.15;
}

.plan-loading,
.empty-state-mini {
  padding: 1rem 0;
}

.plan-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.9rem;
}

.plan-card {
  border-radius: 1rem;
  border: 1px solid #edf1f6;
  box-shadow: 0 0.125rem 0.75rem rgba(15, 23, 42, 0.04);
  background: #fff;
}

.plan-card :deep(.n-card-header) {
  padding-bottom: 0.8rem;
}

.plan-card :deep(.n-card__content) {
  padding-top: 0.9rem;
}

.plan-card__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.75rem;
}

.plan-card__heading {
  min-width: 0;
}

.plan-card__heading h4 {
  margin: 0;
  font-size: 1rem;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.25;
}

.plan-card__heading p {
  margin: 0.25rem 0 0;
  font-size: 0.82rem;
  color: #64748b;
}

.plan-card__body {
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
}

.plan-pill-row {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.65rem;
}

.plan-pill {
  border: 1px solid #e7edf4;
  background: #f8fafc;
  border-radius: 0.95rem;
  padding: 0.75rem 0.85rem;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.plan-pill__label {
  font-size: 0.72rem;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  color: #64748b;
}

.plan-pill__value {
  font-size: 0.92rem;
  font-weight: 700;
  color: #0f172a;
  word-break: break-word;
}

.billing-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.65rem;
}

.billing-box {
  border: 1px solid #e7edf4;
  background: #ffffff;
  border-radius: 0.95rem;
  padding: 0.8rem;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.billing-box--accent {
  background: linear-gradient(180deg, #eff6ff 0%, #ffffff 100%);
  border-color: #bfdbfe;
}

.billing-box__label {
  font-size: 0.72rem;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  color: #64748b;
}

.billing-box__value {
  font-size: 1rem;
  color: #0f172a;
  word-break: break-word;
}

.plan-note {
  display: flex;
  align-items: center;
  gap: 0.55rem;
  padding: 0.75rem 0.9rem;
  border-radius: 0.95rem;
  background: #f8fafc;
  border: 1px solid #e7edf4;
  color: #475569;
  font-size: 0.9rem;
}

.linked-appointments {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
  /* margin-bottom: .4em; */
  /* min-width: 100vw; */
}

.linked-appointment-row {
  border: 1px solid #e7edf4;
  border-radius: 0.7rem;
  padding: 0.55rem 0.7rem;
  background: #ffffff;
  font-size: 0.82rem;
  color: #334155;
}

.linked-appointment-row .appp {
  display: flex;
  justify-content: space-between;
}

.linked-appointment-row .clinical-notes {
  margin: .4em 0;
  margin-top: .6em;
  color: #000;
  max-width: 700px;
}

.linked-appointment-row .cost {
  margin-top: .2em;
}

/* .linked-appointment-row */

.plan-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.55rem;
  flex-wrap: wrap;
}

@media (max-width: 75rem) {
  .treatment-stats {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .plan-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 48rem) {
  .treatment-board__header {
    flex-direction: column;
    align-items: stretch;
  }

  .treatment-stats {
    grid-template-columns: 1fr;
  }

  .plan-pill-row,
  .billing-grid {
    grid-template-columns: 1fr;
  }

  .plan-actions {
    justify-content: stretch;
  }

  .plan-actions :deep(.n-button) {
    flex: 1 1 auto;
  }

  .prescription-board__controls {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 75rem) {
  .plan-meta-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .plan-actions {
    min-width: 100%;
    justify-content: flex-start;
  }
}

@media (max-width: 48rem) {
  .plan-meta-grid {
    grid-template-columns: 1fr;
  }

  .plan-meta-wide {
    grid-column: auto;
  }

  .plan-actions {
    width: 100%;
  }

  .plan-status-select {
    width: 100%;
  }
}
</style>

<style>
@media print {

  html,
  body {
    margin: 0 !important;
    padding: 0 !important;
  }

  #app>* {
    visibility: hidden !important;
  }

  .prescription-print-host,
  .prescription-print-host * {
    visibility: visible !important;
  }

  .prescription-print-host {
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: #fff;
  }
}
</style>
