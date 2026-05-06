<script lang="ts" setup>
import { useMessage } from 'naive-ui'
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Odontogram from '@/components/Odontogram.vue'
import PrimaryOdontogram from '@/components/PrimaryOdontogram.vue'
import { predict } from '@/utils/voiceInference'
import PatientService from '@api/patient'
import OdontogramService from '@api/odontogram'
import type PatientData from '@api/interfaces/Patient'
import type { ConditionLibrary } from '@api/interfaces/Odontogram'
import TreatmentPlanApi from '@api/treatmentPlan'
import type TreatmentData from '@api/interfaces/Treatment'
import AddTreatmentPlanModal from '@/components/AddTreatmentPlanModal.vue'

import EditTreatment from '@/components/EditTreatment.vue'
import procedureApi from '@api/procedure'
import {
  NCard,
  NButton,
  NList,
  NListItem,
  NThing,
  NIcon,
  NSpace,
  NSelect,
  NPopconfirm,
  NEmpty,
  NTag
} from 'naive-ui'
import { Icon } from '@iconify/vue'

type VoiceStep = 'tooth' | 'surface' | 'condition'
type SlotCondition = {
  color: string
  id: string
}
type OdontogramState = Record<number, Record<string, any>>

const route = useRoute()
const patientId = computed(() => Number(route.params.id))

const isRecording = ref(false)
const currentStep = ref<VoiceStep>('tooth')
const loading = ref(false)

const patient = ref<(PatientData & { f_name: string; l_name: string }) | null>(null)
const conditionLibrary = ref<ConditionLibrary[]>([])
const activeFinding = ref<ConditionLibrary | null>(null)

const odontogramData = ref<OdontogramState>({})
const clinicalNotes = ref('')

const selectedTooth = ref<number | null>(null)
const selectedSurface = ref<string | null>(null)
const selectedCondition = ref<string | null>(null)

const message = useMessage()
const MAX_RETRIES = 3
const CONF_THRESHOLD = 0.3

let retryCount = 0
let mediaRecorder: MediaRecorder | null = null
let audioChunks: Blob[] = []

const successAudio = new Audio('/success.mp3')
const errorAudio = new Audio('/error.mp3')

// treatment plan state
const treatmentPlans = ref<any[]>([])
const procedures = ref<any[]>([])
const isPlanModalVisible = ref(false)
const planLoading = ref(false)

const isEditTreatmentVisible = ref(false)
const editingPlan = ref<any | null>(null)
const editPlanLoading = ref(false)


const router = useRouter()

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
      return { type: 'success' as const, label: 'Accepted' }
    case 'proposed':
      return { type: 'warning' as const, label: 'Proposed' }
    case 'completed':
      return { type: 'info' as const, label: 'Completed' }
    case 'cancelled':
    case 'rejected':
      return { type: 'error' as const, label: 'Cancelled' }
    default:
      return { type: 'default' as const, label: status || 'Unknown' }
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
  router.push({
    path: '/appointments/create',
    query: {
      patient_id: String(patientId.value),
      treatment_plan_id: String(plan.id),
      procedure_id: String(plan.procedure_id),
      branch_id: String(plan.branch_id),
    },
  })
}

function formatDate(dateValue: string | number | null | undefined) {
  if (!dateValue) return '—'
  const d = new Date(dateValue)
  if (Number.isNaN(d.getTime())) return '—'
  return d.toLocaleDateString()
}

// function formatMoney(value: any) {
//   if (value === null || value === undefined || value === '') return '—'
//   return `${value} AFN`
// }

async function fetchTreatmentData() {
  if (!patientId.value) return
  planLoading.value = true
  try {
    const [plansRes, procRes] = await Promise.all([
      TreatmentPlanApi.getBranchTreatmentPlans(patientId.value),
      procedureApi.getProcedures()
    ])

    treatmentPlans.value = plansRes.data?.data ?? plansRes.data ?? []
    procedures.value = procRes.data?.data ?? procRes.data ?? []
  } catch (err) {
    message.error('Failed to load treatment plans')
  } finally {
    planLoading.value = false
  }
}

async function proposePlan(procedureId: number, cost: number) {
  try {
    const payload = {
      patient_id: patientId.value,
      appointment_id: 1,
      procedure_id: procedureId,
      total_estimated_cost: cost,
      status: 'proposed',
      total_amount_paid: null,
      duration: null,
      start_date: new Date().toISOString().slice(0, 10)
    }
    await TreatmentPlanApi.postTreatmentPlan(payload)
    message.success('New plan proposed')
    await fetchTreatmentData()
  } catch (err) {
    message.error('Failed to propose plan')
  }
}

async function updatePlanStatus(planId: number, newStatus: string) {
  try {
    await TreatmentPlanApi.updateStatus(planId, { status: newStatus })
    message.success(`Plan marked as ${newStatus}`)
    await fetchTreatmentData()
  } catch (err) {
    message.error('Update failed')
  }
}

async function deletePlan(planId: number) {
  try {
    await TreatmentPlanApi.deleteTreatmentPlan(planId)
    message.success('Plan deleted')
    await fetchTreatmentData()
  } catch (err) {
    message.error('Delete failed')
  }
}

function openEditTreatment(plan: any) {
  editingPlan.value = { ...plan }
  isEditTreatmentVisible.value = true
}

async function saveEditedTreatment(payload: any) {
  if (!editingPlan.value?.id) return
  editPlanLoading.value = true

  try {
    // Adjust this one line only if your API file uses a different method name.
    await TreatmentPlanApi.updateTreatmentPlan(editingPlan.value.id, payload)
    message.success('Treatment updated')
    isEditTreatmentVisible.value = false
    editingPlan.value = null
    await fetchTreatmentData()
  } catch (err) {
    message.error('Failed to update treatment')
  } finally {
    editPlanLoading.value = false
  }
}
// --- Fetch Treatment Plans and Procedures ---
// async function fetchTreatmentData() {
//   if (!patientId.value) return;
//   planLoading.value = true;
//   try {
//     console.log('Patient Id: ', patientId.value)
//     const [plansRes, procRes] = await Promise.all([
//       TreatmentPlanApi.getBranchTreatmentPlans(patientId.value),
//       procedureApi.getProcedures()
//     ]);
//     treatmentPlans.value = plansRes.data?.data ?? plansRes.data;
//     procedures.value = procRes.data?.data ?? procRes.data;
//   } catch (err) {
//     message.error('Failed to load treatment plans');
//   } finally {
//     planLoading.value = false;
//   }
// }

// // --- Action: Propose a New Plan ---
// async function proposePlan(procedureId: number, cost: number) {
//   try {
//     const payload = {
//       patient_id: patientId.value,
//       appointment_id: 1, // Defaulting to current, ideally dynamic
//       procedure_id: procedureId,
//       total_estimated_cost: cost,
//       status: 'proposed'
//     };
//     await TreatmentPlanApi.postTreatmentPlan(payload);
//     message.success('New plan proposed');
//     await fetchTreatmentData();
//   } catch (err) {
//     message.error('Failed to propose plan');
//   }
// }

// // --- Action: Update Plan Status (Acceptance) ---
// async function updatePlanStatus(planId: number, newStatus: string) {
//   try {
//     await TreatmentPlanApi.updateStatus(planId, { status: newStatus });
//     message.success(`Plan marked as ${newStatus}`);
//     await fetchTreatmentData();
//   } catch (err) {
//     message.error('Update failed');
//   }
// }

// // --- Action: Remove a Plan ---
// async function deletePlan(planId: number) {
//   try {
//     await TreatmentPlanApi.deleteTreatmentPlan(planId);
//     message.success('Plan deleted');
//     await fetchTreatmentData();
//   } catch (err) {
//     message.error('Delete failed');
//   }
// }




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

        // 🟣 FORCE SYMBOL
        if (isSymbol) {
          state[fdi].symbols!.push({
            id: cond.id,
            condition_id: lib.id,
            slug: lib.slug,
            svg: lib.svg_path,
            color: lib.ui_color
          })

          return // 🚨 DO NOT FALL THROUGH
        }

        // 🟡 SURFACE
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

    // After refresh, default to no tool selected.
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
    // Important: do NOT auto-select a tool on load.
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

        // SYMBOL
        if (typeof svg === 'string' && svg.includes('<svg')) {
          state[fdi].symbols.push({
            id: cond.id, // DB UUID (for delete)
            condition_id: cond.condition_library.id, // for matching tool
            svg: cond.condition_library.svg_path,
            slug: cond.condition_library.slug,
            color: cond.condition_library.ui_color
          })
          return
        }

        // SURFACE
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
    message.error('Invalid condition')
    return
  }

  // Optional but useful: clicking the same condition again deselects it.
  if (activeFinding.value?.id === condition.id) {
    activeFinding.value = null
    message.info('Tool deselected')
    return
  }

  activeFinding.value = condition
  message.success(`Selected: ${condition.label}`)
}

function mapSurfaceToPart(surface: string, tooth: number): string {
  const quadrant = Math.floor(tooth / 10)
  const buccalIsRight = quadrant === 1 || quadrant === 4

  switch (surface) {
    case 'buccal':
      return buccalIsRight ? 'right' : 'left'
    case 'lingual':
    case 'palatal':
      return buccalIsRight ? 'left' : 'right'
    case 'occlusal':
      return 'top'
    case 'mesial':
      return 'left'
    case 'distal':
      return 'right'
    default:
      return 'center'
  }
}

function normalizeTooth(label: string): number | null {
  const map: Record<string, number> = {
    eleven: 11,
    twelve: 12,
    thirteen: 13,
    fourteen: 14,
    fifteen: 15,
    sixteen: 16,
    seventeen: 17,
    eighteen: 18,
    'twenty one': 21,
    'twenty two': 22,
    'twenty three': 23,
    'twenty four': 24,
    'twenty five': 25,
    'twenty six': 26,
    'twenty seven': 27,
    'twenty eight': 28,
    'thirty one': 31,
    'thirty two': 32,
    'thirty three': 33,
    'thirty four': 34,
    'thirty five': 35,
    'thirty six': 36,
    'thirty seven': 37,
    'thirty eight': 38,
    'forty one': 41,
    'forty two': 42,
    'forty three': 43,
    'forty four': 44,
    'forty five': 45,
    'forty six': 46,
    'forty seven': 47,
    'forty eight': 48,
  }

  const normalized = label.trim().toLowerCase()
  if (map[normalized]) return map[normalized]

  const num = parseInt(normalized, 10)
  return Number.isNaN(num) ? null : num
}

async function recordOnce(): Promise<Blob> {
  const stream = await navigator.mediaDevices.getUserMedia({ audio: true })

  return new Promise((resolve) => {
    mediaRecorder = new MediaRecorder(stream)
    audioChunks = []

    mediaRecorder.ondataavailable = (e) => audioChunks.push(e.data)
    mediaRecorder.onstop = () => {
      const blob = new Blob(audioChunks, { type: 'audio/webm' })
      stream.getTracks().forEach((t) => t.stop())
      resolve(blob)
    }

    mediaRecorder.start()
    setTimeout(() => mediaRecorder?.stop(), 2000)
  })
}

async function runStep(step: VoiceStep): Promise<boolean> {
  const msg = message.loading(`Listening for ${step}...`, { duration: 0 })
  const blob = await recordOnce()
  msg.destroy()

  const res = await predict(blob, step)

  if (!res || !res.probabilities || res.probabilities.length === 0) {
    errorAudio.play()
    message.error(`Could not detect ${step}`)
    return false
  }

  const sorted = [...res.probabilities].sort((a, b) => b - a)
  const confidence = sorted[0] - (sorted[1] ?? 0)

  if (confidence < CONF_THRESHOLD) {
    errorAudio.play()
    message.warning(`Unclear ${step}, try again`)
    return false
  }

  successAudio.play()

  if (step === 'tooth') {
    const tooth = normalizeTooth(res.label)
    if (!tooth) return false
    selectedTooth.value = tooth
    message.success(`Tooth ${tooth}`)
  }

  if (step === 'surface') {
    selectedSurface.value = res.label
    message.success(`Surface ${res.label}`)
  }

  if (step === 'condition') {
    selectedCondition.value = res.label
    message.success(`Condition ${res.label}`)
  }

  return true
}

async function handleToothClick(toothFdi: number, surface: string) {
  console.log(patient.value)
  if (!patientId.value) return

  const surfKey = surface.toLowerCase()
  const toothState = odontogramData.value[toothFdi] || {}

  const existingSurface = toothState[surfKey]
  const symbols = toothState.symbols || []

  const isSymbolTool = !!activeFinding.value?.svg_path

  // =========================
  // 🔴 DELETE FIRST (ALWAYS PRIORITY)
  // =========================

  // 🟣 delete symbol (if symbol tool OR no tool)
  if (symbols.length > 0 && (!activeFinding.value || isSymbolTool)) {
    try {
      const symbolToDelete = symbols[0] // (or refine later if multiple)

      odontogramData.value[toothFdi].symbols =
        symbols.filter((s: any) => s.id !== symbolToDelete.id)

      await OdontogramService.deleteToothCondition(symbolToDelete.id)

      message.success('Condition removed')
      await refreshOdontogram()
      return
    } catch (err) {
      console.error('Symbol delete failed', err)
      message.error('Failed to remove condition')
      await refreshOdontogram()
      return
    }
  }

  // 🟡 delete surface
  if (existingSurface?.id) {
    try {
      clearLocalSlot(toothFdi, surfKey)

      await OdontogramService.deleteToothCondition(existingSurface.id)

      message.success('Condition removed')
      await refreshOdontogram()
      return
    } catch (err) {
      console.error('Surface delete failed', err)
      message.error('Failed to remove condition')
      await refreshOdontogram()
      return
    }
  }

  // =========================
  // ➕ ADD (ONLY IF TOOL SELECTED)
  // =========================

  if (!activeFinding.value?.id) {
    message.warning('Select a condition first')
    return
  }

  // 🟣 SYMBOL ADD
  if (isSymbolTool) {
    try {
      const payload = {
        tooth_id: toothFdi,
        condition_id: activeFinding.value.id,
        surfaces: ['CENTER'] // temp backend workaround
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

      message.success('Condition saved')
      await refreshOdontogram()
    } catch (err) {
      console.error('Symbol save failed', err)
      message.error('Failed to save condition')
      await refreshOdontogram()
    }

    return
  }

  // 🟡 SURFACE ADD
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

    message.success('Condition saved')
    await refreshOdontogram()
  } catch (err) {
    console.error('Surface save failed', err)
    message.error('Failed to save condition')
    await refreshOdontogram()
  }
}


async function startVoiceWorkflow() {
  if (isRecording.value) return
  if (!patient.value || !odontogramData.value) {
    message.warning('Please wait for patient data to load')
    return
  }

  isRecording.value = true
  retryCount = 0
  selectedTooth.value = null
  selectedSurface.value = null
  selectedCondition.value = null

  try {
    while (!(await runStep('tooth'))) {
      if (++retryCount >= MAX_RETRIES) throw new Error('Tooth failed')
    }

    retryCount = 0
    while (!(await runStep('surface'))) {
      if (++retryCount >= MAX_RETRIES) throw new Error('Surface failed')
    }

    retryCount = 0
    while (!(await runStep('condition'))) {
      if (++retryCount >= MAX_RETRIES) throw new Error('Condition failed')
    }

    const tooth = selectedTooth.value!
    const surface = mapSurfaceToPart(selectedSurface.value!, tooth)
    const conditionLabel = selectedCondition.value!

    const found = conditionLibrary.value?.find(
      (c) => c.label.toLowerCase() === conditionLabel.toLowerCase(),
    )

    if (found) {
      activeFinding.value = found
    } else {
      message.warning('Condition not found in library')
      return
    }

    await handleToothClick(tooth, surface)
    message.success('Applied successfully')
  } catch (e) {
    message.error('Voice workflow cancelled')
  } finally {
    isRecording.value = false
  }
}

onMounted(async () => {
  await loadPatientData();
  await fetchTreatmentData();
});

</script>

<template>
  <div class="view-patient-container">
    <div v-if="loading" class="loading-state">Loading Dental Records...</div>

    <template v-else-if="patient">
      <header class="patient-header">
        <div class="patient-info">
          <div class="patient-name-block">
            <h1>{{ patient.fName || patient.f_name }} {{ patient.lName || patient.l_name }}</h1>
            <span class="patient-badge">Patient ID: {{ patient.id }}</span>
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
              <span>Patient Profile</span>
            </div>
            <span class="profile-card-subtitle">Lightweight patient details</span>
          </div>

          <div class="profile-grid">
            <div class="profile-item">
              <div class="profile-icon blood">
                <Icon icon="healthicons:blood-ab-p" />
              </div>
              <div class="profile-content">
                <span>Blood Type</span>
                <strong>{{ patient.bloodType || '—' }}</strong>
              </div>
            </div>

            <div class="profile-item">
              <div class="profile-icon gender">
                <Icon icon="mdi:gender-male-female" />
              </div>
              <div class="profile-content">
                <span>Gender</span>
                <strong>{{ patient.gender || '—' }}</strong>
              </div>
            </div>

            <div class="profile-item">
              <div class="profile-icon phone">
                <Icon icon="tabler:phone" />
              </div>
              <div class="profile-content">
                <span>Phone</span>
                <strong>{{ patient.phone || '—' }}</strong>
              </div>
            </div>

            <div class="profile-item">
              <div class="profile-icon emergency">
                <Icon icon="mdi:ambulance" />
              </div>
              <div class="profile-content">
                <span>Emergency Contact</span>
                <strong>{{ patient.emgContact || '—' }}</strong>
              </div>
            </div>

            <div class="profile-item profile-item-wide">
              <div class="profile-icon date">
                <Icon icon="tabler:calendar-stats" />
              </div>
              <div class="profile-content">
                <span>Registration Date</span>
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
                  <span class="legend-panel-title">Tooth Conditions</span>
                  <span class="legend-panel-subtitle">Select a condition, then tap a tooth</span>
                </div>
              </div>

              <div class="legend">
                <div v-if="conditionLibrary.length === 0" class="legend-hint">
                  No conditions available
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
              <span class="chart-label">Permanent Teeth</span>

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

            <!--
            <div class="chart-box primary-area">
              <span class="chart-label">Primary Teeth</span>
              <PrimaryOdontogram
                v-model="odontogramData"
                ref="primaryOdontogramRef"
                :active-finding="activeFinding?.ui_color || '#ffffff'"
                @tooth-click="handleToothClick"
              />
            </div>
            -->
          </div>

          <section class="treatment-plans">
            <n-card class="treatment-board" :segmented="{ content: true }">
              <template #header>
                <div class="treatment-board__header">
                  <div class="treatment-board__title-block">
                    <p class="section-kicker">Clinical planning</p>
                    <h3>Treatment Plans</h3>
                    <p class="section-subtitle">
                      Review the patient’s active and historical plans, then create a new appointment directly from the
                      plan you want to continue.
                    </p>
                  </div>

                  <n-space align="center" :wrap="false">
                    <n-button type="primary" size="small" @click="isPlanModalVisible = true">
                      <template #icon>
                        <Icon icon="material-symbols:add-notes-outline" />
                      </template>
                      Propose New Plan
                    </n-button>
                  </n-space>
                </div>
              </template>

              <div class="treatment-stats">
                <div class="stat-card">
                  <span class="stat-label">Plans</span>
                  <strong>{{ treatmentPlanStats.total }}</strong>
                </div>

                <div class="stat-card">
                  <span class="stat-label">Accepted</span>
                  <strong>{{ treatmentPlanStats.accepted }}</strong>
                </div>

                <div class="stat-card">
                  <span class="stat-label">Proposed</span>
                  <strong>{{ treatmentPlanStats.proposed }}</strong>
                </div>

                <div class="stat-card">
                  <span class="stat-label">Outstanding</span>
                  <strong>{{ formatMoney(treatmentPlanStats.outstanding) }}</strong>
                </div>
              </div>

              <n-divider />

              <div v-if="planLoading" class="plan-loading">
                Loading treatment plans...
              </div>

              <div v-else-if="treatmentPlans.length === 0" class="empty-state-mini">
                <n-empty description="No treatment plans yet" />
              </div>

              <div v-else class="plan-grid">
                <n-card v-for="plan in treatmentPlans" :key="plan.id" style="min-width: 100% !important;" class="plan-card" size="small" :bordered="true">
                  <template #header>
                    <div class="plan-card__header">
                      <div class="plan-card__heading">
                        <h4>{{ plan.procedure?.name || `Procedure #${plan.procedure_id}` }}</h4>
                        <p>{{ plan.procedure?.category || 'Uncategorized' }}</p>
                      </div>

                      <n-tag round size="small" :type="getStatusMeta(plan.status).type">
                        {{ getStatusMeta(plan.status).label }}
                      </n-tag>
                    </div>
                  </template>

                  <div class="plan-card__body">
                    <div class="plan-pill-row">
                      <div class="plan-pill">
                        <span class="plan-pill__label">Start date</span>
                        <span class="plan-pill__value">{{ formatDate(plan.start_date) }}</span>
                      </div>

                      <div class="plan-pill">
                        <span class="plan-pill__label">Duration</span>
                        <span class="plan-pill__value">{{ plan.duration ?? '—' }} min</span>
                      </div>

                      <div class="plan-pill">
                        <span class="plan-pill__label">Accepted</span>
                        <span class="plan-pill__value">
                          {{ plan.is_accepted ? 'Yes' : 'No' }}
                        </span>
                      </div>
                    </div>

                    <div class="billing-grid">
                      <div class="billing-box">
                        <span class="billing-box__label">Estimated cost</span>
                        <strong class="billing-box__value">
                          {{ formatMoney(plan.total_estimated_cost) }}
                        </strong>
                      </div>

                      <div class="billing-box">
                        <span class="billing-box__label">Paid</span>
                        <strong class="billing-box__value">
                          {{ formatMoney(plan.total_amount_paid) }}
                        </strong>
                      </div>

                      <div class="billing-box billing-box--accent">
                        <span class="billing-box__label">Remaining</span>
                        <strong class="billing-box__value">
                          {{ formatMoney(remainingBalance(plan)) }}
                        </strong>
                      </div>
                    </div>

                    <div class="plan-note">
                      <Icon icon="ph:stethoscope-light" />
                      <span>
                        {{ plan.is_accepted ? 'Ready for scheduling and execution.' : 'Pending approval or clinical review.' }}
                      </span>
                    </div>
                  </div>

                  <template #footer>
                    <div class="plan-actions">
                      <n-button size="small" tertiary @click.stop="openEditTreatment(plan)">
                        <template #icon>
                          <Icon icon="tabler:pencil" />
                        </template>
                        Edit
                      </n-button>

                      <n-button size="small" type="primary" @click.stop="createAppointment(plan)">
                        <template #icon>
                          <Icon icon="mdi:calendar-plus" />
                        </template>
                        Add Appointment
                      </n-button>

                      <n-popconfirm @positive-click="deletePlan(plan.id)">
                        <template #trigger>
                          <n-button type="error" ghost size="small" @click.stop>
                            <template #icon>
                              <Icon icon="tabler:trash" />
                            </template>
                          </n-button>
                        </template>
                        Delete this plan?
                      </n-popconfirm>
                    </div>
                  </template>
                </n-card>
              </div>
            </n-card>
          </section>
        </section>

        <EditTreatment v-model:show="isEditTreatmentVisible" :plan="editingPlan" :procedures="procedures"
          :loading="editPlanLoading" @save="saveEditedTreatment" />

        <aside class="notes-section">
          <div class="card notes-card">
            <h3>Clinical Notes</h3>
            <textarea v-model="clinicalNotes" placeholder="Enter patient notes..."></textarea>
          </div>
        </aside>

        <AddTreatmentPlanModal v-model:show="isPlanModalVisible" :patient-id="patientId" :appointment-id="1"
          @success="fetchTreatmentData" />
      </main>
    </template>

    <div v-else-if="!loading" class="empty-state">
      <p>No patient data available</p>
    </div>
  </div>

  <div v-if="isRecording" class="voice-overlay"></div>
  <button v-if="patient && !isRecording" class="voice-fab" @click="startVoiceWorkflow">🎤</button>
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
  grid-template-columns: minmax(0, 1.85fr) minmax(18rem, 24rem);
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

.notes-section {
  min-width: 0;
  align-self: start;
  position: sticky;
  top: 1rem;
}

.notes-card {
  background: #ffffff;
  border-radius: 1em;
  border: 0.0625em solid #f0f0f0;
  box-shadow: 0 0.125em 0.75em rgba(0, 0, 0, 0.04);
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  min-height: calc(100vh - 8rem);
}

.notes-card h3 {
  margin: 0;
  font-size: 1.05rem;
  color: #262626;
}

textarea {
  width: 100%;
  flex: 1;
  min-height: 24rem;
  border: 0.0625em solid #d9d9d9;
  border-radius: 0.75em;
  padding: 1em;
  resize: vertical;
  line-height: 1.6;
  font-family: inherit;
  box-sizing: border-box;
  background: #ffffff;
}

textarea:focus {
  outline: none;
  border-color: #40a9ff;
  box-shadow: 0 0 0 0.125em rgba(24, 144, 255, 0.2);
}

.voice-fab {
  position: fixed;
  bottom: 1.75rem;
  right: 1.75rem;
  width: 4rem;
  height: 4rem;
  border-radius: 50%;
  background: #22c55e;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.6rem;
  color: white;
  cursor: pointer;
  box-shadow: 0 0.5rem 1.25rem rgba(0, 0, 0, 0.2);
  transition: transform 0.2s ease;
  z-index: 1000;
}

.voice-fab:hover {
  transform: scale(1.08);
}

.voice-fab:active {
  transform: scale(0.96);
}

.voice-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 999;
  backdrop-filter: blur(0.125rem);
}

@media (min-width: 75rem) {
  .odontogram-shell {
    justify-content: center;
  }

  .notes-card {
    position: sticky;
    top: 1rem;
  }
}

@media (max-width: 75rem) {
  .content-grid {
    grid-template-columns: 1fr;
  }

  .notes-section {
    position: static;
    top: auto;
  }

  .notes-card {
    min-height: 20rem;
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
  .notes-card,
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

  .notes-card {
    min-height: auto;
    padding: 1rem;
  }

  textarea {
    min-height: 18rem;
  }

  .voice-fab {
    width: 3.5rem;
    height: 3.5rem;
    bottom: 1rem;
    right: 1rem;
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
.treatment-plans {
  min-width: 0;
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
  grid-template-columns: repeat(2, minmax(0, 1fr));
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

  /* Hide everything by default */
  #app>* {
    visibility: hidden !important;
  }
}
</style>
