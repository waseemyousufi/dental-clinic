<script setup lang="ts">
import { computed } from 'vue'
import DOMPurify from 'dompurify'

defineOptions({
  name: 'OdontogramChart'
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
}>()

console.log("Fresh Odontogram Props: ", props)

const emit = defineEmits<{
  (e: 'update:modelValue', value: OdontogramState): void
  (e: 'tooth-click', tooth: number, part: string): void
}>()

const state = computed({
  get: () => props.modelValue || {},
  set: (val) => emit('update:modelValue', val)
})

/**
 * 🧠 anatomical mapping (same for both jaws)
 */
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

const upperRow = [
  18, 17, 16, 15, 14, 13, 12, 11,
  21, 22, 23, 24, 25, 26, 27, 28
]

const lowerRow = [
  48, 47, 46, 45, 44, 43, 42, 41,
  31, 32, 33, 34, 35, 36, 37, 38
]

const getToothType = (num: number) => {
  const d = num % 10
  if (d >= 6) return 'molar'
  if (d >= 4) return 'premolar'
  return 'incisor'
}

const toothConfigs: any = {
  molar: {
    width: 30,
    height: 50,
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
  premolar: {
    width: 25,
    height: 50,
    parts: [
      { id: 'top', points: '0,0 25,0 20,10 5,10' },
      { id: 'left', points: '0,0 5,10 5,20 0,30' },
      { id: 'bottom', points: '0,30 5,20 20,20 25,30' },
      { id: 'right', points: '25,0 20,10 20,20 25,30' },
      { id: 'center', points: '5,10 20,10 20,20 5,20' },
      { id: 'root-1', points: '0,30 7,50 13,30' },
      { id: 'root-2', points: '13,30 18,50 25,30' }
    ]
  },
  incisor: {
    width: 20,
    height: 50,
    parts: [
      { id: 'top', points: '0,0 20,0 15,15 5,15' },
      { id: 'left', points: '0,0 5,15 0,30' },
      { id: 'bottom', points: '0,30 5,15 15,15 20,30' },
      { id: 'right', points: '20,0 15,15 20,30' },
      { id: 'root-1', points: '0,30 10,50 20,30' }
    ]
  }
}

/**
 * 🎯 FIXED SYMBOL POSITIONING
 * KEY CHANGE: no jaw-awareness here anymore
 */
const getSymbolTransform = (
  toothNumber: number,
  symbol: any,
  isUpper: boolean
) => {
  const type = getToothType(toothNumber)
  const config = toothConfigs[type]
  const w = config.width
  const h = config.height
  let scale = w / 100

  const pos = resolveSymbolPosition(symbol.slug, symbol.position)

  const x = w / 2
  let y = h / 2

  if (isUpper) {
    /**
     * UPPER ROW (Flipped Group):
     * 0 is visual BOTTOM, h is visual TOP.
     */
    // Root needs to be at the TOP (large Y in flipped space)
    // Crown needs to be at the BOTTOM (small Y in flipped space)
    y = pos === 'root' ? h * 0.85 : h * 0.25
    scale *= -1 // Flip vertically for upper row
  } else {
    y = pos === 'crown' ? h * 0.25 : h * 0.85
  }

  // This "scale(1, -1)" flips the icon back so it isn't upside down in the upper row
  const iconFlip = isUpper ? 'scale(1, -1)' : ''

  return `
    translate(${x}, ${y})
    ${iconFlip}
    scale(${scale * 0.9})
    translate(-50,-50)
  `
}
const getPartStyle = (toothNumber: number, partId: string, isLower = false) => {
  const data = state.value[toothNumber]?.[partId]
  const color = typeof data === 'object' ? data?.color : data

  if (isLower && partId === 'root-2' && getToothType(toothNumber) === 'molar') {
    return {
      fill: color || '#ffffff',
      transition: 'fill 0.2s ease',
      opacity: 0.3
    }
  }

  return {
    fill: color || '#ffffff',
    transition: 'fill 0.2s ease'
  }
}
</script>

<template>
  <div class="odontogram-wrapper">
    <table class="odontogram-table">
      <tbody>

        <!-- UPPER NUMBERS -->
        <tr class="num-row">
          <td v-for="num in upperRow" :key="num">{{ num }}</td>
        </tr>

        <!-- UPPER TEETH -->
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

        <!-- LOWER TEETH -->
        <tr>
          <td v-for="num in lowerRow" :key="num">
            <svg :width="toothConfigs[getToothType(num)].width" :height="toothConfigs[getToothType(num)].height">

              <g>
                <polygon v-for="p in toothConfigs[getToothType(num)].parts" :key="p.id" :points="p.points"
                  class="tooth-part" :style="getPartStyle(num, p.id, true)" @click="emit('tooth-click', num, p.id)" />
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

        <!-- LOWER NUMBERS -->
        <tr class="num-row">
          <td v-for="num in lowerRow" :key="num">{{ num }}</td>
        </tr>

      </tbody>
    </table>
  </div>
</template>

<style scoped>
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
</style>
