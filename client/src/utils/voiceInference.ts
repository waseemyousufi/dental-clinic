import * as tf from '@tensorflow/tfjs'
import '@tensorflow/tfjs-backend-webgl'

type Category = 'tooth' | 'surface' | 'condition'

const SAMPLE_RATE = 16000
const TARGET_SAMPLES = SAMPLE_RATE * 1

const FRAME_LENGTH = 480
const FRAME_STEP = 160
const FFT_LENGTH = 512
const MEL_BINS = 40
const FEATURE_FRAMES = 97

const FEATURE_SHAPE: [number, number, number] = [FEATURE_FRAMES, MEL_BINS, 1]

let audioContext: AudioContext | null = null
let MEL_FILTERBANK: tf.Tensor2D | null = null

const MODEL_PREFIX = 'dental-voice-dscnn'

function getModelKey(mode: 'initial' | 'personalized', category: Category) {
  const prefix = mode === 'initial' ? 'base' : 'user'
  return `indexeddb://${MODEL_PREFIX}-${prefix}-${category}`
}

function getLabelKey(mode: 'initial' | 'personalized', category: Category) {
  const prefix = mode === 'initial' ? 'base' : 'user'
  return `${MODEL_PREFIX}-${prefix}:${category}:labels`
}

function getAudioContext() {
  if (!audioContext) audioContext = new AudioContext()
  return audioContext
}

/**
 * ======================
 * AUDIO PREPROCESSING
 * ======================
 */

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
  const out = new Float32Array(length)
  out.set(input.slice(0, length))
  return out
}

function trimSilence(input: Float32Array): Float32Array {
  let peak = 0
  for (let i = 0; i < input.length; i++) {
    peak = Math.max(peak, Math.abs(input[i]!))
  }

  const threshold = Math.max(0.008, peak * 0.06)

  let start = 0
  let end = input.length - 1

  while (start < input.length && Math.abs(input[start]!) < threshold) start++
  while (end > start && Math.abs(input[end]!) < threshold) end--

  return input.slice(start, end + 1)
}

function preEmphasis(input: Float32Array) {
  const out = new Float32Array(input.length)
  out[0] = input[0]!
  for (let i = 1; i < input.length; i++) {
    out[i] = input[i]! - 0.97 * input[i - 1]!
  }
  return out
}

function peakNormalize(input: Float32Array) {
  let peak = 0
  for (let i = 0; i < input.length; i++) {
    peak = Math.max(peak, Math.abs(input[i]!))
  }
  if (peak < 1e-6) return input

  const gain = 0.95 / peak
  const out = new Float32Array(input.length)
  for (let i = 0; i < input.length; i++) out[i] = input[i]! * gain
  return out
}

async function blobToWaveform(blob: Blob): Promise<Float32Array> {
  const arrayBuffer = await blob.arrayBuffer()
  const ctx = getAudioContext()
  const decoded = await ctx.decodeAudioData(arrayBuffer)

  const mono = new Float32Array(decoded.length)
  for (let i = 0; i < decoded.length; i++) {
    let sum = 0
    for (let ch = 0; ch < decoded.numberOfChannels; ch++) {
      sum += decoded.getChannelData(ch)?.[i] || 0
    }
    mono[i] = sum / decoded.numberOfChannels
  }

  const resampled = resampleLinear(mono, decoded.sampleRate, SAMPLE_RATE)
  const trimmed = trimSilence(resampled)
  const emphasized = preEmphasis(trimmed)
  const normalized = peakNormalize(emphasized)

  return padOrTrim(normalized, TARGET_SAMPLES)
}

/**
 * ======================
 * FEATURE EXTRACTION
 * ======================
 */

function hzToMel(hz: number) {
  return 2595 * Math.log10(1 + hz / 700)
}

function melToHz(mel: number) {
  return 700 * (Math.pow(10, mel / 2595) - 1)
}

function createMelFilterbank(): tf.Tensor2D {
  const numSpectrogramBins = Math.floor(FFT_LENGTH / 2) + 1

  const melPoints: number[] = []
  const lowMel = hzToMel(20)
  const highMel = hzToMel(SAMPLE_RATE / 2)

  for (let i = 0; i < MEL_BINS + 2; i++) {
    melPoints.push(lowMel + (i / (MEL_BINS + 1)) * (highMel - lowMel))
  }

  const hzPoints = melPoints.map(melToHz)
  const bin = hzPoints.map((hz) => Math.floor(((FFT_LENGTH + 1) * hz) / SAMPLE_RATE))

  const filterbank: number[][] = []

  for (let i = 0; i < MEL_BINS; i++) {
    const row = new Array(numSpectrogramBins).fill(0)

    for (let k = bin[i]!; k < bin[i + 1]!; k++) {
      row[k] = (k - bin[i]!) / (bin[i + 1]! - bin[i]!)
    }

    for (let k = bin[i + 1]!; k < bin[i + 2]!; k++) {
      row[k] = (bin[i + 2]! - k) / (bin[i + 2]! - bin[i + 1]!)
    }

    filterbank.push(row)
  }

  return tf.tensor2d(filterbank).transpose()
}

async function ensureBackendReady() {
  await tf.ready()
  await tf.setBackend('webgl')

  if (!MEL_FILTERBANK) {
    MEL_FILTERBANK = createMelFilterbank()
  }
}

async function waveformToFeatures(waveform: Float32Array): Promise<Float32Array> {
  const tensor = tf.tidy(() => {
    const input = tf.tensor1d(waveform)

    const stft = tf.signal.stft(input, FRAME_LENGTH, FRAME_STEP, FFT_LENGTH)
    const magnitude = tf.abs(stft)

    let mel = tf.matMul(magnitude, MEL_FILTERBANK!)

    if (mel.shape[0] > FEATURE_FRAMES) {
      mel = mel.slice([0, 0], [FEATURE_FRAMES, MEL_BINS])
    } else if (mel.shape[0] < FEATURE_FRAMES) {
      mel = tf.pad(mel, [[0, FEATURE_FRAMES - mel.shape[0]], [0, 0]])
    }

    const logMel = tf.log(mel.add(1e-6))
    const mean = tf.mean(logMel)
    const std = tf.sqrt(tf.mean(tf.square(logMel.sub(mean))))

    return logMel.sub(mean).div(std.add(1e-6)).expandDims(-1)
  })

  const data = await tensor.data()
  tensor.dispose()

  return Float32Array.from(data)
}

/**
 * ======================
 * MODEL LOADING + PREDICT
 * ======================
 */

export async function loadModel(category: Category) {
  await ensureBackendReady()

  try {
    return await tf.loadLayersModel(getModelKey('personalized', category))
  } catch {
    return await tf.loadLayersModel(getModelKey('initial', category))
  }
}

export function loadLabels(category: Category): string[] {
  return (
    JSON.parse(localStorage.getItem(getLabelKey('personalized', category)) || 'null') ||
    JSON.parse(localStorage.getItem(getLabelKey('initial', category)) || 'null') ||
    []
  )
}

export async function predict(blob: Blob, category: Category) {
  const model = await loadModel(category)
  const labels = loadLabels(category)

  const waveform = await blobToWaveform(blob)
  const features = await waveformToFeatures(waveform)

  const input = tf.tensor4d(features, [1, FEATURE_SHAPE[0], FEATURE_SHAPE[1], FEATURE_SHAPE[2]])
  const output = model.predict(input) as tf.Tensor

  const probs = Array.from(await output.data())

  input.dispose()
  output.dispose()

  const bestIdx = probs.indexOf(Math.max(...probs))

  return {
    label: labels[bestIdx],
    probabilities: probs,
  }
}
