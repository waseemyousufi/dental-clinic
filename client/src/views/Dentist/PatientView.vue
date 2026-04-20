<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useMessage } from 'naive-ui'

import * as cornerstone from '@cornerstonejs/core'
import {
  init as cornerstoneInit,
  RenderingEngine,
  Enums as csCoreEnums,
} from '@cornerstonejs/core'

import {
  addTool,
  destroy as destroyCornerstoneTools,
  init as cornerstoneToolsInit,
  ToolGroupManager,
  Enums as csToolsEnums,
  PanTool,
  ZoomTool,
  WindowLevelTool,
  StackScrollTool,
} from '@cornerstonejs/tools'

import dicomImageLoader from '@cornerstonejs/dicom-image-loader'
import patientApi from '@/api/patient'

import Odontogram from '@/components/Odontogram.vue'
import PrimaryOdontogram from '@/components/PrimaryOdontogram.vue'

/* =======================================================
   🧠 ROUTE + UI
======================================================= */
const route = useRoute()
const message = useMessage()
const patientId = Number(route.params.id)

/* =======================================================
   🧠 PATIENT STATE
======================================================= */
const patient = ref<any>(null)
const loadingPatient = ref(false)
const isSaving = ref(false)
const clinicalNotes = ref('')

const xrayMeta = reactive({
  modality: 'CT',
  total: 0,
  tool: 'Window/Level',
  latestTakenAt: 'N/A',
})

const xrayPhotos = ref<any[]>([])

const findingTypes = ref([
  {
    id: 1,
    label: 'Caries',
    description: 'Tooth decay',
    color: '#ef4444',
  },
  {
    id: 2,
    label: 'Filling',
    description: 'Restoration',
    color: '#3b82f6',
  },
  {
    id: 3,
    label: 'Extraction',
    description: 'Missing tooth',
    color: '#111827',
  },
])

const activeFindingId = ref(1)

const activeFinding = computed(() => {
  return findingTypes.value.find(f => f.id === activeFindingId.value) || findingTypes.value[0]
})

const activeFindingColor = computed(() => activeFinding.value?.color || '#000')

const odontogramData = ref({})

const activeSection = ref('chart')
const usePrimaryOdontogram = ref(false)

const chartSectionRef = ref<HTMLElement | null>(null)
const xraySectionRef = ref<HTMLElement | null>(null)
const notesSectionRef = ref<HTMLElement | null>(null)
const xrayShellRef = ref<HTMLElement | null>(null)

const setSection = (section: string) => {
  activeSection.value = section
  let el: HTMLElement | null = null
  if (section === 'chart') el = chartSectionRef.value
  if (section === 'xray') el = xraySectionRef.value
  if (section === 'notes') el = notesSectionRef.value

  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
}

const summaryRows = computed(() => {
  return Object.entries(odontogramData.value).map(([tooth, parts]) => {
    return {
      tooth,
      parts: Object.entries(parts as any).filter(([_, color]) => !!color)
    }
  }).filter(row => row.parts.length > 0)
})

/* =======================================================
   📡 FETCH PATIENT
======================================================= */
const fetchPatient = async () => {
  try {
    loadingPatient.value = true

    const res = await patientApi.getPatient(patientId)

    // axios wraps response in .data
    patient.value = res.data

    clinicalNotes.value = res.data?.patient_file?.notes || ''
    odontogramData.value = res.data?.patient_file?.odontogram_data || {}

    xrayMeta.total = xrayImageIds.value.length
  } catch (err) {
    console.error(err)
    message.error('Failed to load patient')
  } finally {
    loadingPatient.value = false
  }
}
/* =======================================================
   💾 SAVE
======================================================= */
const handleSave = async () => {
  try {
    isSaving.value = true

    await patientApi.updatePatient(patientId, {
      ...patient.value,
      patient_file: {
        ...patient.value.patient_file,
        notes: clinicalNotes.value,
        odontogram_data: odontogramData.value,
      },
    })

    message.success('Saved successfully')
  } catch (err) {
    console.error(err)
    message.error('Save failed')
  } finally {
    isSaving.value = false
  }
}

const handlePrintChart = () => window.print()

/* =======================================================
   🧠 CORNERSTONE INIT
======================================================= */
let toolsAdded = false
let cornerstoneReady = false

const ensureCornerstoneReady = async () => {
  if (cornerstoneReady) return

  await cornerstoneInit()
  await cornerstoneToolsInit()

  // link cornerstone
  // @ts-ignore
  dicomImageLoader.external = {
    cornerstone,
  }

  dicomImageLoader.configure({
    useWebWorkers: true,
    maxWebWorkers: navigator.hardwareConcurrency || 4,
  })

  await dicomImageLoader.init()

  if (!toolsAdded) {
    addTool(WindowLevelTool)
    addTool(PanTool)
    addTool(ZoomTool)
    addTool(StackScrollTool)
    toolsAdded = true
  }

  cornerstoneReady = true
}

/* =======================================================
   🖼️ IMAGE IDS (I1 → I302)
======================================================= */
const xrayImageIds = computed(() => {
  const total = 302

  return Array.from({ length: total }, (_, i) => {
    const index = i + 1
    return `wadouri:/2_skull_ct/DICOM/I${index}`
  })
})

/* =======================================================
   🧠 VIEWER STATE
======================================================= */
const xrayViewportRef = ref<HTMLDivElement | null>(null)

let xrayRenderingEngine: RenderingEngine | null = null
let xrayToolGroup: any = null

const xrayRenderingEngineId = 'patient-xray-engine'
const xrayViewportId = 'patient-xray-viewport'
const xrayToolGroupId = 'patient-xray-tool-group'

const xrayState = reactive({
  ready: false,
  activeTool: 'window-level',
  zoom: 1,
  imageCount: 0,
  fullscreen: false,
})

/* =======================================================
   🧹 CLEANUP
======================================================= */
const destroyXrayViewer = () => {
  try {
    xrayRenderingEngine?.destroy?.()
  } catch {}

  xrayRenderingEngine = null

  try {
    ToolGroupManager.destroyToolGroup(xrayToolGroupId)
  } catch {}

  xrayToolGroup = null
  xrayState.ready = false
}

/* =======================================================
   🔄 TOOL SWITCHING
======================================================= */
const setXrayTool = (mode: string) => {
  xrayState.activeTool = mode
  if (!xrayToolGroup) return

  xrayToolGroup.setToolActive(WindowLevelTool.toolName, {
    bindings: [{ mouseButton: csToolsEnums.MouseBindings.Primary }],
  })
  xrayToolGroup.setToolActive(PanTool.toolName, {
    bindings: [{ mouseButton: csToolsEnums.MouseBindings.Auxiliary }],
  })
  xrayToolGroup.setToolActive(ZoomTool.toolName, {
    bindings: [{ mouseButton: csToolsEnums.MouseBindings.Secondary }],
  })
  xrayToolGroup.setToolActive(StackScrollTool.toolName, {
    bindings: [{ mouseButton: csToolsEnums.MouseBindings.Wheel }],
  })
}

/* =======================================================
   🖥️ VIEWER SETUP
======================================================= */
const setupXrayViewer = async () => {
  const element = xrayViewportRef.value
  const imageIds = xrayImageIds.value

  destroyXrayViewer()

  if (!element || imageIds.length === 0) return

  await ensureCornerstoneReady()

  xrayRenderingEngine = new RenderingEngine(xrayRenderingEngineId)

  xrayRenderingEngine.enableElement({
    viewportId: xrayViewportId,
    type: csCoreEnums.ViewportType.STACK,
    element,
  })

  const viewport = xrayRenderingEngine.getViewport(xrayViewportId) as any

  await viewport.setStack(imageIds, 0)
  viewport.render()

  xrayToolGroup = ToolGroupManager.createToolGroup(xrayToolGroupId)

  if (xrayToolGroup) {
    xrayToolGroup.addTool(WindowLevelTool.toolName)
    xrayToolGroup.addTool(PanTool.toolName)
    xrayToolGroup.addTool(ZoomTool.toolName)
    xrayToolGroup.addTool(StackScrollTool.toolName)

    xrayToolGroup.addViewport(xrayViewportId, xrayRenderingEngineId)

    setXrayTool('window-level')
  }

  xrayState.imageCount = imageIds.length
  xrayState.ready = true
}

/* =======================================================
   🎛️ EXTRA CONTROLS
======================================================= */
const handleResetXray = () => {
  setupXrayViewer()
}

const syncXrayLayout = () => {
  const viewport = xrayRenderingEngine?.getViewport(xrayViewportId) as any
  viewport?.resetCamera?.()
  viewport?.render?.()
}

const handleToggleFullscreen = () => {
  xrayState.fullscreen = !xrayState.fullscreen
}

/* =======================================================
   🚀 LIFECYCLE
======================================================= */
onMounted(async () => {
  await fetchPatient()
  await nextTick()
  await setupXrayViewer()
})

onBeforeUnmount(() => {
  destroyXrayViewer()
  try {
    destroyCornerstoneTools()
  } catch {}
})
</script>
<template>
  <div class="patient-page">
    <header class="hero-card">
      <div class="hero-copy">
        <p class="eyebrow">Dental chart</p>
        <h1>
          {{ patient?.fName }} {{ patient?.lName }}
          <span class="muted">#{{ patient?.id }}</span>
        </h1>
        <p class="subline">
          <span>Age: {{ patient?.age ?? 'N/A' }}</span>
          <span>Phone: {{ patient?.phone || 'N/A' }}</span>
          <span>Mode: {{ usePrimaryOdontogram ? 'Primary teeth' : 'Permanent teeth' }}</span>
        </p>
      </div>

      <div class="hero-actions">
        <button class="btn btn-ghost" @click="handlePrintChart">Print chart</button>
        <button class="btn btn-primary" @click="handleSave" :disabled="isSaving">
          {{ isSaving ? 'Saving…' : 'Save changes' }}
        </button>
      </div>
    </header>

    <nav class="section-nav" aria-label="Patient sections">
      <button class="nav-chip" :class="{ active: activeSection === 'chart' }" @click="setSection('chart')">
        Odontogram
      </button>
      <button class="nav-chip" :class="{ active: activeSection === 'xray' }" @click="setSection('xray')">
        X-rays
      </button>
      <button class="nav-chip" :class="{ active: activeSection === 'notes' }" @click="setSection('notes')">
        Notes
      </button>
    </nav>

    <div v-if="loadingPatient" class="state-card">
      Loading patient data…
    </div>

    <div v-else-if="!patient" class="state-card error">
      Patient not found or an error occurred.
    </div>

    <main v-else class="layout-grid">
      <section class="stack-column">
        <section ref="chartSectionRef" class="surface-card chart-card">
          <div class="section-head">
            <div>
              <p class="section-kicker">Chart</p>
              <h2>Dental odontogram</h2>
            </div>
            <div class="segmented-wrap">
              <button v-for="finding in findingTypes" :key="finding.id" class="segmented-pill"
                :class="{ active: activeFindingId === finding.id }" @click="activeFindingId = finding.id">
                <span class="dot" :style="{ backgroundColor: finding.color }"></span>
                <span>{{ finding.label }}</span>
              </button>
            </div>
          </div>

          <div class="chart-shell">
            <div class="chart-panel">
              <div class="chart-panel__meta">
                <span class="badge" :style="{ backgroundColor: activeFindingColor }">{{ activeFinding.label || '' }}</span>
                <span class="badge muted-badge">{{ activeFinding.description }}</span>
              </div>

              <div class="odontogram-wrap">
                <PrimaryOdontogram v-if="usePrimaryOdontogram" v-model="odontogramData"
                  :active-finding="activeFindingColor" />
                <Odontogram v-else v-model="odontogramData" :active-finding="activeFindingColor" />
              </div>
            </div>
          </div>
        </section>

        <section ref="xraySectionRef" class="surface-card xray-card">
          <div class="section-head section-head--xray">
            <div>
              <p class="section-kicker">Imaging</p>
              <h2>X-ray viewer</h2>
            </div>

            <div class="xray-mini-meta">
              <span class="mini-chip">{{ xrayMeta.modality }}</span>
              <span class="mini-chip">{{ xrayMeta.total }} images</span>
              <span class="mini-chip">Tool: {{ xrayMeta.tool }}</span>
            </div>
          </div>

          <div class="xray-shell" ref="xrayShellRef">
            <div class="xray-canvas-card">
              <div ref="xrayViewportRef" class="xray-viewport"></div>

              <div v-if="!xrayState.ready" class="xray-empty">
                <strong>No X-ray images loaded yet.</strong>
                <span>Connect patient x-ray imageIds from your API response to render them here.</span>
              </div>
            </div>

            <aside class="xray-controls">
              <div class="control-card">
                <p class="control-title">Viewer controls</p>
                <div class="tool-buttons">
                  <button class="tool-btn" :class="{ active: xrayState.activeTool === 'window-level' }"
                    @click="setXrayTool('window-level')">
                    Window / Level
                  </button>
                  <button class="tool-btn" :class="{ active: xrayState.activeTool === 'pan' }"
                    @click="setXrayTool('pan')">
                    Pan
                  </button>
                  <button class="tool-btn" :class="{ active: xrayState.activeTool === 'zoom' }"
                    @click="setXrayTool('zoom')">
                    Zoom
                  </button>
                  <button class="tool-btn" :class="{ active: xrayState.activeTool === 'scroll' }"
                    @click="setXrayTool('scroll')">
                    Scroll
                  </button>
                </div>

                <div class="action-row">
                  <button class="tool-btn secondary" @click="handleResetXray">Reset</button>
                  <button class="tool-btn secondary" @click="syncXrayLayout">Fit</button>
                  <button class="tool-btn secondary" @click="handleToggleFullscreen">
                    {{ xrayState.fullscreen ? 'Exit fullscreen' : 'Fullscreen' }}
                  </button>
                </div>
              </div>

              <div class="control-card">
                <p class="control-title">Useful data</p>
                <dl class="meta-grid">
                  <div>
                    <dt>Patient</dt>
                    <dd>{{ patient.fName }} {{ patient.lName }}</dd>
                  </div>
                  <div>
                    <dt>Study date</dt>
                    <dd>{{ xrayMeta.latestTakenAt }}</dd>
                  </div>
                  <div>
                    <dt>Images</dt>
                    <dd>{{ xrayMeta.total }}</dd>
                  </div>
                  <div>
                    <dt>Zoom</dt>
                    <dd>{{ xrayState.zoom.toFixed(2) }}×</dd>
                  </div>
                </dl>
              </div>

              <div v-if="xrayPhotos.length" class="control-card">
                <p class="control-title">Available X-rays</p>
                <div class="photo-list">
                  <button v-for="photo in xrayPhotos" :key="photo.id" class="photo-row">
                    <span class="photo-label">{{ photo.label || 'X-ray photo' }}</span>
                    <span class="photo-sub">{{ photo.modality || 'XR' }}</span>
                  </button>
                </div>
              </div>
            </aside>
          </div>
        </section>
      </section>

      <aside class="side-column">
        <section ref="notesSectionRef" class="surface-card notes-card">
          <div class="section-head compact">
            <div>
              <p class="section-kicker">Clinical</p>
              <h2>Notes</h2>
            </div>
          </div>
          <textarea v-model="clinicalNotes" class="notes-input"
            placeholder="Enter observations, treatment plan, or clinical notes…" />
        </section>

        <section class="surface-card summary-card">
          <div class="section-head compact">
            <div>
              <p class="section-kicker">Summary</p>
              <h2>Marked findings</h2>
            </div>
          </div>

          <div v-if="summaryRows.length === 0" class="empty-summary">
            No findings marked yet.
          </div>

          <div v-else class="summary-list">
            <article v-for="row in summaryRows" :key="row.tooth" class="summary-item">
              <div class="summary-item__head">
                <strong>Tooth {{ row.tooth }}</strong>
                <span class="summary-count">{{ row.parts.length }} surfaces</span>
              </div>
              <div class="surface-tags">
                <span v-for="([part, color]) in row.parts" :key="part" class="surface-tag"
                  :style="{ borderColor: color, color }">
                  {{ part }}
                </span>
              </div>
            </article>
          </div>
        </section>
      </aside>
    </main>
  </div>
</template>

<style scoped lang="scss">
:global(*) {
  box-sizing: border-box;
}

.patient-page {
  min-height: 100vh;
  padding: 16px;
  background:
    radial-gradient(circle at top left, rgba(24, 144, 255, 0.1), transparent 28%),
    linear-gradient(180deg, #f6f8fc 0%, #eef2f7 100%);
  color: #102033;
}

.hero-card,
.surface-card,
.state-card {
  border: 1px solid rgba(16, 32, 51, 0.08);
  border-radius: 24px;
  background: rgba(255, 255, 255, 0.88);
  box-shadow: 0 18px 48px rgba(16, 32, 51, 0.08);
  backdrop-filter: blur(14px);
}

.hero-card {
  display: flex;
  flex-direction: column;
  gap: 16px;
  padding: 18px;
}

.eyebrow,
.section-kicker,
.control-title {
  margin: 0;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  font-size: 0.72rem;
  color: #6b7a90;
}

.hero-copy h1,
.section-head h2 {
  margin: 6px 0 0;
  line-height: 1.15;
}

.hero-copy h1 {
  font-size: clamp(1.4rem, 3vw, 2.2rem);
}

.muted {
  color: #8090a4;
  font-weight: 600;
  font-size: 0.95em;
}

.subline {
  display: flex;
  flex-wrap: wrap;
  gap: 10px 14px;
  margin: 10px 0 0;
  color: #526072;
  font-size: 0.95rem;
}

.subline span {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.hero-actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.btn {
  appearance: none;
  border: 0;
  border-radius: 16px;
  padding: 12px 16px;
  font-weight: 700;
  cursor: pointer;
  transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
}

.btn:active,
.nav-chip:active,
.segmented-pill:active,
.tool-btn:active,
.photo-row:active {
  transform: translateY(1px);
}

.btn-ghost {
  background: #ffffff;
  color: #102033;
  border: 1px solid rgba(16, 32, 51, 0.1);
}

.btn-primary {
  background: linear-gradient(135deg, #1890ff, #0f6fe5);
  color: #fff;
  box-shadow: 0 12px 24px rgba(24, 144, 255, 0.24);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  box-shadow: none;
}

.section-nav {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
  margin: 14px 0;
}

.nav-chip {
  border: 1px solid rgba(16, 32, 51, 0.08);
  border-radius: 999px;
  padding: 12px 10px;
  background: rgba(255, 255, 255, 0.8);
  color: #405164;
  font-weight: 700;
  cursor: pointer;
}

.nav-chip.active {
  background: #102033;
  color: #fff;
  border-color: #102033;
}

.state-card {
  padding: 28px;
  text-align: center;
  font-weight: 600;
  color: #526072;
}

.state-card.error {
  color: #b42318;
}

.layout-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 16px;
}

.stack-column,
.side-column {
  display: grid;
  gap: 16px;
}

.surface-card {
  padding: 16px;
}

.section-head {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 14px;
}

.section-head.compact {
  margin-bottom: 12px;
}

.segmented-wrap {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.segmented-pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  border: 1px solid rgba(16, 32, 51, 0.08);
  background: #f8fafc;
  color: #405164;
  border-radius: 999px;
  padding: 10px 12px;
  font-size: 0.84rem;
  font-weight: 700;
  cursor: pointer;
}

.segmented-pill.active {
  background: #e6f4ff;
  color: #0f6fe5;
  border-color: rgba(15, 111, 229, 0.28);
}

.dot {
  width: 10px;
  height: 10px;
  border-radius: 999px;
  flex: none;
}

.chart-shell,
.xray-shell {
  display: grid;
  gap: 16px;
}

.chart-panel {
  display: grid;
  gap: 14px;
}

.chart-panel__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.badge,
.mini-chip {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 8px 12px;
  font-size: 0.82rem;
  font-weight: 700;
}

.badge {
  color: #fff;
}

.muted-badge,
.mini-chip {
  background: #edf2f7;
  color: #526072;
}

.odontogram-wrap {
  overflow-x: auto;
  padding-bottom: 4px;
}

.xray-card .section-head--xray {
  align-items: flex-start;
}

.xray-mini-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.xray-canvas-card {
  position: relative;
  min-height: 280px;
  border-radius: 22px;
  overflow: hidden;
  border: 1px solid rgba(16, 32, 51, 0.08);
  background: #0b1220;
}

.xray-viewport {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
}

.xray-empty {
  position: absolute;
  inset: 0;
  display: grid;
  place-content: center;
  gap: 8px;
  padding: 24px;
  text-align: center;
  color: rgba(255, 255, 255, 0.8);
  background: linear-gradient(180deg, rgba(11, 18, 32, 0.1), rgba(11, 18, 32, 0.45));
}

.xray-controls {
  display: grid;
  gap: 12px;
}

.control-card {
  border: 1px solid rgba(16, 32, 51, 0.08);
  border-radius: 20px;
  background: #fff;
  padding: 14px;
}

.tool-buttons,
.action-row,
.photo-list {
  display: grid;
  gap: 8px;
}

.tool-buttons {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.action-row {
  margin-top: 10px;
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.tool-btn {
  border: 1px solid rgba(16, 32, 51, 0.1);
  background: #f8fafc;
  color: #102033;
  border-radius: 14px;
  padding: 11px 12px;
  font-weight: 700;
  cursor: pointer;
}

.tool-btn.active {
  background: #e6f4ff;
  color: #0f6fe5;
  border-color: rgba(15, 111, 229, 0.28);
}

.tool-btn.secondary {
  background: #fff;
}

.meta-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
  margin: 0;
}

.meta-grid dt {
  font-size: 0.74rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #7c8b9d;
  margin-bottom: 4px;
}

.meta-grid dd {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 700;
  color: #102033;
}

.photo-row {
  width: 100%;
  text-align: left;
  border: 1px solid rgba(16, 32, 51, 0.08);
  background: #f8fafc;
  border-radius: 14px;
  padding: 11px 12px;
  cursor: pointer;
  display: flex;
  justify-content: space-between;
  gap: 8px;
}

.photo-label {
  font-weight: 700;
  color: #102033;
}

.photo-sub {
  font-size: 0.85rem;
  color: #6b7a90;
}

.notes-input {
  width: 100%;
  min-height: 180px;
  resize: vertical;
  border: 1px solid rgba(16, 32, 51, 0.12);
  border-radius: 18px;
  padding: 14px;
  outline: none;
  background: #fbfcfe;
  color: #102033;
  font: inherit;
}

.notes-input:focus {
  border-color: rgba(15, 111, 229, 0.45);
  box-shadow: 0 0 0 4px rgba(24, 144, 255, 0.08);
}

.empty-summary {
  color: #6b7a90;
  padding: 10px 2px;
}

.summary-list {
  display: grid;
  gap: 12px;
}

.summary-item {
  padding: 14px;
  border-radius: 18px;
  border: 1px solid rgba(16, 32, 51, 0.08);
  background: #fbfcfe;
}

.summary-item__head {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  align-items: center;
  margin-bottom: 10px;
}

.summary-count {
  font-size: 0.8rem;
  color: #7c8b9d;
}

.surface-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.surface-tag {
  border: 1px solid currentColor;
  border-radius: 999px;
  padding: 6px 10px;
  font-size: 0.8rem;
  font-weight: 700;
  background: rgba(255, 255, 255, 0.78);
}

@media (min-width: 768px) {
  .patient-page {
    padding: 20px;
  }

  .hero-card {
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
    padding: 22px;
  }

  .section-nav {
    display: none;
  }

  .layout-grid {
    grid-template-columns: minmax(0, 1fr);
    gap: 18px;
  }

  .section-head {
    flex-direction: row;
    justify-content: space-between;
    align-items: flex-start;
  }

  .xray-shell {
    grid-template-columns: minmax(0, 1fr) 320px;
    align-items: start;
  }

  .xray-canvas-card {
    min-height: 420px;
  }

  .tool-buttons {
    grid-template-columns: 1fr 1fr;
  }
}

@media (min-width: 1180px) {
  .patient-page {
    padding: 24px;
  }

  .layout-grid {
    grid-template-columns: minmax(0, 1fr) 360px;
    align-items: start;
  }

  .side-column {
    position: sticky;
    top: 24px;
  }

  .stack-column {
    gap: 18px;
  }

  .xray-shell {
    grid-template-columns: minmax(0, 1fr) 340px;
  }
}

@media (max-width: 767px) {
  .xray-shell {
    grid-template-columns: 1fr;
  }

  .tool-buttons,
  .action-row,
  .meta-grid {
    grid-template-columns: 1fr 1fr;
  }

  .action-row {
    grid-template-columns: 1fr;
  }
}

@media print {

  .section-nav,
  .hero-actions,
  .xray-controls,
  .photo-list,
  .btn,
  .tool-btn {
    display: none !important;
  }

  .patient-page {
    background: #fff;
    padding: 0;
  }

  .hero-card,
  .surface-card {
    box-shadow: none;
    background: #fff;
  }
}
</style>
