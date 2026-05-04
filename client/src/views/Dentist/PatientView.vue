<script lang="ts" setup>
import { useMessage } from 'naive-ui'
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import Odontogram from '@/components/Odontogram.vue'
import PrimaryOdontogram from '@/components/PrimaryOdontogram.vue'
import { predict } from '@/utils/voiceInference'
import PatientService from '@api/patient'
import OdontogramService from '@api/odontogram'
import type PatientData from '@api/interfaces/Patient'
import type {
  ConditionLibrary,
} from '@api/interfaces/Odontogram'
import TreatmentPlanApi from '@api/treatmentPlan';
import type TreatmentData from '@api/interfaces/Treatment'
import AddTreatmentPlanModal from '@/components/AddTreatmentPlanModal.vue'
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
  NEmpty
} from 'naive-ui'
import { Icon } from '@iconify/vue'

type VoiceStep = 'tooth' | 'surface' | 'condition'


type SlotCondition = {
  color: string
  id: string
}

type OdontogramState = Record<number, Record<string, SlotCondition>>

const route = useRoute()
const patientId = computed(() => Number(route.params.id))

const isRecording = ref(false)
const currentStep = ref<VoiceStep>('tooth')
const loading = ref(false)

const patient = ref<(PatientData & { f_name: string; l_name: string }) | null>(null)
const conditionLibrary = ref<ConditionLibrary[]>([])
const activeFinding = ref<ConditionLibrary | null>(null)

// Backend-friendly local state:
// { 11: { buccal: { color: '#ff0000', id: 'uuid' } } }
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

import procedureApi from '@api/procedure'; // Import the procedure API

// --- New Reactive State for Treatment Plans ---
const treatmentPlans = ref<any[]>([]);
const procedures = ref<any[]>([]);
const isPlanModalVisible = ref(false);
const planLoading = ref(false);

// --- Fetch Treatment Plans and Procedures ---
async function fetchTreatmentData() {
  if (!patientId.value) return;
  planLoading.value = true;
  try {
    console.log('Patient Id: ', patientId.value)
    const [plansRes, procRes] = await Promise.all([
      TreatmentPlanApi.getBranchTreatmentPlans(patientId.value),
      procedureApi.getProcedures()
    ]);
    treatmentPlans.value = plansRes.data?.data ?? plansRes.data;
    procedures.value = procRes.data?.data ?? procRes.data;
  } catch (err) {
    message.error('Failed to load treatment plans');
  } finally {
    planLoading.value = false;
  }
}

// --- Action: Propose a New Plan ---
async function proposePlan(procedureId: number, cost: number) {
  try {
    const payload = {
      patient_id: patientId.value,
      appointment_id: 1, // Defaulting to current, ideally dynamic
      procedure_id: procedureId,
      total_estimated_cost: cost,
      status: 'proposed'
    };
    await TreatmentPlanApi.postTreatmentPlan(payload);
    message.success('New plan proposed');
    await fetchTreatmentData();
  } catch (err) {
    message.error('Failed to propose plan');
  }
}

// --- Action: Update Plan Status (Acceptance) ---
async function updatePlanStatus(planId: number, newStatus: string) {
  try {
    await TreatmentPlanApi.updateStatus(planId, { status: newStatus });
    message.success(`Plan marked as ${newStatus}`);
    await fetchTreatmentData();
  } catch (err) {
    message.error('Update failed');
  }
}

// --- Action: Remove a Plan ---
async function deletePlan(planId: number) {
  try {
    await TreatmentPlanApi.deleteTreatmentPlan(planId);
    message.success('Plan deleted');
    await fetchTreatmentData();
  } catch (err) {
    message.error('Delete failed');
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
          <h1>{{ patient.f_name }} {{ patient.l_name }}</h1>
          <span class="patient-badge">Patient ID: {{ patient.id }}</span>
        </div>

        <div class="legend">
          <div v-if="conditionLibrary.length === 0" class="legend-hint">
            No conditions available
          </div>

          <div v-for="(f, idx) in conditionLibrary" :key="f.id || idx" class="legend-pill"
            :class="{ active: activeFinding?.id === f.id }" @click="selectCondition(f)">
            <!-- {{ console.log(f) }} -->
            <span class="dot"
              :style="{ backgroundColor: f.svg_icon_path || f.svg_path ? '#ffffff' : f.ui_color }"></span>
            {{ f.label }}
          </div>
        </div>
      </header>

      <main class="content-grid">
        <section class="chart-section">
          <div class="odontogram-card">
            <div class="chart-box">
              <span class="chart-label">Permanent Teeth</span>
              <Odontogram v-model="odontogramData" :slug="activeFinding?.slug || ''"
                :active-finding="activeFinding?.ui_color || '#ffffff'" @tooth-click="handleToothClick" />
            </div>

            <div class="chart-divider"></div>

            <div class="chart-box primary-area">
              <span class="chart-label">Primary Teeth</span>
              <PrimaryOdontogram v-model="odontogramData" :active-finding="activeFinding?.ui_color || '#ffffff'"
                @tooth-click="handleToothClick" />
            </div>
          </div>
          <section class="treatment-plans">
            <n-card title="Clinical Treatment Plans" :segmented="{ content: true }">
              <template #header-extra>
                <n-button type="primary" size="small" @click="isPlanModalVisible = true">
                  <template #icon>
                    <Icon icon="material-symbols:add-notes-outline" />
                  </template>
                  Propose New Plan
                </n-button>
              </template>

              <n-list hoverable clickable>
                <n-list-item v-for="plan in treatmentPlans" :key="plan.id">
                  <template #prefix>
                    <n-icon size="24" :color="plan.status === 'accepted' ? '#18a058' : '#f0a020'">
                      <Icon
                        :icon="plan.status === 'accepted' ? 'fluent:checkmark-circle-24-filled' : 'fluent:clock-24-regular'" />
                    </n-icon>
                  </template>

                  <n-thing :title="plan.procedure?.name" :description="`${plan.total_estimated_cost} AFN`" />

                  <template #suffix>
                    <n-space>
                      <!-- Status Toggle -->
                      <n-select v-model:value="plan.status" size="small" style="width: 120px" :options="[
                        { label: 'Proposed', value: 'proposed' },
                        { label: 'Accepted', value: 'accepted' },
                        { label: 'Rejected', value: 'rejected' }
                      ]" @update:value="(val) => updatePlanStatus(plan.id, val)" />

                      <!-- Delete -->
                      <n-popconfirm @positive-click="deletePlan(plan.id)">
                        <template #trigger>
                          <n-button type="error" ghost size="small">
                            <template #icon>
                              <Icon icon="tabler:trash" />
                            </template>
                          </n-button>
                        </template>
                        Delete this proposal?
                      </n-popconfirm>
                    </n-space>
                  </template>
                </n-list-item>
              </n-list>

              <div v-if="treatmentPlans.length === 0" class="empty-state-mini">
                <n-empty description="No plans proposed yet" />
              </div>
            </n-card>
          </section>



        </section>

        <section class="notes-section">
          <div class="card">
            <h3>Clinical Notes</h3>
            <textarea v-model="clinicalNotes" placeholder="Enter patient notes..."></textarea>
          </div>
        </section>

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

<style scoped lang="scss">
.view-patient-container {
  padding: 30px;
  background: #fafafa;
  min-height: 100vh;
  position: relative;
}

.loading-state,
.empty-state {
  text-align: center;
  padding: 100px;
  color: #8c8c8c;
}

.patient-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
  background: white;
  padding: 20px 30px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  flex-wrap: wrap;
  gap: 20px;
}

.patient-info h1 {
  margin: 0;
  font-size: 24px;
  color: #262626;
}

.patient-badge {
  font-size: 12px;
  color: #8c8c8c;
}

.legend {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  align-items: center;
}

.legend-hint {
  padding: 8px 16px;
  background: #fff7e6;
  border: 1px solid #ffd591;
  border-radius: 25px;
  color: #d48806;
  font-size: 14px;
}

.legend-pill {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 16px;
  background: #fff;
  border: 2px solid #d9d9d9;
  border-radius: 25px;
  cursor: pointer;
  font-size: 14px;
  transition: all 0.2s ease;
  user-select: none;
}

.legend-pill:hover {
  border-color: #40a9ff;
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.legend-pill.active {
  border-color: #1890ff;
  background: #e6f7ff;
  color: #1890ff;
  font-weight: 600;
  box-shadow: 0 2px 8px rgba(24, 144, 255, 0.3);
}

.dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  border: 1px solid rgba(0, 0, 0, 0.1);
  flex-shrink: 0;
}

.content-grid {
  display: grid;
  grid-template-columns: 1fr 380px;
  gap: 30px;
}

.odontogram-card {
  background: white;
  border-radius: 12px;
  padding: 40px;
  border: 1px solid #f0f0f0;
  display: flex;
  flex-direction: column;
  gap: 40px;
}

.chart-box {
  display: flex;
  flex-direction: column;
  align-items: center;

  .chart-label {
    font-size: 11px;
    color: #bfbfbf;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    margin-bottom: 25px;
  }
}

.primary-area {
  background: #f9f9f9;
  padding: 30px 0;
  border-radius: 8px;
}

.chart-divider {
  height: 1px;
  background: linear-gradient(to right, transparent, #e8e8e8, transparent);
}

.card {
  background: white;
  padding: 24px;
  border-radius: 12px;
  border: 1px solid #f0f0f0;

  h3 {
    margin: 0 0 10px 0;
    font-size: 16px;
    color: #262626;
  }
}

textarea {
  width: 100%;
  height: 300px;
  border: 1px solid #d9d9d9;
  border-radius: 8px;
  padding: 15px;
  resize: none;
  margin-top: 15px;
  line-height: 1.6;
  font-family: inherit;

  &:focus {
    outline: none;
    border-color: #40a9ff;
    box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2);
  }
}

.voice-fab {
  position: fixed;
  bottom: 30px;
  right: 30px;
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: #22c55e;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 26px;
  color: white;
  cursor: pointer;
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
  transition: transform 0.2s;

  &:hover {
    transform: scale(1.1);
  }

  &:active {
    transform: scale(0.95);
  }
}

.voice-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 999;
  backdrop-filter: blur(2px);
}

.treatment-plans {
  margin-top: 20px;
}


.plan-card {
  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
  }
}

.plan-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  border-bottom: 1px solid #f0f0f0;
  transition: background 0.2s;

  &:hover {
    background: #fafafa;
  }

  .plan-info {
    display: flex;
    flex-direction: column;
    gap: 4px;

    .plan-name {
      font-weight: 600;
      color: #262626;
    }

    .plan-cost {
      font-size: 13px;
      color: #595959;
    }
  }

  .plan-actions {
    display: flex;
    gap: 8px;
  }
}

.empty-plans {
  text-align: center;
  padding: 20px;
  color: #bfbfbf;
  font-style: italic;
}

.content-grid {
  display: grid;
  grid-template-columns: 1fr 380px;
  gap: 30px;
  align-items: start;
  /* Prevents children from stretching to match heights */
}

.chart-section {
  display: flex;
  flex-direction: column;
  gap: 30px;
  /* Space between Odontogram and Treatment Plans */
}

/* Ensure the card itself has a minimum height if empty */
.treatment-plans {
  min-height: 100px;
}
</style>
