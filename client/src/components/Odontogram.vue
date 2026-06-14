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

const props = defineProps<{ modelValue: OdontogramState; slug: string; readonly?: boolean; patient?: any }>()
const emit = defineEmits<{ (e: 'update:modelValue', value: OdontogramState): void; (e: 'tooth-click', tooth: number, part: string): void }>()

const state = computed({
  get: () => props.modelValue || {},
  set: (val) => emit('update:modelValue', val)
})

const wrapperRef = ref<HTMLElement | null>(null)



// Printing Logic
const triggerPrint = () => {
  if (!wrapperRef.value) return;

  const printWindow = window.open('', '_blank', 'width=1200,height=800');
  if (!printWindow) return;

  const chartHtml = wrapperRef.value.innerHTML;

  // Collect styles from the main app
  let styles = '';
  document.querySelectorAll('style, link[rel="stylesheet"]').forEach(style => {
    styles += style.outerHTML;
  });

  const patientInfoHtml = props.patient ? `
    <div class="patient-info-header">
      <h1>Patient: ${props.patient.fName || props.patient.f_name} ${props.patient.lName || props.patient.l_name}</h1>
      <p>Gender: ${props.patient.gender || '—'} | Blood Type: ${props.patient.bloodType || '—'} | Phone: ${props.patient.phone || '—'}</p>
      <p>Date: ${new Date().toLocaleDateString()}</p>
    </div>
  ` : '';

  printWindow.document.write(`
    <html>
      <head>
        <title>Print Odontogram</title>
        ${styles}
        <style>
          html, body {
            margin: 0;
            padding: 0;
            background: white;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
          }

          .print-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 20px;
            min-height: 100vh;
            box-sizing: border-box;
            position: relative;
          }

          /* A4 Base Styles (Default) */
          .patient-info-header {
            text-align: center;
            margin-bottom: 120px;
            width: 100%;
            max-width: 900px;
            border-bottom: 3px solid #eee;
            padding-bottom: 30px;
            position: relative;
            z-index: 2;
          }
          .patient-info-header h1 {
            margin: 0 0 20px 0;
            font-size: 48px;
            color: #000;
            font-weight: 900;
            text-align: center;
          }
          .patient-details {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 30px;
            font-size: 20px;
            color: #444;
            line-height: 2;
          }
          .detail-item {
            display: flex;
            align-items: center;
            gap: 12px;
          }
          .detail-icon {
            width: 28px;
            height: 28px;
            filter: grayscale(1);
          }

          .print-odontogram-content {
            visibility: visible !important;
            display: block !important;
            position: relative !important;
            transform: none !important;
            top: auto !important;
            left: auto !important;
            margin-top: 60px;
          }

          .odontogram-table {
            transform: scale(1.4);
            transform-origin: top center;
          }

          /* A5 Responsive Scaling */
          @media (max-width: 600px), print {
            .patient-info-header {
              margin-bottom: 50px;
              padding-bottom: 20px;
              // display:none;
            }
            .patient-info-header h1 {
              font-size: 32px;
              margin-bottom: 15px;
            }
            .patient-details {
              font-size: 16px;
              gap: 20px;
            }
            .detail-icon {
              width: 20px;
              height: 20px;
            }

            .odontogram-table {
              transform: scale(1) !important;
            }

            tbody {
              transform: scale(.84);
              }

            .odontogram-table td {
              padding: 2px;
              text-align: center;
              border-right: 0px solid #f0f0f0 !important;
            }

          .odontogram-table td:nth-child(8) {
            padding-right: 2px !important;
            border-right: 1px solid #bfbfbf !important;
          }

          }

          @media print {
            body {
              visibility: visible !important;
            }
            .print-container {
              padding: 10mm 0;
            }
          }

          @page {
            margin: 10mm;
            size: auto;
          }
        </style>
      </head>
      <body>
        <div class="print-container">
          ${props.patient ? `
            <div class="patient-info-header">
              <h1>${props.patient.fName || props.patient.f_name} ${props.patient.lName || props.patient.l_name}</h1>
              <div class="patient-details">
                <div class="detail-item">
                  <img src="https://api.iconify.design/mdi:gender-male-female.svg" class="detail-icon" />
                  <span>Gender: ${props.patient.gender || '—'}</span>
                </div>
                <div class="detail-item">
                  <img src="https://api.iconify.design/healthicons:blood-ab-p.svg" class="detail-icon" />
                  <span>Blood Type: ${props.patient.bloodType || '—'}</span>
                </div>
                <div class="detail-item">
                  <img src="https://api.iconify.design/tabler:phone.svg" class="detail-icon" />
                  <span>Phone: ${props.patient.phone || '—'}</span>
                </div>
                <div class="detail-item">
                  <img src="https://api.iconify.design/tabler:calendar.svg" class="detail-icon" />
                  <span>Date: ${new Date().toLocaleDateString()}</span>
                </div>
              </div>
            </div>
          ` : ''}
          <div class="print-odontogram-content">
            ${chartHtml}
          </div>
        </div>
        <script>
          window.onload = () => {
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
  <div class="odontogram-wrapper" ref="wrapperRef">
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

@media(min-width: 600px) {
  .odontogram-wrapper:hover {
    transform: scale(1.5) !important;
    transition: transform 1s ease !important;
  }
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
    /* transform: scale(.4) !important; */
  }

  /* 3. Scale to fill width */
  .odontogram-table {
    visibility: visible !important;
    transform: scale(1.5) !important;
    transform-origin: center;
  }

  /* 4. Force the browser to ignore its own default margins */
  @page {
    margin: 0;
    size: auto;
  }
}
</style>
