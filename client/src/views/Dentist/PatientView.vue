<script lang="ts" setup>
import { useMessage } from 'naive-ui'
import { ref, computed, onMounted, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import Odontogram from '@/components/Odontogram.vue'
import PrimaryOdontogram from '@/components/PrimaryOdontogram.vue'
import { predict } from '@/utils/voiceInference'
import PatientService from '@api/patient'
import OdontogramService from '@api/odontogram'
import type PatientData from '@api/interfaces/Patient'
import type { OdontogramData, ConditionLibrary, SaveConditionPayload, Tooth } from '@api/interfaces/Odontogram'

type VoiceStep = 'tooth' | 'surface' | 'condition'

const route = useRoute()
const patientId = computed(() => Number(route.params.id))

const isRecording = ref(false)
const currentStep = ref<VoiceStep>('tooth')
const loading = ref(false)

const patient = ref<PatientData & { f_name: string; l_name: string } | null>(null)
const conditionLibrary = ref<ConditionLibrary[]>([])
const activeFinding = ref<ConditionLibrary | null>(null)

// Matches your backend's wrapped response
const odontogramData = ref<{ patient_id: number; teeth: Tooth[] } | null>(null)
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

// --- Data Loading ---
async function loadPatientData() {
  if (!patientId.value) return;
  loading.value = true;

  try {
    const [patientRes, odontogramRes, libraryRes] = await Promise.all([
      PatientService.getPatient(patientId.value),
      OdontogramService.getPatientOdontogram(patientId.value),
      OdontogramService.getConditionLibrary()
    ]);

    // 1. Patient Info
    patient.value = {
      ...patientRes.data.data,
      f_name: patientRes.data.data.fName,
      l_name: patientRes.data.data.lName,
    };

    // 2. THE TRANSFORMATION (Backend Array -> Component Object)
    // Drilling into axios.data and laravel.data
    const teethArray = odontogramRes.data.data || [];
    const transformedState: any = {};

    teethArray.forEach((tooth: any) => {
      const fdi = tooth.fdi_code;
      transformedState[fdi] = {};

      (tooth.active_conditions || []).forEach((cond: any) => {
        const color = cond.condition_library?.ui_color || '#999999';
        const dbId = cond.id; // 🔑 This is the UUID Laravel generated

        if (Array.isArray(cond.surfaces)) {
          cond.surfaces.forEach((surface: string) => {
            // Store BOTH the color and the ID
            transformedState[fdi][surface.toLowerCase()] = {
              color: color,
              id: dbId
            };
          });
        }
      });
    });
    odontogramData.value = transformedState;

    // 3. Condition Library
    const libArr = libraryRes.data.data || libraryRes.data || [];
    conditionLibrary.value = libArr.map((item: any) => ({
      id: item.id,
      label: item.label,
      slug: item.slug,
      ui_color: item.ui_color,
      svg_icon_path: item.svg_icon_path // Keep this for symbols like 'X'
    }));

    if (conditionLibrary.value.length > 0) {
      // Set activeFinding to the COLOR string, as your prop expects string
      activeFinding.value = conditionLibrary.value[0].ui_color;
    }

    message.success('Records synchronized');
  } catch (error) {
    console.error('Load error:', error);
    message.error('Failed to load data');
  } finally {
    loading.value = false;
  }
}

// --- 🔑 CRITICAL: Refresh with forced reactivity ---
async function refreshOdontogram() {
  if (!patientId.value) return false;

  try {
    const res = await OdontogramService.getPatientOdontogram(patientId.value);

    const teethArray = odontogramRes.data.data || [];
    const transformedState: any = {};

    teethArray.forEach((tooth: any) => {
      const fdi = tooth.fdi_code;
      transformedState[fdi] = {};

      (tooth.active_conditions || []).forEach((cond: any) => {
        const color = cond.condition_library?.ui_color || '#999999';
        const dbId = cond.id; // 🔑 This is the UUID Laravel generated

        if (Array.isArray(cond.surfaces)) {
          cond.surfaces.forEach((surface: string) => {
            // Store BOTH the color and the ID
            transformedState[fdi][surface.toLowerCase()] = {
              color: color,
              id: dbId
            };
          });
        }
      });
    });
    odontogramData.value = transformedState;

    return true;
  } catch (err) {
    console.error('Refresh failed:', err);
    return false;
  }
}

// --- Select condition from legend ---
function selectCondition(condition: ConditionLibrary) {
  if (!condition?.id) {
    message.error('Invalid condition')
    return
  }
  activeFinding.value = condition
  message.success(`Selected: ${condition.label}`)
}

// --- FDI-aware surface mapping ---
function mapSurfaceToPart(surface: string, tooth: number): string {
  const quadrant = Math.floor(tooth / 10)
  const buccalIsRight = quadrant === 1 || quadrant === 4
  switch (surface) {
    case 'buccal': return buccalIsRight ? 'right' : 'left'
    case 'lingual':
    case 'palatal': return buccalIsRight ? 'left' : 'right'
    case 'occlusal': return 'top'
    case 'mesial': return 'left'
    case 'distal': return 'right'
    default: return 'center'
  }
}

// --- Convert spoken tooth → number ---
function normalizeTooth(label: string): number | null {
  const map: Record<string, number> = {
    'eleven': 11, 'twelve': 12, 'thirteen': 13, 'fourteen': 14,
    'fifteen': 15, 'sixteen': 16, 'seventeen': 17, 'eighteen': 18,
    'twenty one': 21, 'twenty two': 22, 'twenty three': 23,
    'twenty four': 24, 'twenty five': 25, 'twenty six': 26,
    'twenty seven': 27, 'twenty eight': 28,
    'thirty one': 31, 'thirty two': 32, 'thirty three': 33,
    'thirty four': 34, 'thirty five': 35, 'thirty six': 36,
    'thirty seven': 37, 'thirty eight': 38,
    'forty one': 41, 'forty two': 42, 'forty three': 43,
    'forty four': 44, 'forty five': 45, 'forty six': 46,
    'forty seven': 47, 'forty eight': 48,
  }
  if (map[label]) return map[label]
  const num = parseInt(label)
  return isNaN(num) ? null : num
}

// --- Recording ---
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

// --- Core step executor ---
async function runStep(step: VoiceStep): Promise<boolean> {
  const msg = message.loading(`Listening for ${step}...`, { duration: 0 })
  const blob = await recordOnce()
  msg.destroy()
  const res = await predict(blob, step)

  if (!res || res.probabilities.length === 0) {
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

// --- 🔥 SAVE & SYNC ---
async function handleToothClick(toothFdi: number, surface: string) {
  if (!patientId.value) return;

  const surfKey = surface.toLowerCase();
  const existing = odontogramData.value[toothFdi]?.[surfKey];

  // 1. DELETE check (This part you said is working!)
  if (existing && typeof existing === 'object' && existing.id) {
    try {
      await OdontogramService.deleteToothCondition(existing.id);
      message.success('Condition removed');
      await refreshOdontogram();
      return;
    } catch (err) {
      console.error('Delete failed', err);
      return;
    }
  }

  // 2. SAVE check
  // The error says activeFinding is undefined. Let's be safe:
  if (!activeFinding.value || !activeFinding.value.id) {
    message.warning('Please select a condition from the legend first');
    return;
  }

  const payload = {
    tooth_id: toothFdi, // FDI Code (e.g. 11)
    condition_id: activeFinding.value.id,
    surfaces: [surface.toUpperCase()], // "TOP"
  };

  try {
    // Check your console to see if this even triggers now
    console.log('Sending Save Payload:', payload);

    await OdontogramService.saveToothCondition(Number(patientId.value), payload);
    message.success('Condition saved');

    // 🔑 IMPORTANT: Refresh to get the new ID from the DB
    await refreshOdontogram();
  } catch (err: any) {
    console.error('Save failed:', err.response?.data || err);
    message.error('Failed to save to database');
  }
}

// --- Full voice pipeline ---
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
      (c) => c.label.toLowerCase() === conditionLabel.toLowerCase()
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

onMounted(() => {
  loadPatientData()
})
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
            <span class="dot" :style="{ backgroundColor: f.ui_color }"></span>
            {{ f.label }}
          </div>
        </div>
      </header>

      <main class="content-grid">
        <section class="chart-section">
          <div class="odontogram-card">
            <div class="chart-box">
              <span class="chart-label">Permanent Teeth</span>
              <Odontogram v-model="odontogramData" :active-finding="activeFinding?.ui_color || '#ffffff'"
                @tooth-click="handleToothClick" />
            </div>
            <div class="chart-divider"></div>
            <div class="chart-box primary-area">
              <span class="chart-label">Primary Teeth</span>
              <PrimaryOdontogram v-model="odontogramData" :active-finding="activeFinding?.ui_color || '#ffffff'"
                @tooth-click="handleToothClick" />
            </div>
          </div>
        </section>

        <section class="notes-section">
          <div class="card">
            <h3>Clinical Notes</h3>
            <textarea v-model="clinicalNotes" placeholder="Enter patient notes..."></textarea>
          </div>
        </section>
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
</style>
