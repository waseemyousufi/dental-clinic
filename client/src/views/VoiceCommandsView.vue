<script lang="ts">
type CommandItem = {
  command: string
  subtitle?: string
  recordingSeconds?: number
}

function normalizeLabel(command: string): string {
  return command.trim().toLowerCase().replace(/\s+/g, ' ')
}

const BACKGROUND_NOISE_COMMAND = 'Background Noise'
const NOISE_LABEL = normalizeLabel(BACKGROUND_NOISE_COMMAND)

const toothNumbers: CommandItem[] = [
  { command: '11', subtitle: 'Upper right central incisor' },
  { command: '12', subtitle: 'Upper right lateral incisor' },
]

const surfaceCommands: CommandItem[] = [
  { command: 'Mesial', subtitle: 'Toward the midline' },
  { command: 'Distal', subtitle: 'Away from the midline' },
]

const conditionCommands: CommandItem[] = [
  { command: 'Caries', subtitle: 'Active decay' },
  { command: 'Recurrent Caries', subtitle: 'Decay around restoration' },
]

const noiseCommands: CommandItem[] = [
  { command: BACKGROUND_NOISE_COMMAND, subtitle: 'Ambient room sound for augmentation', recordingSeconds: 10 },
]
</script>

<script setup lang="ts">
import VoiceCommandRecorder from '@/components/VoiceCommandRecorder.vue'
import { computed, onBeforeUnmount, onMounted, reactive, ref, markRaw, shallowReactive } from 'vue'
import * as tf from '@tensorflow/tfjs'
import '@tensorflow/tfjs-backend-webgl'
import { useMessage } from 'naive-ui'

/**
 * TYPE DEFINITIONS
 */
type RecorderPayload = { command: string; total: number; blob?: Blob; index?: number }
type CommandGroup = { title: string; description: string; items: CommandItem[] }
type Category = 'tooth' | 'surface' | 'condition'
type TrainingMode = 'initial' | 'personalized'
type TrainSummary = {
  loss: number
  accuracy: number
  valLoss?: number
  valAccuracy?: number
  samples: number
  labels: string[]
  inputShape: [number, number, number]
}
type FeatureExample = { features: Float32Array; labelIndex: number; command: string; createdAt: number }
type PredictionResult = {
  label: string
  confidence: number
  probabilities: number[]
  labels: string[]
  inputShape: [number, number, number]
}
type ModelKind = 'binary' | 'multiclass'

/**
 * STATE & REPEATABLE LOGIC
 */
const message = useMessage()

const trainingMode = ref<TrainingMode>('initial')
const currentModeLabel = computed(() => (trainingMode.value === 'initial' ? 'Initial training' : 'End-user training'))

const groups: CommandGroup[] = [
  { title: 'Tooth Location', description: 'FDI / ISO 3950 tooth numbers.', items: toothNumbers },
  { title: 'Surface', description: 'Surface descriptors for dental charting.', items: surfaceCommands },
  { title: 'Condition', description: 'Common clinical findings.', items: conditionCommands },
  { title: 'Noise', description: 'Ambient samples used for augmentation.', items: noiseCommands },
]

const COMMAND_TO_CATEGORY = new Map<string, Category>()

const CATEGORY_LABELS: Record<Category, string[]> = {
  tooth: toothNumbers.map((x) => normalizeLabel(x.command)),
  surface: surfaceCommands.map((x) => normalizeLabel(x.command)),
  condition: conditionCommands.map((x) => normalizeLabel(x.command)),
}

for (const item of toothNumbers) COMMAND_TO_CATEGORY.set(normalizeLabel(item.command), 'tooth')
for (const item of surfaceCommands) COMMAND_TO_CATEGORY.set(normalizeLabel(item.command), 'surface')
for (const item of conditionCommands) COMMAND_TO_CATEGORY.set(normalizeLabel(item.command), 'condition')

const samplesByCommand = reactive<Record<string, number>>({})
const backgroundNoiseSamples = reactive<Blob[]>([])
const trainingState = reactive({
  status: 'idle' as 'idle' | 'ready' | 'training' | 'done' | 'error',
  message: '',
  progress: 0,
})
const trainedModels: Record<TrainingMode, Partial<Record<Category, tf.LayersModel>>> = shallowReactive({
  initial: {},
  personalized: {},
})
const labelCache: Record<TrainingMode, Partial<Record<Category, string[]>>> = {
  initial: {},
  personalized: {},
}

// Backing stores, split by mode so the base model and user model do not overwrite each other.
const voiceSamplesByMode = reactive<Record<TrainingMode, Record<string, Blob[]>>>({
  initial: {},
  personalized: {},
})
const featureStoreByMode = reactive<Record<TrainingMode, Record<Category, FeatureExample[]>>>({
  initial: { tooth: [], surface: [], condition: [] },
  personalized: { tooth: [], surface: [], condition: [] },
})
const featureStoreHydrated = reactive<Record<TrainingMode, boolean>>({
  initial: false,
  personalized: false,
})

// TF Setup Constants
const SAMPLE_RATE = 16_000
const TARGET_DURATION_SECONDS = 1
const TARGET_SAMPLES = SAMPLE_RATE * TARGET_DURATION_SECONDS
const MIN_LABEL_SAMPLES = 5
const MODEL_PREFIX = 'dental-voice-dscnn'

// DS-CNN feature extraction constants
const FRAME_LENGTH = 480
const FRAME_STEP = 160
const FFT_LENGTH = 512
const MEL_BINS = 40
const MEL_LOW_FREQ = 20
const MEL_HIGH_FREQ = SAMPLE_RATE / 2
const FEATURE_FRAMES = 97
const FEATURE_SHAPE: [number, number, number] = [FEATURE_FRAMES, MEL_BINS, 1]

// Speech cleanup constants
const SILENCE_THRESHOLD_RATIO = 0.06
const SILENCE_FLOOR = 0.008
const SILENCE_PAD_MS = 120

// Training constants
const BALANCED_TARGET_PER_LABEL = 24
const AUGMENTATIONS_PER_SAMPLE = 3
const FEATURE_JITTER_STD = 0.03
const INITIAL_TRAIN_LR = 8e-4
const PERSONALIZED_TRAIN_LR = 5e-5
const PERSONALIZED_EPOCHS = 18
const INITIAL_EPOCHS = 36
const LOW_CONFIDENCE_MARGIN = 0.15

const waveformCache = new WeakMap<Blob, Float32Array>()
let audioContext: AudioContext | null = null
let MEL_FILTERBANK: tf.Tensor2D | null = null

// IndexedDB for feature cache, split by mode.
const FEATURE_DB_VERSION = 1
const FEATURE_STORE_NAME = 'feature_examples'
const featureDbPromiseByMode: Partial<Record<TrainingMode, Promise<IDBDatabase>>> = {}

// Floating Button States
const isGlobalRecording = ref(false)
const timeLeft = ref(2.0)
const totalDuration = 2.0
const circlesCircumference = 2 * Math.PI * 26
let mediaRecorder: MediaRecorder | null = null
let audioChunks: Blob[] = []
let timerInterval: number | null = null

/**
 * COMPUTED PROPERTIES
 */
const totalCommands = computed(() => groups.reduce((count, group) => count + group.items.length, 0))
const totalSamples = computed(() => {
  const mode = trainingMode.value
  return Object.values(voiceSamplesByMode[mode]).reduce((sum, samples) => sum + (samples?.length ?? 0), 0)
})
const noiseSamples = computed(() => backgroundNoiseSamples.length)
const completedCommands = computed(() => Object.values(samplesByCommand).filter((count) => count >= 5).length)
const canTrain = computed(() => {
  const mode = trainingMode.value
  return (['tooth', 'surface', 'condition'] as Category[]).every((cat) =>
    CATEGORY_LABELS[cat].filter((l) => getKnownSampleCount(cat, l, mode) >= MIN_LABEL_SAMPLES).length >= 2,
  )
})

const recordingCircleOffset = computed(() => circlesCircumference * (1 - timeLeft.value / totalDuration))
const trainingCircleOffset = computed(() => 188.5 * (1 - trainingState.progress / 100))

/**
 * SMALL UTILS
 */
function makeId() {
  if (typeof crypto !== 'undefined' && 'randomUUID' in crypto) return crypto.randomUUID()
  return `${Date.now()}-${Math.random().toString(36).slice(2)}`
}

function getModePrefix(mode: TrainingMode) {
  return mode === 'initial' ? 'base' : 'user'
}

function getModelKey(mode: TrainingMode, category: Category) {
  return `indexeddb://${MODEL_PREFIX}-${getModePrefix(mode)}-${category}`
}

function getLabelKey(mode: TrainingMode, category: Category) {
  return `${MODEL_PREFIX}-${getModePrefix(mode)}:${category}:labels`
}

function getLastTrainedAtKey(mode: TrainingMode, category: Category) {
  return `${MODEL_PREFIX}-${getModePrefix(mode)}:${category}:lastTrainedAt`
}

function getFeatureDbName(mode: TrainingMode) {
  return `${MODEL_PREFIX}-${getModePrefix(mode)}-feature-store`
}

function rand(min: number, max: number) {
  return Math.random() * (max - min) + min
}

function randn() {
  let u = 0
  let v = 0
  while (u === 0) u = Math.random()
  while (v === 0) v = Math.random()
  return Math.sqrt(-2.0 * Math.log(u)) * Math.cos(2.0 * Math.PI * v)
}

function clamp(v: number, min = -1, max = 1) {
  return Math.max(min, Math.min(max, v))
}

function shuffleCopy<T>(items: T[]): T[] {
  const out = [...items]
  for (let i = out.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1))
      ;[out[i], out[j]] = [out[j]!, out[i]!]
  }
  return out
}

function toFloat32Array(value: Float32Array | number[] | ArrayBuffer | ArrayBufferView): Float32Array {
  if (value instanceof Float32Array) return value
  if (Array.isArray(value)) return Float32Array.from(value)
  if (ArrayBuffer.isView(value)) {
    const view = value as ArrayBufferView
    return new Float32Array(view.buffer.slice(view.byteOffset, view.byteOffset + view.byteLength))
  }
  return new Float32Array(value)
}

function lastNumber(values: unknown): number | undefined {
  if (!Array.isArray(values) || values.length === 0) return undefined
  const last = values[values.length - 1]
  return typeof last === 'number' && Number.isFinite(last) ? last : undefined
}

function clearModelCacheForMode(mode: TrainingMode, category: Category) {
  const model = trainedModels[mode][category]
  if (model) {
    model.dispose()
    trainedModels[mode][category] = undefined
  }
}

/**
 * INDEXEDDB FEATURE CACHE
 */
function openFeatureDb(mode: TrainingMode): Promise<IDBDatabase> {
  const cached = featureDbPromiseByMode[mode]
  if (cached) return cached

  const promise = new Promise<IDBDatabase>((resolve, reject) => {
    const req = indexedDB.open(getFeatureDbName(mode), FEATURE_DB_VERSION)

    req.onupgradeneeded = () => {
      const db = req.result
      if (!db.objectStoreNames.contains(FEATURE_STORE_NAME)) {
        const store = db.createObjectStore(FEATURE_STORE_NAME, { keyPath: 'id' })
        store.createIndex('by_category', 'category', { unique: false })
        store.createIndex('by_category_command', ['category', 'command'], { unique: false })
        store.createIndex('by_category_createdAt', ['category', 'createdAt'], { unique: false })
      }
    }

    req.onsuccess = () => resolve(req.result)
    req.onerror = () => reject(req.error)
  })

  featureDbPromiseByMode[mode] = promise
  return promise
}

function normalizeStoredFeatureExample(raw: unknown): FeatureExample | null {
  if (!raw || typeof raw !== 'object') return null
  const item = raw as Partial<FeatureExample> & { id?: string; category?: Category; features?: unknown; labelIndex?: number; command?: string; createdAt?: number }

  if (!item.category || !item.command || typeof item.labelIndex !== 'number' || !item.features || typeof item.createdAt !== 'number') {
    return null
  }

  const labels = CATEGORY_LABELS[item.category]
  const labelIndex = labels.indexOf(item.command)
  if (labelIndex < 0) return null

  return {
    command: item.command,
    createdAt: item.createdAt,
    labelIndex,
    features: toFloat32Array(item.features as Float32Array | number[] | ArrayBuffer | ArrayBufferView),
  }
}

async function putFeatureExample(example: FeatureExample, category: Category, mode: TrainingMode): Promise<void> {
  const db = await openFeatureDb(mode)
  await new Promise<void>((resolve, reject) => {
    const tx = db.transaction(FEATURE_STORE_NAME, 'readwrite')
    const store = tx.objectStore(FEATURE_STORE_NAME)
    const id = makeId()
    const req = store.put({
      id,
      category,
      command: example.command,
      createdAt: example.createdAt,
      labelIndex: example.labelIndex,
      features: example.features,
    })

    req.onerror = () => reject(req.error)
    tx.oncomplete = () => resolve()
    tx.onerror = () => reject(tx.error)
    tx.onabort = () => reject(tx.error)
  })
}

async function deleteFeatureExamplesByCommand(category: Category, command: string, mode: TrainingMode): Promise<void> {
  const db = await openFeatureDb(mode)
  await new Promise<void>((resolve, reject) => {
    const tx = db.transaction(FEATURE_STORE_NAME, 'readwrite')
    const store = tx.objectStore(FEATURE_STORE_NAME)
    const index = store.index('by_category_command')
    const keyRange = IDBKeyRange.only([category, command])

    const req = index.openCursor(keyRange)
    req.onerror = () => reject(req.error)
    req.onsuccess = () => {
      const cursor = req.result
      if (cursor) {
        cursor.delete()
        cursor.continue()
      }
    }

    tx.oncomplete = () => resolve()
    tx.onerror = () => reject(tx.error)
    tx.onabort = () => reject(tx.error)
  })
}

async function loadFeatureExamplesByCategory(category: Category, mode: TrainingMode): Promise<FeatureExample[]> {
  const db = await openFeatureDb(mode)
  return new Promise<FeatureExample[]>((resolve, reject) => {
    const tx = db.transaction(FEATURE_STORE_NAME, 'readonly')
    const store = tx.objectStore(FEATURE_STORE_NAME)
    const index = store.index('by_category')
    const req = index.getAll(IDBKeyRange.only(category))

    req.onerror = () => reject(req.error)
    req.onsuccess = () => {
      const result = (req.result || [])
        .map((item) => normalizeStoredFeatureExample(item))
        .filter((x): x is FeatureExample => Boolean(x))
      resolve(result)
    }
  })
}

function setFeatureStoreCategory(mode: TrainingMode, category: Category, values: FeatureExample[]) {
  featureStoreByMode[mode][category].splice(0, featureStoreByMode[mode][category].length, ...values)
}

async function hydrateFeatureStoreFromDb(mode: TrainingMode) {
  const categories: Category[] = ['tooth', 'surface', 'condition']
  const loaded = await Promise.all(categories.map(async (category) => [category, await loadFeatureExamplesByCategory(category, mode)] as const))

  for (const [category, items] of loaded) {
    setFeatureStoreCategory(mode, category, items)
  }

  featureStoreHydrated[mode] = true
}

function getKnownSampleCount(category: Category, command: string, mode: TrainingMode): number {
  const fromVoice = voiceSamplesByMode[mode][command]?.length ?? 0
  const fromFeatures = featureStoreByMode[mode][category].filter((x) => x.command === command).length
  return Math.max(fromVoice, fromFeatures)
}

function sampleUpTo<T>(items: T[], limit: number): T[] {
  if (items.length <= limit) return [...items]
  return shuffleCopy(items).slice(0, limit)
}

function buildBalancedBatch(mode: TrainingMode, category: Category, allExamples: FeatureExample[]): FeatureExample[] {
  const labels = CATEGORY_LABELS[category]
  const perLabel = labels.map((_, labelIndex) => allExamples.filter((x) => x.labelIndex === labelIndex))

  if (perLabel.some((group) => group.length === 0)) return []

  const minCount = Math.min(...perLabel.map((group) => group.length))
  const targetPerLabel = Math.max(minCount, BALANCED_TARGET_PER_LABEL)

  const batch: FeatureExample[] = []
  for (const group of perLabel) {
    const selected = sampleUpTo(group, targetPerLabel)

    for (const base of selected) {
      batch.push({
        command: base.command,
        createdAt: base.createdAt,
        labelIndex: base.labelIndex,
        features: Float32Array.from(base.features),
      })

      for (let i = 0; i < AUGMENTATIONS_PER_SAMPLE; i++) {
        batch.push({
          command: base.command,
          createdAt: base.createdAt,
          labelIndex: base.labelIndex,
          features: jitterFeatures(base.features, FEATURE_JITTER_STD * (1 + i * 0.25)),
        })
      }
    }
  }

  return shuffleCopy(batch)
}

/**
 * SPEECH CLEANUP
 */
function trimSilence(input: Float32Array, sampleRate: number): Float32Array {
  if (input.length === 0) return input

  let peak = 0
  for (let i = 0; i < input.length; i++) {
    const a = Math.abs(input[i]!)
    if (a > peak) peak = a
  }

  if (peak < 1e-6) return input

  const threshold = Math.max(SILENCE_FLOOR, peak * SILENCE_THRESHOLD_RATIO)
  let start = 0
  let end = input.length - 1

  while (start < input.length && Math.abs(input[start]!) < threshold) start++
  while (end > start && Math.abs(input[end]!) < threshold) end--

  if (end <= start) return input

  const pad = Math.round((SILENCE_PAD_MS / 1000) * sampleRate)
  const from = Math.max(0, start - pad)
  const to = Math.min(input.length, end + pad + 1)

  return input.slice(from, to)
}

function peakNormalize(input: Float32Array, targetPeak = 0.95): Float32Array {
  let peak = 0
  for (let i = 0; i < input.length; i++) {
    const a = Math.abs(input[i]!)
    if (a > peak) peak = a
  }
  if (peak < 1e-8) return input

  const gain = targetPeak / peak
  const out = new Float32Array(input.length)
  for (let i = 0; i < input.length; i++) out[i] = clamp(input[i]! * gain)
  return out
}

function preEmphasis(input: Float32Array, coeff = 0.97): Float32Array {
  if (input.length === 0) return input
  const out = new Float32Array(input.length)
  out[0] = input[0]!
  for (let i = 1; i < input.length; i++) {
    out[i] = input[i]! - coeff * input[i - 1]!
  }
  return out
}

function jitterFeatures(input: Float32Array, sigma = FEATURE_JITTER_STD): Float32Array {
  const out = new Float32Array(input.length)
  for (let i = 0; i < input.length; i++) {
    out[i] = input[i]! + randn() * sigma
  }
  return out
}

/**
 * CORE TF.JS FUNCTIONS
 */
function getAudioContext() {
  if (!audioContext) audioContext = new AudioContext()
  return audioContext
}

async function blobToWaveform(blob: Blob): Promise<Float32Array> {
  const cached = waveformCache.get(blob)
  if (cached) return Float32Array.from(cached)

  const arrayBuffer = await blob.arrayBuffer()
  const ctx = getAudioContext()
  const decoded = await ctx.decodeAudioData(arrayBuffer)

  const mono = new Float32Array(decoded.length)
  for (let i = 0; i < decoded.length; i++) {
    let sum = 0
    for (let ch = 0; ch < decoded.numberOfChannels; ch++) {
      sum += decoded.getChannelData(ch)?.[i] || 0
    }
    mono[i] = sum / Math.max(1, decoded.numberOfChannels)
  }

  const resampled = resampleLinear(mono, decoded.sampleRate, SAMPLE_RATE)
  const trimmed = trimSilence(resampled, SAMPLE_RATE)
  const emphasized = preEmphasis(trimmed)
  const normalized = peakNormalize(emphasized)
  const fixed = padOrTrim(normalized, TARGET_SAMPLES)
  waveformCache.set(blob, fixed)
  return fixed
}

function padOrTrim2D(tensor: tf.Tensor2D, targetFrames: number) {
  const [frames, bins] = tensor.shape

  if (frames === targetFrames) return tensor

  if (frames > targetFrames) {
    return tensor.slice([0, 0], [targetFrames, bins])
  }

  const padAmount = targetFrames - frames
  const padding = tf.zeros([padAmount, bins])
  return tf.concat([tensor, padding], 0)
}

function resampleLinear(input: Float32Array, sR: number, tR: number) {
  if (sR === tR) return input
  const ratio = sR / tR
  const out = new Float32Array(Math.round(input.length / ratio))

  for (let i = 0; i < out.length; i++) {
    const sIdx = i * ratio
    const i0 = Math.floor(sIdx)
    const i1 = Math.min(input.length - 1, i0 + 1)
    const frac = sIdx - i0
    out[i] = input[i0]! * (1 - frac) + input[i1]! * frac
  }
  return out
}

function padOrTrim(input: Float32Array, length: number) {
  if (input.length === length) return input
  const out = new Float32Array(length)
  out.set(input.slice(0, length))
  return out
}

function rms(wf: Float32Array) {
  let sum = 0
  for (let i = 0; i < wf.length; i++) sum += wf[i]! * wf[i]!
  return Math.sqrt(sum / Math.max(1, wf.length))
}

function applyGain(input: Float32Array, gain: number) {
  const out = new Float32Array(input.length)
  for (let i = 0; i < input.length; i++) out[i] = clamp(input[i]! * gain)
  return out
}

function timeShift(input: Float32Array, shiftSamples: number) {
  const out = new Float32Array(input.length)

  if (shiftSamples > 0) {
    out.set(input.subarray(0, Math.max(0, input.length - shiftSamples)), shiftSamples)
  } else if (shiftSamples < 0) {
    out.set(input.subarray(Math.min(input.length, -shiftSamples)))
  } else {
    out.set(input)
  }

  return out
}

function synthNoise(length: number, amp = 0.02) {
  const out = new Float32Array(length)
  for (let i = 0; i < length; i++) {
    out[i] = (Math.random() * 2 - 1) * amp
  }
  return out
}

function sliceNoiseToLength(noise: Float32Array, length: number) {
  if (noise.length === 0) return synthNoise(length)
  if (noise.length === length) return Float32Array.from(noise)

  const out = new Float32Array(length)
  const start = Math.floor(Math.random() * noise.length)

  for (let i = 0; i < length; i++) {
    out[i] = noise[(start + i) % noise.length]!
  }
  return out
}

function mixWithNoise(signal: Float32Array, noise: Float32Array, snrDb: number) {
  const sRms = Math.max(rms(signal), 1e-8)
  const n = sliceNoiseToLength(noise, signal.length)
  const nRms = Math.max(rms(n), 1e-8)

  const desiredNoiseRms = sRms / Math.pow(10, snrDb / 20)
  const scale = desiredNoiseRms / nRms

  const out = new Float32Array(signal.length)
  for (let i = 0; i < signal.length; i++) {
    out[i] = clamp(signal[i]! + n[i]! * scale)
  }
  return out
}

function augmentWaveform(wf: Float32Array, noisePool: Float32Array[]) {
  let out = Float32Array.from(wf)

  out = applyGain(out, rand(0.85, 1.15))

  const shiftSamples = Math.round((rand(-120, 120) / 1000) * SAMPLE_RATE)
  out = timeShift(out, shiftSamples)

  if (noisePool.length > 0 && Math.random() < 0.85) {
    const noise = noisePool[Math.floor(Math.random() * noisePool.length)]!
    const snrDb = rand(7, 22)
    out = mixWithNoise(out, noise, snrDb)
  } else {
    const snrDb = rand(8, 24)
    out = mixWithNoise(out, synthNoise(out.length, rand(0.01, 0.04)), snrDb)
  }

  return out
}

function hzToMel(hz: number) {
  return 2595 * Math.log10(1 + hz / 700)
}

function melToHz(mel: number) {
  return 700 * (Math.pow(10, mel / 2595) - 1)
}

function createMelFilterbank(
  numMelBins: number,
  numSpectrogramBins: number,
  sampleRate: number,
  lowFreq: number,
  highFreq: number,
): tf.Tensor2D {
  const lowMel = hzToMel(lowFreq)
  const highMel = hzToMel(highFreq)

  const melPoints: number[] = []
  for (let i = 0; i < numMelBins + 2; i++) {
    melPoints.push(lowMel + (i / (numMelBins + 1)) * (highMel - lowMel))
  }

  const hzPoints = melPoints.map(melToHz)
  const bin = hzPoints.map((hz) => Math.floor(((FFT_LENGTH + 1) * hz) / sampleRate))

  const filterbank: number[][] = []
  for (let i = 0; i < numMelBins; i++) {
    const f_m_minus = bin[i]!
    const f_m = bin[i + 1]!
    const f_m_plus = bin[i + 2]!

    const row = new Array(numSpectrogramBins).fill(0)

    const leftDenom = Math.max(1, f_m - f_m_minus)
    const rightDenom = Math.max(1, f_m_plus - f_m)

    for (let k = f_m_minus; k < f_m; k++) {
      if (k >= 0 && k < numSpectrogramBins) {
        row[k] = (k - f_m_minus) / leftDenom
      }
    }

    for (let k = f_m; k < f_m_plus; k++) {
      if (k >= 0 && k < numSpectrogramBins) {
        row[k] = (f_m_plus - k) / rightDenom
      }
    }

    filterbank.push(row)
  }

  return tf.tensor2d(filterbank, [numMelBins, numSpectrogramBins]).transpose()
}

async function ensureBackendReady() {
  await tf.ready()
  await tf.setBackend('webgl')

  if (!MEL_FILTERBANK) {
    MEL_FILTERBANK = createMelFilterbank(
      MEL_BINS,
      Math.floor(FFT_LENGTH / 2) + 1,
      SAMPLE_RATE,
      MEL_LOW_FREQ,
      MEL_HIGH_FREQ,
    )
  }

  trainingState.status = 'ready'
}

/**
 * MODEL RESTORATION
 */
async function loadSavedModel(mode: TrainingMode, category: Category): Promise<boolean> {
  const modelKey = getModelKey(mode, category)

  try {
    const model = await tf.loadLayersModel(modelKey)
    trainedModels[mode][category] = markRaw(model)

    const labelsRaw = localStorage.getItem(getLabelKey(mode, category))
    if (labelsRaw) {
      try {
        labelCache[mode][category] = JSON.parse(labelsRaw) as string[]
      } catch {
        labelCache[mode][category] = CATEGORY_LABELS[category]
      }
    } else {
      labelCache[mode][category] = CATEGORY_LABELS[category]
      localStorage.setItem(getLabelKey(mode, category), JSON.stringify(CATEGORY_LABELS[category]))
    }

    return true
  } catch {
    return false
  }
}

async function restoreSavedModels() {
  if (tf.getBackend() !== 'webgl') {
    await tf.setBackend('webgl')
  }

  const modes: TrainingMode[] = ['initial', 'personalized']
  const categories: Category[] = ['tooth', 'surface', 'condition']
  let loadedCount = 0

  for (const mode of modes) {
    for (const category of categories) {
      const loaded = await loadSavedModel(mode, category)
      if (loaded) loadedCount += 1
    }
  }

  if (loadedCount > 0) {
    trainingState.status = 'done'
    trainingState.message = 'Saved models loaded.'
  } else {
    trainingState.status = 'ready'
    trainingState.message = 'No saved model found.'
  }
}

/**
 * TRAINING LOGIC
 */
async function waveformToFeatures(waveform: Float32Array): Promise<Float32Array> {
  if (!MEL_FILTERBANK) {
    throw new Error('Mel filterbank not initialized')
  }

  const featureTensor = tf.tidy(() => {
    const input = tf.tensor1d(waveform)
    const stft = tf.signal.stft(input, FRAME_LENGTH, FRAME_STEP, FFT_LENGTH, tf.signal.hannWindow)
    const magnitude = tf.abs(stft)

    const melFilterbank = MEL_FILTERBANK!
    let melSpectrogram = tf.matMul(magnitude, melFilterbank)

    melSpectrogram = padOrTrim2D(melSpectrogram, FEATURE_FRAMES)

    const logMel = tf.log(melSpectrogram.add(1e-6))
    const mean = tf.mean(logMel)
    const std = tf.sqrt(tf.mean(tf.square(logMel.sub(mean))))
    const normalized = logMel.sub(mean).div(std.add(1e-6))

    return normalized.expandDims(-1)
  })

  const data = await featureTensor.data()
  featureTensor.dispose()
  return Float32Array.from(data)
}

function compileClassifier(model: tf.LayersModel, kind: ModelKind, learningRate: number) {
  if (kind === 'binary') {
    model.compile({
      optimizer: tf.train.adam(learningRate),
      loss: 'binaryCrossentropy',
      metrics: ['accuracy'],
    })
  } else {
    model.compile({
      optimizer: tf.train.adam(learningRate),
      loss: 'categoricalCrossentropy',
      metrics: ['accuracy'],
    })
  }
}

function buildClassifier(inputShape: [number, number, number], classCount: number, kind: ModelKind) {
  const model = tf.sequential()

  model.add(
    tf.layers.conv2d({
      inputShape,
      filters: 16,
      kernelSize: [3, 3],
      padding: 'same',
      activation: 'relu',
      dataFormat: 'channelsLast',
      kernelInitializer: 'heNormal',
    }),
  )
  model.add(tf.layers.batchNormalization())

  model.add(
    tf.layers.separableConv2d({
      filters: 32,
      kernelSize: [3, 3],
      padding: 'same',
      activation: 'relu',
      depthMultiplier: 1,
      dataFormat: 'channelsLast',
      depthwiseInitializer: 'heNormal',
      pointwiseInitializer: 'heNormal',
      depthwiseRegularizer: tf.regularizers.l2({ l2: 1e-4 }),
      pointwiseRegularizer: tf.regularizers.l2({ l2: 1e-4 }),
    }),
  )
  model.add(tf.layers.batchNormalization())
  model.add(tf.layers.maxPooling2d({ poolSize: [2, 2], strides: [2, 2], dataFormat: 'channelsLast' }))
  model.add(tf.layers.dropout({ rate: 0.25 }))

  model.add(
    tf.layers.separableConv2d({
      filters: 64,
      kernelSize: [3, 3],
      padding: 'same',
      activation: 'relu',
      depthMultiplier: 1,
      dataFormat: 'channelsLast',
      depthwiseInitializer: 'heNormal',
      pointwiseInitializer: 'heNormal',
      depthwiseRegularizer: tf.regularizers.l2({ l2: 1e-4 }),
      pointwiseRegularizer: tf.regularizers.l2({ l2: 1e-4 }),
    }),
  )
  model.add(tf.layers.batchNormalization())
  model.add(tf.layers.maxPooling2d({ poolSize: [2, 2], strides: [2, 2], dataFormat: 'channelsLast' }))
  model.add(tf.layers.dropout({ rate: 0.35 }))

  model.add(tf.layers.globalAveragePooling2d({ dataFormat: 'channelsLast' }))

  if (kind === 'binary') {
    model.add(tf.layers.dense({ units: 1, activation: 'sigmoid', biasInitializer: 'zeros' }))
  } else {
    model.add(tf.layers.dense({ units: classCount, activation: 'softmax', biasInitializer: 'zeros' }))
  }

  compileClassifier(model, kind, INITIAL_TRAIN_LR)
  return model
}

function seedModelForMode(mode: TrainingMode, category: Category, kind: ModelKind) {
  if (mode === 'personalized') {
    const base = trainedModels.initial[category]
    if (base) {
      compileClassifier(base, kind, PERSONALIZED_TRAIN_LR)
      return base
    }
  }
  return undefined
}

async function storeFeatureExampleFromBlob(
  blob: Blob,
  category: Category,
  command: string,
  labelIndex: number,
  saveToDb = true,
): Promise<FeatureExample> {
  const wf = await blobToWaveform(blob)

  // 🔹 Collect noise pool
  const noiseWaveforms: Float32Array[] = []
  for (const noiseBlob of backgroundNoiseSamples) {
    try {
      const n = await blobToWaveform(noiseBlob)
      noiseWaveforms.push(n)
    } catch { }
  }

  // 🔹 ALWAYS store original
  const baseFeat = await waveformToFeatures(wf)
  const baseExample: FeatureExample = {
    command,
    createdAt: Date.now(),
    labelIndex,
    features: baseFeat,
  }

  featureStore[category].push(baseExample)
  if (saveToDb) await putFeatureExample(baseExample, category)

  // 🔥 ADD REAL AUGMENTATION HERE
  for (let i = 0; i < AUGMENTATIONS_PER_SAMPLE; i++) {
    const augmented = augmentWaveform(wf, noiseWaveforms)
    const feat = await waveformToFeatures(augmented)

    const example: FeatureExample = {
      command,
      createdAt: Date.now(),
      labelIndex,
      features: feat,
    }

    featureStore[category].push(example)
    if (saveToDb) await putFeatureExample(example, category)
  }

  return baseExample
}

async function backfillFeatureStoreFromVoiceSamples(mode: TrainingMode, category: Category) {
  const labels = CATEGORY_LABELS[category]

  for (const label of labels) {
    const blobs = voiceSamplesByMode[mode][label] ?? []
    const existingCount = featureStoreByMode[mode][category].filter((x) => x.command === label).length

    for (let i = existingCount; i < blobs.length; i++) {
      const blob = blobs[i]
      if (!blob) continue
      await storeFeatureExampleFromBlob(blob, category, label, labels.indexOf(label), mode, true)
    }
  }
}

async function ensureFeatureStoreReady(mode: TrainingMode) {
  if (!featureStoreHydrated[mode]) {
    await hydrateFeatureStoreFromDb(mode)
  }
  await Promise.all((['tooth', 'surface', 'condition'] as Category[]).map((category) => backfillFeatureStoreFromVoiceSamples(mode, category)))
}

async function trainCategoryIncremental(category: Category): Promise<TrainSummary> {
  const mode = trainingMode.value
  const labels = CATEGORY_LABELS[category]
  labelCache[mode][category] = labels

  await ensureFeatureStoreReady(mode)

  let model = trainedModels[mode][category]
  if (!model) {
    if (mode === 'personalized') {
      const base = seedModelForMode(mode, category, labels.length === 2 ? 'binary' : 'multiclass')
      model = base ?? buildClassifier(FEATURE_SHAPE, labels.length, labels.length === 2 ? 'binary' : 'multiclass')
    } else {
      model = buildClassifier(FEATURE_SHAPE, labels.length, labels.length === 2 ? 'binary' : 'multiclass')
    }
  }

  const kind: ModelKind = labels.length === 2 ? 'binary' : 'multiclass'
  compileClassifier(model, kind, mode === 'personalized' ? PERSONALIZED_TRAIN_LR : INITIAL_TRAIN_LR)

  const allExamples = featureStoreByMode[mode][category]
  const batch = buildBalancedBatch(mode, category, allExamples)

  if (batch.length === 0) {
    throw new Error(`No training examples found for ${mode} / ${category}`)
  }

  if (batch[0]!.features.length !== FEATURE_FRAMES * MEL_BINS) {
    throw new Error('Feature shape mismatch — check spectrogram sizing')
  }

  const inputShape: [number, number, number] = FEATURE_SHAPE
  const featureLength = FEATURE_FRAMES * MEL_BINS

  const flat = new Float32Array(batch.length * featureLength)
  batch.forEach((e, i) => {
    flat.set(e.features, i * featureLength)
  })

  const xTrain = tf.tensor4d(flat, [batch.length, FEATURE_FRAMES, MEL_BINS, 1])

  let yTrain: tf.Tensor
  if (kind === 'binary') {
    yTrain = tf.tensor2d(batch.map((e) => [e.labelIndex]), [batch.length, 1], 'float32')
  } else {
    yTrain = tf.oneHot(tf.tensor1d(batch.map((e) => e.labelIndex), 'int32'), labels.length)
  }

  const epochs = mode === 'personalized' ? PERSONALIZED_EPOCHS : INITIAL_EPOCHS
  const history = await model.fit(xTrain, yTrain, {
    epochs,
    batchSize: Math.min(8, batch.length),
    shuffle: true,
    validationSplit: batch.length >= 10 ? 0.2 : 0,
    callbacks: {
      onEpochEnd: (e) => {
        trainingState.progress = Math.round(((e + 1) / epochs) * 100)
      },
    },
  })

  xTrain.dispose()
  yTrain.dispose()

  await model.save(getModelKey(mode, category))
  localStorage.setItem(getLabelKey(mode, category), JSON.stringify(labels))
  localStorage.setItem(getLastTrainedAtKey(mode, category), String(Math.max(...batch.map((x) => x.createdAt), Date.now())))
  trainedModels[mode][category] = markRaw(model)

  const loss = lastNumber(history.history.loss) ?? 0
  const accuracy = lastNumber(history.history.accuracy ?? history.history.acc) ?? 0
  const valLoss = lastNumber(history.history.val_loss)
  const valAccuracy = lastNumber(history.history.val_accuracy ?? history.history.val_acc)

  return {
    loss,
    accuracy,
    valLoss,
    valAccuracy,
    samples: batch.length,
    labels,
    inputShape,
  }
}

async function train() {
  const mode = trainingMode.value
  trainingState.status = 'training'
  trainingState.progress = 0
  trainingState.message = ''

  try {
    await trainCategoryIncremental('tooth')
    await trainCategoryIncremental('surface')
    await trainCategoryIncremental('condition')
    trainingState.status = 'done'
    trainingState.message = mode === 'initial' ? 'Initial training complete.' : 'End-user training complete.'
    message.success(trainingState.message)
  } catch (e: unknown) {
    trainingState.status = 'error'
    const errorMessage = e instanceof Error ? e.message : String(e)
    trainingState.message = errorMessage
    message.error(errorMessage)
    console.error(e)
  }
}

async function getCategoryModel(category: Category): Promise<tf.LayersModel> {
  const personalized = trainedModels.personalized[category]
  if (personalized) return personalized

  const loadedPersonalized = await loadSavedModel('personalized', category)
  if (loadedPersonalized && trainedModels.personalized[category]) {
    return trainedModels.personalized[category]!
  }

  const base = trainedModels.initial[category]
  if (base) return base

  const loadedBase = await loadSavedModel('initial', category)
  if (loadedBase && trainedModels.initial[category]) {
    return trainedModels.initial[category]!
  }

  throw new Error('Model not trained')
}

async function predictRecordedCommand(payload: { blob: Blob; category: Category }): Promise<PredictionResult> {
  const model = await getCategoryModel(payload.category)
  const labels = labelCache.personalized[payload.category] || labelCache.initial[payload.category] || CATEGORY_LABELS[payload.category]

  const wf = await blobToWaveform(payload.blob)
  const feat = await waveformToFeatures(wf)
  const input = tf.tensor4d(feat, [1, FEATURE_SHAPE[0], FEATURE_SHAPE[1], FEATURE_SHAPE[2]])
  const output = model.predict(input) as tf.Tensor
  const raw = Array.from(await output.data())

  input.dispose()
  output.dispose()

  if (labels.length === 2) {
    let probs: number[]
    let bestIdx: number

    if (raw.length === 1) {
      const p1 = raw[0]!
      probs = [1 - p1, p1]
      bestIdx = p1 >= 0.5 ? 1 : 0
    } else {
      probs = raw.slice(0, 2)
      bestIdx = probs[1]! >= probs[0]! ? 1 : 0
    }

    const sorted = [...probs].sort((a, b) => b - a)
    const confidence = Math.max(0, (sorted[0] ?? 0) - (sorted[1] ?? 0))

    return {
      label: labels[bestIdx]!,
      confidence,
      probabilities: probs,
      labels,
      inputShape: FEATURE_SHAPE,
    }
  }

  const probs = raw
  const bestIdx = probs.indexOf(Math.max(...probs))
  const sorted = [...probs].sort((a, b) => b - a)
  const confidence = Math.max(0, (sorted[0] ?? 0) - (sorted[1] ?? 0))

  if (bestIdx < 0 || bestIdx >= labels.length) {
    throw new Error('Invalid prediction index')
  }

  return {
    label: labels[bestIdx]!,
    confidence,
    probabilities: probs,
    labels,
    inputShape: FEATURE_SHAPE,
  }
}

/**
 * ACTION HANDLERS
 */
async function storeRecordedSample(p: RecorderPayload) {
  const cmd = normalizeLabel(p.command)
  if (!p.blob) return

  if (cmd === NOISE_LABEL) return

  const category = COMMAND_TO_CATEGORY.get(cmd)
  if (!category) return

  const labelIndex = CATEGORY_LABELS[category].indexOf(cmd)
  if (labelIndex < 0) return

  const mode = trainingMode.value

  try {
    await storeFeatureExampleFromBlob(p.blob, category, cmd, labelIndex, mode, true)
  } catch (e) {
    console.error('Failed to cache feature example:', e)
  }
}

const startGlobalRecording = async () => {
  const stream = await navigator.mediaDevices.getUserMedia({ audio: true })
  mediaRecorder = new MediaRecorder(stream)

  audioChunks = []
  isGlobalRecording.value = true
  timeLeft.value = totalDuration

  mediaRecorder.ondataavailable = (e) => audioChunks.push(e.data)
  mediaRecorder.onstop = async () => {
    const blob = new Blob(audioChunks, { type: mediaRecorder?.mimeType || 'audio/webm' })
    stream.getTracks().forEach((t) => t.stop())

    const load = message.loading('Predicting...', { duration: 0 })
    try {
      const res = await predictRecordedCommand({ blob, category: 'surface' })
      console.log(res)

      if (res.confidence < LOW_CONFIDENCE_MARGIN) {
        message.warning(`Low confidence: ${res.label} (${Math.round(res.confidence * 100)}%)`)
      } else {
        message.success(`Detected: ${res.label} (${Math.round(res.confidence * 100)}%)`)
      }
    } catch (e) {
      console.warn(e)
      message.error('Not recognized. Train first or load a saved model.')
    } finally {
      load.destroy()
    }
  }

  mediaRecorder.start()

  timerInterval = window.setInterval(() => {
    timeLeft.value -= 0.1
    if (timeLeft.value <= 0) {
      if (timerInterval !== null) clearInterval(timerInterval)
      mediaRecorder?.stop()
      isGlobalRecording.value = false
    }
  }, 100)
}

const handleTrainClick = async () => {
  if (!canTrain.value) return message.warning('Need more samples to train.')
  await train()
}

function handleRecorded(p: RecorderPayload) {
  const cmd = normalizeLabel(p.command)
  if (!p.blob) {
    samplesByCommand[cmd] = p.total
    return
  }

  if (cmd === NOISE_LABEL) {
    backgroundNoiseSamples.push(p.blob)
    samplesByCommand[cmd] = p.total
    return
  }

  const mode = trainingMode.value
  if (!voiceSamplesByMode[mode][cmd]) voiceSamplesByMode[mode][cmd] = []
  voiceSamplesByMode[mode][cmd].push(p.blob)
  samplesByCommand[cmd] = p.total

  void storeRecordedSample(p)
}

async function removeFeaturesForCommand(cmd: string, mode: TrainingMode) {
  const category = COMMAND_TO_CATEGORY.get(cmd)
  if (!category) return

  featureStoreByMode[mode][category] = featureStoreByMode[mode][category].filter((x) => x.command !== cmd)
  await deleteFeatureExamplesByCommand(category, cmd, mode)
}

function handleDeleted(p: { command: string; total: number }) {
  const cmd = normalizeLabel(p.command)
  const mode = trainingMode.value

  if (cmd === NOISE_LABEL) {
    backgroundNoiseSamples.splice(0, backgroundNoiseSamples.length)
    samplesByCommand[cmd] = p.total
    return
  }

  voiceSamplesByMode[mode][cmd] = []
  samplesByCommand[cmd] = p.total

  void removeFeaturesForCommand(cmd, mode)
}

onMounted(async () => {
  await ensureBackendReady()
  await Promise.all((['initial', 'personalized'] as TrainingMode[]).map((mode) => hydrateFeatureStoreFromDb(mode)))
  await restoreSavedModels()
})

onBeforeUnmount(() => {
  Object.values(trainedModels.initial).forEach((m) => m?.dispose())
  Object.values(trainedModels.personalized).forEach((m) => m?.dispose())
  MEL_FILTERBANK?.dispose()
  MEL_FILTERBANK = null
})
</script>


<template>
  <div class="voice-page">
    <header class="page-header">
      <div class="page-header__copy">
        <p class="eyebrow">Hands-free odontogram setup</p>
        <h1>Voice Commands</h1>
        <p class="lead">
          Record each command multiple times to build your localized AI model.
        </p>
      </div>

      <div class="page-header__stats">
        <div class="stat-card">
          <span>Commands</span>
          <strong>{{ totalCommands }}</strong>
        </div>
        <div class="stat-card">
          <span>Completed</span>
          <strong>{{ completedCommands }}</strong>
        </div>
        <div class="stat-card">
          <span>Samples</span>
          <strong>{{ totalSamples }}</strong>
        </div>
      </div>
    </header>

    <section v-for="group in groups" :key="group.title" class="command-group">
      <div class="command-group__header">
        <div>
          <h2>{{ group.title }}</h2>
          <p>{{ group.description }}</p>
        </div>
        <span class="group-badge">{{ group.items.length }} items</span>
      </div>

      <div class="command-group__list">
        <VoiceCommandRecorder v-for="item in group.items" :recordingSeconds="item.recordingSeconds || 2"
          :key="item.command" :command="item.command" :subtitle="item.subtitle" :max-samples="15"
          @recorded="handleRecorded" @deleted="handleDeleted" />
      </div>
    </section>

    <!-- FABs -->
    <div class="voice-fab-container">
      <!-- RECORD -->
      <div class="fab-wrapper">
        <div v-if="isGlobalRecording" class="fab-timer-overlay">
          <svg viewBox="0 0 64 64">
            <circle stroke="#18a058" stroke-width="4" fill="transparent" r="28" cx="32" cy="32" stroke-dasharray="175.9"
              :stroke-dashoffset="recordingCircleOffset" />
          </svg>
        </div>

        <button class="fab-btn rec-btn" :class="{ 'is-active': isGlobalRecording }" @click="startGlobalRecording"
          :disabled="isGlobalRecording">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z" />
            <path
              d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z" />
          </svg>
        </button>
      </div>

      <!-- TRAIN -->
      <div class="fab-wrapper">
        <div v-if="trainingState.status === 'training'" class="fab-timer-overlay">
          <svg viewBox="0 0 64 64">
            <circle stroke="#2563eb" stroke-width="4" fill="transparent" r="28" cx="32" cy="32" stroke-dasharray="175.9"
              :stroke-dashoffset="trainingCircleOffset" />
          </svg>
        </div>

        <button class="fab-btn train-btn" :class="{ 'is-training': trainingState.status === 'training' }"
          @click="handleTrainClick" :disabled="trainingState.status === 'training' || !canTrain">
          <span v-if="trainingState.status === 'training'" class="progress-text">
            {{ trainingState.progress }}%
          </span>

          <svg v-else width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
            <path
              d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z" />
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.voice-page {
  min-height: 100vh;
  padding: 24px;
  background: linear-gradient(to bottom, #f8fbff, #f1f5f9);
}

/* HEADER */
.page-header {
  display: flex;
  justify-content: space-between;
  padding: 24px;
  border-radius: 24px;
  background: white;
  border: 1px solid #dbe4f0;
  margin-bottom: 24px;
}

.page-header__stats {
  display: flex;
  gap: 16px;
  align-items: stretch;
}

.stat-card {
  padding: 14px 16px;
  border-radius: 14px;
  background: #f9fbff;
  border: 1px solid #e6edf5;
  text-align: center;
  min-width: 110px;
}

.stat-card span {
  display: block;
  font-size: 12px;
  color: #666;
}

.stat-card strong {
  font-size: 18px;
  display: block;
  margin-top: 4px;
}

/* TYPOGRAPHY */
.eyebrow {
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #64748b;
  margin-bottom: 6px;
}

.lead {
  color: #475569;
  margin-top: 6px;
}

/* GROUPS */
.command-group {
  margin-bottom: 24px;
  padding: 20px;
  background: white;
  border-radius: 20px;
  border: 1px solid #e6edf5;
}

.command-group__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 12px;
}

.group-badge {
  font-size: 12px;
  background: #eef2ff;
  color: #4338ca;
  padding: 4px 10px;
  border-radius: 999px;
  font-weight: 500;
}

.command-group__list {
  display: grid;
  gap: 10px;
}

/* FAB */
.voice-fab-container {
  position: fixed;
  bottom: 32px;
  right: 32px;
  display: flex;
  flex-direction: row-reverse;
  gap: 16px;
  align-items: center;
  z-index: 1000;
}

.fab-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

.fab-btn {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  border: none;
  color: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
}

.fab-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 10px 22px rgba(0, 0, 0, 0.18);
}

.fab-btn:active:not(:disabled) {
  transform: scale(0.95);
}

.rec-btn {
  background: #18a058;
}

.train-btn {
  background: #2563eb;
}

.fab-btn:disabled {
  background: #999;
  cursor: not-allowed;
}

.fab-btn.is-active {
  background: #d03050;
}

/* PERFECTLY CENTERED PROGRESS RING */
.fab-timer-overlay {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  pointer-events: none;
}

.fab-timer-overlay svg {
  width: 64px;
  height: 64px;
  transform: rotate(-90deg);
}

/* TEXT */
.progress-text {
  font-size: 10px;
  font-weight: bold;
}

/* RESPONSIVE */
@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    gap: 16px;
  }

  .voice-fab-container {
    bottom: 16px;
    right: 16px;
  }
}
</style>
