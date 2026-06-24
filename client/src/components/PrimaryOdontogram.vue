<script setup lang="ts">
import { computed, ref, nextTick } from 'vue'
import DOMPurify from 'dompurify'

defineOptions({
  name: 'PrimaryOdontogram'
})

const safeSvg = (svg: string) => DOMPurify.sanitize(svg)

export type ToothState = {
  [partId: string]: string | { color: string; id: string }
  symbols: {
    id: string
    svg: string
    slug?: string
    color: string
    position?: 'crown' | 'root' | 'auto'
  }[]
}

export interface OdontogramState {
  [toothNumber: number]: ToothState
}

const props = defineProps<{
  modelValue: OdontogramState
  slug: string
  readonly?: boolean
  patient?: any
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: OdontogramState): void
  (e: 'tooth-click', tooth: number, part: string): void
}>()

const state = computed({
  get: () => props.modelValue || {},
  set: (val) => emit('update:modelValue', val)
})

const wrapperRef = ref<HTMLElement | null>(null)

const slugPositionMap: Record<string, 'crown' | 'root'> = {
  abscess: 'root',
  root_canal: 'root',
  unerupted: 'root',
  impacted_tooth: 'crown',
  implant: 'crown',
  extraction: 'crown',
  missing: 'crown',
  caries: 'crown',
  recurrent_caries: 'crown',
  sealant: 'crown',
  amalgam_filling: 'crown',
  full_crown: 'crown',
  fracture: 'crown',
  dental_bridge: 'crown',
  orthodontic_brackets: 'crown',
  gingival_recession: 'crown',
  diastema: 'crown',
  loose_tooth: 'crown'
}

const resolveSymbolPosition = (
  slug: string,
  explicit?: 'crown' | 'root' | 'auto'
): 'crown' | 'root' => {
  if (explicit && explicit !== 'auto') return explicit
  return slugPositionMap[slug] || 'crown'
}

// Primary Tooth Rows (FDI Notation)
const upperRow = [55, 54, 53, 52, 51, 61, 62, 63, 64, 65]
const lowerRow = [85, 84, 83, 82, 81, 71, 72, 73, 74, 75]

const getToothType = (num: number) => {
  const d = num % 10
  return (d === 4 || d === 5) ? 'molar' : 'incisor'
}

const toothConfigs: any = {
  molar: {
    width: 30, height: 50,
    parts: [
      { id: 'top', points: '0,0 30,0 20,10 10,10' },
      { id: 'left', points: '0,0 10,10 10,20 0,30' },
      { id: 'bottom', points: '0,30 10,20 20,20 30,30' },
      { id: 'right', points: '30,0 20,10 20,20 30,30' },
      { id: 'center', points: '10,10 20,10 20,20 10,20' },
      { id: 'root-1', points: '0,30 5,50 10,30' },
      { id: 'root-2', points: '10,30 15,50 20,30' },
      { id: 'root-3', points: '20,30 25,50 30,30' }
    ]
  },
  incisor: {
    width: 20, height: 50,
    parts: [
      { id: 'top', points: '0,0 20,0 15,15 5,15' },
      { id: 'left', points: '0,0 5,15 0,30' },
      { id: 'bottom', points: '0,30 5,15 15,15 20,30' },
      { id: 'right', points: '20,0 15,15 20,30' },
      { id: 'root-1', points: '0,30 10,50 20,30' }
    ]
  }
}

const getSymbolTransform = (toothNumber: number, symbol: any, isUpper: boolean) => {
  const type = getToothType(toothNumber)
  const config = toothConfigs[type]
  const w = config.width
  const h = config.height
  let scale = w / 100
  const pos = resolveSymbolPosition(symbol.slug, symbol.position)
  const x = w / 2
  let y = h / 2

  if (isUpper) {
    y = pos === 'root' ? h * 0.85 : h * 0.25
    scale *= -1
  } else {
    y = pos === 'crown' ? h * 0.25 : h * 0.85
  }
  const iconFlip = isUpper ? 'scale(1, -1)' : ''

  return `translate(${x}, ${y}) ${iconFlip} scale(${scale * 0.9}) translate(-50,-50)`
}

const getPartStyle = (toothNumber: number, partId: string) => {
  const data = state.value[toothNumber]?.[partId]
  const color = typeof data === 'object' ? data?.color : data
  return {
    fill: color || '#ffffff',
    transition: 'fill 0.2s ease'
  }
}

// Printing Logic
const triggerPrint = async () => {
  await nextTick()

  await new Promise(resolve =>
    requestAnimationFrame(resolve)
  )

  if (!wrapperRef.value) return

  const printWindow = window.open('', '_blank', 'width=1200,height=900')

  if (!printWindow) return

  const chartHtml = wrapperRef.value.innerHTML

  printWindow.document.write(`
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Odontogram</title>

<style>
html,
body {
  margin: 0;
  padding: 0;
  background: white;
  font-family: Arial, sans-serif;
}

.print-container {
  padding: 30px;
  box-sizing: border-box;
}

.patient-info-header {
  text-align: center;
  margin-bottom: 40px;
  border-bottom: 2px solid #e5e5e5;
  padding-bottom: 20px;
}

.patient-info-header h1 {
  margin: 0 0 16px;
  font-size: 32px;
  font-weight: 700;
}

.patient-details {
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  gap: 24px;
}

.detail-item {
  font-size: 14px;
}

.print-odontogram-content {
  display: flex;
  justify-content: center;
  margin-top: 20px;
}

/* odontogram styles only */

.odontogram-table {
  border-collapse: collapse;
  margin: auto;
}

.odontogram-table td {
  padding: 2px;
  text-align: center;
  border-right: 1px solid #f0f0f0;
}

.odontogram-table td:nth-child(5) {
  border-right: 3px solid #bfbfbf;
}

.num-row {
  font-size: 10px;
  font-weight: bold;
  color: #595959;
}

.tooth-part {
  stroke: #8c8c8c;
  stroke-width: .5;
}

.symbol-layer {
  pointer-events: none;
}

svg {
  overflow: visible;
}

@media print {
  html,
  body {
    margin: 0;
    padding: 0;
  }

  .print-container {
    padding-top: 1em !important;
  }

  /* IMPORTANT */
  * {
    // transform: none !important;
  }

  @page {
  margin: 20mm 10mm 10mm 10mm;
}
}
</style>
</head>

<body>

<div class="print-container">

  ${
    props.patient
      ? `
    <div class="patient-info-header">
      <h1>
        ${props.patient.fName || props.patient.f_name || ''}
        ${props.patient.lName || props.patient.l_name || ''}
      </h1>

      <div class="patient-details">
        <div class="detail-item">
          Gender: ${props.patient.gender || '—'}
        </div>

        <div class="detail-item">
          Blood Type: ${props.patient.bloodType || '—'}
        </div>

        <div class="detail-item">
          Phone: ${props.patient.phone || '—'}
        </div>

        <div class="detail-item">
          Date: ${new Date().toLocaleDateString()}
        </div>
      </div>
    </div>
  `
      : ''
  }

  <div class="print-odontogram-content">
    ${chartHtml}
  </div>

</div>

<script>
window.onload = async () => {

  const images = Array.from(document.images);

  await Promise.all(
    images.map(img => {
      if (img.complete) return Promise.resolve();

      return new Promise(resolve => {
        img.onload = resolve;
        img.onerror = resolve;
      });
    })
  );

  if (document.fonts) {
    await document.fonts.ready;
  }

  await new Promise(resolve =>
    requestAnimationFrame(() =>
      requestAnimationFrame(resolve)
    )
  );

  setTimeout(() => {
    window.focus();
    window.print();
  }, 300);
};
<\/script>

</body>
</html>
`)

  printWindow.document.close()
}

</script>

<template>
  <div class="odontogram-wrapper" ref="wrapperRef">
    <table class="odontogram-table">
      <tbody>
        <tr class="num-row">
          <td v-for="num in upperRow" :key="num">{{ num }}</td>
        </tr>
        <tr>
          <td v-for="num in upperRow" :key="num">
            <svg :width="toothConfigs[getToothType(num)].width" :height="toothConfigs[getToothType(num)].height"
              :viewBox="`0 0 ${toothConfigs[getToothType(num)].width} ${toothConfigs[getToothType(num)].height}`">
              <g :transform="`translate(0, ${toothConfigs[getToothType(num)].height}) scale(1,-1)`">
                <polygon v-for="p in toothConfigs[getToothType(num)].parts" :key="p.id" :points="p.points"
                  class="tooth-part" :style="getPartStyle(num, p.id)" @click="emit('tooth-click', num, p.id)" />
                <g class="symbol-layer">
                  <g v-for="symbol in state[num]?.symbols || []" :key="symbol.id"
                    :transform="getSymbolTransform(num, symbol, true)">
                    <path :d="safeSvg(symbol.svg)" stroke="#ff0000" fill="none" stroke-width="2"
                      vector-effect="non-scaling-stroke" />
                  </g>
                </g>
              </g>
            </svg>
          </td>
        </tr>
        <tr>
          <td v-for="num in lowerRow" :key="num">
            <svg :width="toothConfigs[getToothType(num)].width" :height="toothConfigs[getToothType(num)].height"
              :viewBox="`0 0 ${toothConfigs[getToothType(num)].width} ${toothConfigs[getToothType(num)].height}`">
              <g>
                <polygon v-for="p in toothConfigs[getToothType(num)].parts" :key="p.id" :points="p.points"
                  class="tooth-part" :style="getPartStyle(num, p.id)" @click="emit('tooth-click', num, p.id)" />
              </g>
              <g class="symbol-layer">
                <g v-for="symbol in state[num]?.symbols || []" :key="symbol.id"
                  :transform="getSymbolTransform(num, symbol, false)">
                  <path :d="safeSvg(symbol.svg)" stroke="#ff0000" fill="none" stroke-width="2"
                    vector-effect="non-scaling-stroke" />
                </g>
              </g>
            </svg>
          </td>
        </tr>
        <tr class="num-row">
          <td v-for="num in lowerRow" :key="num">{{ num }}</td>
        </tr>
      </tbody>
    </table>
  </div>

  <div class="print-controls">
    <button type="button" @click="triggerPrint">
      Print Odontogram
    </button>
  </div>
</template>

<style scoped>
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

.odontogram-table {
  border-collapse: collapse;
  margin: auto;

}

.odontogram-table td {
  padding: 2px;
  text-align: center;
  border-right: 1px solid #f0f0f0;
}

.odontogram-table td:nth-child(5) {
  border-right: 3px solid #bfbfbf;
}

.num-row {
  font-size: 10px;
  font-weight: bold;
  color: #595959;
}

.tooth-part {
  stroke: #8c8c8c;
  stroke-width: 0.5;
  cursor: pointer;
}

.tooth-part:hover {
  stroke-width: 1;
  stroke: #40a9ff;
}

.symbol-layer {
  pointer-events: none;
}


@media print {

  /* 1. Reset everything to zero margins */
  :global(html, body) {
    margin: 0 !important;
    padding: 0 !important;
    visibility: hidden !important;
    width: 100% !important;
    height: 100% !important;
  }

  /* 2. Isolate and expand wrapper */
  .odontogram-wrapper {
    visibility: visible !important;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    background: white !important;
    z-index: 9999999 !important;
    border: none !important;
    overflow: visible;
  }

  /* 3. Portrait: Scale to fill width */
  .primary-print-portrait .odontogram-table {
    visibility: visible !important;
    /* Scale significantly higher to fill the paper width */
    transform: scale(2.5) !important;
    transform-origin: center;
  }

  /* 4. Landscape: Fill the maximum area */
  .primary-print-landscape .odontogram-table {
    visibility: visible !important;
    /* Rotated 90deg and scaled to fill the long side of the paper */
    transform: rotate(90deg) scale(3) !important;
    transform-origin: center;
  }

  @media (min-width: 1550px) {
    .primary-print-landscape {}
  }

  @media (max-width: 30cm) {
    .primary-print-portrait {
      transform: scale(.8) !important;
    }
  }

  /* 5. Force the browser to ignore its own default margins */
  @page {
    margin: 0;
    size: auto;
  }
}
</style>

<style>
.odontogram-shell {
  display: flex !important;
  flex-wrap: wrap !important;
  flex-direction: column !important;
  gap: .5em !important;
  /* display: none !important; */
}
</style>
