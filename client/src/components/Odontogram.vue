<script setup lang="ts">
import { computed, ref } from 'vue'
import DOMPurify from 'dompurify'

defineOptions({ name: 'OdontogramChart' })

const safeSvg = (svg: string) => DOMPurify.sanitize(svg)

// Types & Props
export type ToothState = {
  [partId: string]: string | { color: string; id: string }
  symbols: { id: string; svg: string; slug?: string; color: string; position?: 'crown' | 'root' | 'auto' }[]
}

export interface OdontogramState { [toothNumber: number]: ToothState }

const props = defineProps<{ modelValue: OdontogramState; slug: string; readonly?: boolean }>()
const emit = defineEmits<{ (e: 'update:modelValue', value: OdontogramState): void; (e: 'tooth-click', tooth: number, part: string): void }>()

const state = computed({
  get: () => props.modelValue || {},
  set: (val) => emit('update:modelValue', val)
})

const wrapperRef = ref<HTMLElement | null>(null)



// Printing Logic
const printMode = ref<'portrait' | 'landscape' | null>(null)
const triggerPrint = (mode: 'portrait' | 'landscape') => {
  if (!wrapperRef.value) return;

  const printWindow = window.open('', '_blank', 'width=1200,height=800');
  if (!printWindow) return;

  const chartHtml = wrapperRef.value.innerHTML;

  // Collect styles from the main app
  let styles = '';
  document.querySelectorAll('style, link[rel="stylesheet"]').forEach(style => {
    styles += style.outerHTML;
  });

  let rotation = 'rotate(90deg)' as string | null
  if (mode === 'portrait') {
    rotation = null
  }

  printWindow.document.write(`
    <html>
      <head>
        <title>Print Odontogram</title>
        ${styles}
        <style>
          /* 1. Ensure the container takes up the full page height */
          html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            width: 100%;
          }

          /* 2. Create a flex container to center the content */
          .print-container {
            display: flex;
            ${rotation ? 'align-items: center;' : 'margin-top: 100px;'}
            justify-content: center;
            height: 100vh;
            width: 100vw;
            background: white;
          }

          /* 3. Reset component-specific styles for the print doc */
          .odontogram-wrapper {
            visibility: visible !important;
            display: block !important;
          }

          /* 4. Handle scaling based on orientation */
          .odontogram-table {
            transform: scale(${mode === 'portrait' ? '2.2' : '2.8'});
            transform-origin: center;
          }

            @media (min-width: 1550px) {
              .odontogram-wrapper {
                transform: ${rotation} ${rotation ? 'scale(1.5)' : 'scale(1)'}  !important;
              }
            }

            @media (max-width: 30cm) {
              .odontogram-wrapper {
                transform: ${rotation} ${rotation ? 'scale(1)' : 'scale(.8)'}  !important;
                display: none;
              }
            }


          @page {
            margin: 0;
          }
        </style>
      </head>
      <body>
        <div class="print-container">
          <div class="odontogram-wrapper">
            ${chartHtml}
          </div>
        </div>
        <script>
          window.onload = () => {
            // Small timeout ensures fonts and SVGs render before print
            setTimeout(() => {
              window.print();
              window.close();
            }, 250);
          };
        <\/script>
      </body>
    </html>
  `);

  printWindow.document.close();
};

// Anatomical Mapping
const slugPositionMap: Record<string, 'crown' | 'root'> = {
  abscess: 'root', root_canal: 'root', unerupted: 'root',
  impacted_tooth: 'crown', implant: 'crown', extraction: 'crown',
  missing: 'crown', caries: 'crown', recurrent_caries: 'crown',
  sealant: 'crown', amalgam_filling: 'crown', full_crown: 'crown',
  fracture: 'crown', dental_bridge: 'crown', orthodontic_brackets: 'crown',
  gingival_recession: 'crown', diastema: 'crown', loose_tooth: 'crown'
}

const resolveSymbolPosition = (slug: string, explicit?: 'crown' | 'root' | 'auto') =>
  (explicit && explicit !== 'auto') ? explicit : (slugPositionMap[slug] || 'crown')

const upperRow = [18, 17, 16, 15, 14, 13, 12, 11, 21, 22, 23, 24, 25, 26, 27, 28]
const lowerRow = [48, 47, 46, 45, 44, 43, 42, 41, 31, 32, 33, 34, 35, 36, 37, 38]

const getToothType = (num: number) => {
  const d = num % 10
  if (d >= 6) return 'molar'
  if (d >= 4) return 'premolar'
  return 'incisor'
}

const toothConfigs: any = {
  molar: { width: 30, height: 50, parts: [{ id: 'top', points: '0,0 30,0 20,10 10,10' }, { id: 'left', points: '0,0 10,10 10,20 0,30' }, { id: 'bottom', points: '0,30 10,20 20,20 30,30' }, { id: 'right', points: '30,0 20,10 20,20 30,30' }, { id: 'center', points: '10,10 20,10 20,20 10,20' }, { id: 'root-1', points: '0,30 5,50 10,30' }, { id: 'root-2', points: '10,30 15,50 20,30' }, { id: 'root-3', points: '20,30 25,50 30,30' }] },
  premolar: { width: 25, height: 50, parts: [{ id: 'top', points: '0,0 25,0 20,10 5,10' }, { id: 'left', points: '0,0 5,10 5,20 0,30' }, { id: 'bottom', points: '0,30 5,20 20,20 25,30' }, { id: 'right', points: '25,0 20,10 20,20 25,30' }, { id: 'center', points: '5,10 20,10 20,20 5,20' }, { id: 'root-1', points: '0,30 7,50 13,30' }, { id: 'root-2', points: '13,30 18,50 25,30' }] },
  incisor: { width: 20, height: 50, parts: [{ id: 'top', points: '0,0 20,0 15,15 5,15' }, { id: 'left', points: '0,0 5,15 0,30' }, { id: 'bottom', points: '0,30 5,15 15,15 20,30' }, { id: 'right', points: '20,0 15,15 20,30' }, { id: 'root-1', points: '0,30 10,50 20,30' }] }
}

const getSymbolTransform = (num: number, symbol: any, isUpper: boolean) => {
  const type = getToothType(num)
  const { width: w, height: h } = toothConfigs[type]
  let scale = w / 100
  const pos = resolveSymbolPosition(symbol.slug, symbol.position)
  const x = w / 2
  let y = isUpper ? (pos === 'root' ? h * 0.85 : h * 0.25) : (pos === 'crown' ? h * 0.25 : h * 0.85)
  if (isUpper) scale *= -1
  return `translate(${x}, ${y}) ${isUpper ? 'scale(1, -1)' : ''} scale(${scale * 0.9}) translate(-50,-50)`
}

const getPartStyle = (num: number, partId: string) => {
  const data = state.value[num]?.[partId]
  return { fill: (typeof data === 'object' ? data?.color : data) || '#ffffff', transition: 'fill 0.2s ease' }
}



</script>

<template>
  <div class="odontogram-wrapper" ref="wrapperRef"
    :class="{ 'adult-print-portrait': printMode === 'portrait', 'adult-print-landscape': printMode === 'landscape' }">
    <table class="odontogram-table">
      <tbody>
        <tr class="num-row">
          <td v-for="num in upperRow" :key="num">{{ num }}</td>
        </tr>
        <tr>
          <td v-for="num in upperRow" :key="num">
            <svg :width="toothConfigs[getToothType(num)].width" :height="toothConfigs[getToothType(num)].height">
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
            <svg :width="toothConfigs[getToothType(num)].width" :height="toothConfigs[getToothType(num)].height">
              <polygon v-for="p in toothConfigs[getToothType(num)].parts" :key="p.id" :points="p.points"
                class="tooth-part" :style="getPartStyle(num, p.id)" @click="emit('tooth-click', num, p.id)" />
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
    <button type="button" @click="triggerPrint('portrait')">
      Print Portrait
    </button>
    <button type="button" @click="triggerPrint('landscape')">
      Print Landscape
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

.odontogram-table td:nth-child(8) {
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
  .adult-print-portrait .odontogram-table {
    visibility: visible !important;
    /* Scale significantly higher to fill the paper width */
    transform: scale(2.5) !important;
    transform-origin: center;
  }

  /* 4. Landscape: Fill the maximum area */
  .adult-print-landscape .odontogram-table {
    visibility: visible !important;
    /* Rotated 90deg and scaled to fill the long side of the paper */
    transform: rotate(90deg) scale(3) !important;
    transform-origin: center;
  }

  /* 5. Force the browser to ignore its own default margins */
  @page {
    margin: 0;
    size: auto;
  }
}
</style>
