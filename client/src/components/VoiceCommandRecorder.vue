<script setup lang="ts">
import { computed, onBeforeUnmount, ref } from 'vue'
import { Icon } from '@iconify/vue'

type RecordingPayload = {
  command: string
  blob: Blob
  index: number
  total: number
}

const props = withDefaults(
  defineProps<{
    command: string
    subtitle?: string
    maxSamples?: number
    disabled?: boolean
    recordingSeconds?: number
  }>(),
  {
    subtitle: '',
    maxSamples: 10,
    disabled: false,
    recordingSeconds: 2,
  },
)

const emit = defineEmits<{
  (e: 'recorded', payload: RecordingPayload): void
  (e: 'deleted', payload: { command: string; total: number }): void
  (e: 'complete', payload: { command: string; total: number }): void
}>()

const isRecording = ref(false)
const isBusy = ref(false)
const mediaRecorder = ref<MediaRecorder | null>(null)
const chunks = ref<BlobPart[]>([])
const samples = ref<Blob[]>([])
const elapsedSeconds = ref(0)
const timerId = ref<number | null>(null)

const sampleCount = computed(() => samples.value.length)
const isComplete = computed(() => sampleCount.value >= props.maxSamples)

const statusLabel = computed(() => {
  if (isComplete.value) return 'Ready for training'
  if (isRecording.value) return 'Recording'
  if (sampleCount.value > 0) return 'In progress'
  return 'Waiting'
})

const statusIcon = computed(() => {
  if (isComplete.value) return 'mdi:check-decagram'
  if (isRecording.value) return 'mdi:record-circle-outline'
  if (sampleCount.value > 0) return 'mdi:progress-check'
  return 'mdi:circle-outline'
})

const statusClass = computed(() => {
  if (isComplete.value) return 'is-complete'
  if (isRecording.value) return 'is-recording'
  if (sampleCount.value > 0) return 'is-progress'
  return 'is-idle'
})

const hintText = computed(() => {
  if (isComplete.value) return 'All samples captured.'
  if (isRecording.value) return 'Speak the command clearly.'
  if (sampleCount.value > 0) return 'Add more samples for better recognition.'
  return 'Record the command several times with natural variations.'
})

const progress = computed(() => Math.min(100, Math.round((elapsedSeconds.value / props.recordingSeconds) * 100)))
const elapsedLabel = computed(() => `${formatTime(elapsedSeconds.value)} / ${formatTime(props.recordingSeconds)}`)

async function toggleRecording() {
  if (props.disabled || isBusy.value || isComplete.value) return
  if (isRecording.value) {
    stopRecording()
    return
  }
  await startRecording()
}

async function startRecording() {
  if (!navigator.mediaDevices?.getUserMedia) return
  if (isRecording.value) return

  try {
    isBusy.value = true
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true })

    mediaRecorder.value = new MediaRecorder(stream)
    chunks.value = []
    elapsedSeconds.value = 0

    timerId.value = window.setInterval(() => {
      elapsedSeconds.value += 1
      if (elapsedSeconds.value >= props.recordingSeconds) {
        stopRecording()
      }
    }, 1000)

    mediaRecorder.value.ondataavailable = (event) => {
      if (event.data.size > 0) chunks.value.push(event.data)
    }

    mediaRecorder.value.onstop = () => {
      const blob = new Blob(chunks.value, { type: 'audio/webm' })
      samples.value.push(blob)

      const payload = {
        command: props.command,
        blob,
        index: samples.value.length - 1,
        total: samples.value.length,
      }

      emit('recorded', payload)

      if (isComplete.value) {
        emit('complete', { command: props.command, total: samples.value.length })
      }

      cleanupStream()
      isBusy.value = false
    }

    mediaRecorder.value.start()
    isRecording.value = true
  } catch (error) {
    cleanupStream()
    isBusy.value = false
    console.error('Unable to start voice recording', error)
  }
}

function stopRecording() {
  if (!isRecording.value) return

  isRecording.value = false
  if (timerId.value) {
    window.clearInterval(timerId.value)
    timerId.value = null
  }

  try {
    mediaRecorder.value?.stop()
  } catch (error) {
    console.error('Unable to stop voice recording', error)
    cleanupStream()
    isBusy.value = false
  }
}

function deleteLast() {
  if (props.disabled || sampleCount.value === 0 || isBusy.value) return

  samples.value.pop()
  emit('deleted', { command: props.command, total: samples.value.length })
}

function cleanupStream() {
  const stream = mediaRecorder.value?.stream
  if (stream) {
    stream.getTracks().forEach((track) => track.stop())
  }
  mediaRecorder.value = null
}

function formatTime(value: number) {
  const safeValue = Math.max(0, value)
  return `${String(Math.floor(safeValue / 60)).padStart(2, '0')}:${String(safeValue % 60).padStart(2, '0')}`
}

onBeforeUnmount(() => {
  if (timerId.value) window.clearInterval(timerId.value)
  cleanupStream()
})
</script>

<template>
  <article class="vcr-card" :class="{
    'is-recording': isRecording,
    'is-complete': isComplete,
    'is-disabled': disabled,
  }">
    <div class="vcr-main">
      <div class="vcr-text">
        <div class="vcr-title-row">
          <span class="vcr-label">{{ command }}</span>
          <span class="vcr-count">{{ sampleCount }}/{{ maxSamples }}</span>
        </div>

        <p v-if="subtitle" class="vcr-subtitle">
          {{ subtitle }}
        </p>

        <div class="vcr-meta">
          <span class="vcr-status" :class="statusClass">
            <Icon :icon="statusIcon" class="vcr-status-icon" />
            {{ statusLabel }}
          </span>
          <span class="vcr-hint">
            {{ hintText }}
          </span>
        </div>
      </div>

      <div class="vcr-actions">
        <button type="button" class="vcr-btn vcr-btn-record" :class="{ recording: isRecording }"
          :disabled="disabled || isComplete || isBusy" :aria-label="isRecording ? 'Stop recording' : 'Start recording'"
          @click="toggleRecording">
          <Icon :icon="isRecording ? 'mdi:stop' : 'mdi:microphone'" class="vcr-btn-icon" />
        </button>

        <button type="button" class="vcr-btn vcr-btn-delete" :disabled="disabled || sampleCount === 0 || isBusy"
          aria-label="Delete last recording" @click="deleteLast">
          <Icon icon="mdi:trash-can-outline" class="vcr-btn-icon" />
        </button>
      </div>
    </div>

    <div v-if="isRecording" class="vcr-progress-wrap" aria-live="polite">
      <div class="vcr-progress-head">
        <span>Recording</span>
        <span>{{ elapsedLabel }}</span>
      </div>
      <div class="vcr-progress-bar">
        <div class="vcr-progress-fill" :style="{ width: `${progress}%` }" />
      </div>
    </div>
  </article>
</template>

<style scoped>
.vcr-card {
  --border: #dbe3f0;
  --border-hover: #9fb4d6;
  --bg: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
  --shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
  --record: #1d4ed8;
  --record-hover: #2563eb;
  --delete: #e11d48;
  --delete-hover: #f43f5e;
  --complete: #22c55e;
  --complete-bg: rgba(34, 197, 94, 0.08);

  border: 2px solid var(--border);
  border-radius: 20px;
  background: var(--bg);
  box-shadow: var(--shadow);
  padding: 16px 18px;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease,
    transform 0.2s ease,
    background 0.2s ease;
}

.vcr-card:hover {
  transform: translateY(-1px);
  border-color: var(--border-hover);
  box-shadow: 0 14px 34px rgba(15, 23, 42, 0.08);
}

.vcr-card.is-recording {
  border-color: var(--record);
  box-shadow: 0 14px 34px rgba(29, 78, 216, 0.12);
}

.vcr-card.is-complete {
  border-color: var(--complete);
  background: linear-gradient(180deg, #f7fff9 0%, #f2fff5 100%);
  box-shadow: 0 14px 34px rgba(34, 197, 94, 0.12);
}

.vcr-card.is-disabled {
  opacity: 0.7;
}

.vcr-main {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
}

.vcr-text {
  min-width: 0;
  flex: 1;
}

.vcr-title-row {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
}

.vcr-label {
  min-width: 0;
  font-size: 0.98rem;
  font-weight: 650;
  color: #0f172a;
  letter-spacing: -0.01em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.vcr-count {
  flex: 0 0 auto;
  padding: 5px 10px;
  border-radius: 999px;
  font-size: 0.8rem;
  font-weight: 700;
  color: #334155;
  background: #eaf1ff;
}

.vcr-subtitle {
  margin: 8px 0 0;
  font-size: 0.86rem;
  line-height: 1.45;
  color: #64748b;
}

.vcr-meta {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 10px;
}

.vcr-status {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  border-radius: 999px;
  padding: 6px 10px;
  font-size: 0.78rem;
  font-weight: 700;
}

.vcr-status.is-idle {
  color: #475569;
  background: #f1f5f9;
}

.vcr-status.is-progress {
  color: #1e40af;
  background: #dbeafe;
}

.vcr-status.is-recording {
  color: #991b1b;
  background: #fee2e2;
}

.vcr-status.is-complete {
  color: #166534;
  background: #dcfce7;
}

.vcr-status-icon {
  font-size: 1rem;
}

.vcr-hint {
  font-size: 0.8rem;
  color: #64748b;
}

.vcr-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  flex: 0 0 auto;
}

.vcr-btn {
  width: 48px;
  height: 48px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: none;
  border-radius: 16px;
  cursor: pointer;
  transition:
    transform 0.15s ease,
    box-shadow 0.2s ease,
    background 0.2s ease,
    opacity 0.2s ease;
  box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
}

.vcr-btn:hover:not(:disabled) {
  transform: translateY(-1px);
}

.vcr-btn:active:not(:disabled) {
  transform: translateY(0);
}

.vcr-btn:disabled {
  cursor: not-allowed;
  opacity: 0.5;
  box-shadow: none;
}

.vcr-btn-icon {
  font-size: 1.2rem;
}

.vcr-btn-record {
  background: linear-gradient(180deg, #3b82f6 0%, #1d4ed8 100%);
  color: #fff;
}

.vcr-btn-record:hover:not(:disabled) {
  background: linear-gradient(180deg, #60a5fa 0%, #2563eb 100%);
}

.vcr-btn-record.recording {
  background: linear-gradient(180deg, #ef4444 0%, #dc2626 100%);
}

.vcr-btn-delete {
  background: linear-gradient(180deg, #fb7185 0%, #e11d48 100%);
  color: #fff;
}

.vcr-btn-delete:hover:not(:disabled) {
  background: linear-gradient(180deg, #fda4af 0%, #f43f5e 100%);
}

.vcr-progress-wrap {
  margin-top: 14px;
}

.vcr-progress-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
  font-size: 0.78rem;
  font-weight: 700;
  color: #64748b;
}

.vcr-progress-bar {
  height: 8px;
  overflow: hidden;
  border-radius: 999px;
  background: #e2e8f0;
}

.vcr-progress-fill {
  height: 100%;
  border-radius: inherit;
  background: linear-gradient(90deg, #3b82f6 0%, #22c55e 100%);
  transition: width 0.2s ease;
}

@media (max-width: 640px) {
  .vcr-main {
    align-items: flex-start;
    flex-direction: column;
  }

  .vcr-actions {
    width: 100%;
    justify-content: flex-end;
  }
}
</style>
