<script setup lang="ts">
import { computed } from 'vue'
import DOMPurify from 'dompurify'

defineOptions({
  name: 'OdontogramChart'
})



const safeSvg = (svg: string) => {
  return DOMPurify.sanitize(svg)
}

export type ToothState = {
  [partId: string]: string | { color: string; id: string }

  symbols: [
    {
      id: string
      svg: string
      color: string
      position?: 'center' | 'top' | 'root'
    }
  ]
}

export interface OdontogramState {
  [toothNumber: number]: ToothState
}

const props = defineProps<{
  modelValue: OdontogramState
  activeFinding: string
  readonly?: boolean
}>()

// Add this to both Odontogram.vue and PrimaryOdontogram.vue inside <script setup>
const emit = defineEmits<{
  (e: 'update:modelValue', value: OdontogramState): void
  (e: 'tooth-click', tooth: number, part: string): void // Add this!
}>()

const togglePart = (toothNumber: number, partId: string) => {
  if (props.readonly) return

  // 1. Emit the custom event for the API call
  emit('tooth-click', toothNumber, partId)

  // 2. Keep local color toggle for instant feedback
  const newState = { ...state.value }
  if (!newState[toothNumber]) newState[toothNumber] = {}
  const current = newState[toothNumber][partId]
  newState[toothNumber][partId] = (current === props.activeFinding) ? null : props.activeFinding
  state.value = newState
}

const state = computed({
  get: () => props.modelValue || {},
  set: (val) => emit('update:modelValue', val)
})

const getPartStyle = (toothNumber: number, partId: string, isLower: boolean = false) => {
  const data = state.value[toothNumber]?.[partId];

  // Extract color whether data is an object or a legacy string
  const color = typeof data === 'object' ? data?.color : data;
  if (isLower && partId === 'root-2' && getToothType(toothNumber) === 'molar')
    return {
      fill: color || '#ffffff',
      transition: 'fill 0.2s ease',
      // transform: 'translate(0, 2px)',
      opacity: 0.3 // Shift root-2 down for lower teeth
    };

  return {
    fill: color || '#ffffff',
    transition: 'fill 0.2s ease'
  };
}
// Tooth Rows (FDI Notation)
const upperRow = [
  18, 17, 16, 15, 14, 13, 12, 11, // Quadrant 1 (Right to Mid)
  21, 22, 23, 24, 25, 26, 27, 28  // Quadrant 2 (Mid to Left)
];

const lowerRow = [
  48, 47, 46, 45, 44, 43, 42, 41, // Quadrant 4 (Right to Mid)
  31, 32, 33, 34, 35, 36, 37, 38  // Quadrant 3 (Mid to Left)
];


const getToothType = (num: number) => {
  const d = num % 10
  if (d >= 6) return 'molar'
  if (d >= 4) return 'premolar'
  return 'incisor'
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
      { id: 'root-3', points: '20,30 25,50 30,30' },
    ]
  },
  premolar: {
    width: 25, height: 50,
    parts: [
      { id: 'top', points: '0,0 25,0 20,10 5,10' },
      { id: 'left', points: '0,0 5,10 5,20 0,30' },
      { id: 'bottom', points: '0,30 5,20 20,20 25,30' },
      { id: 'right', points: '25,0 20,10 20,20 25,30' },
      { id: 'center', points: '5,10 20,10 20,20 5,20' },
      { id: 'root-1', points: '0,30 7,50 13,30' },
      { id: 'root-2', points: '13,30 18,50 25,30' },
    ]
  },
  incisor: {
    width: 20, height: 50,
    parts: [
      { id: 'top', points: '0,0 20,0 15,15 5,15' },
      { id: 'left', points: '0,0 5,15 0,30' },
      { id: 'bottom', points: '0,30 5,15 15,15 20,30' },
      { id: 'right', points: '20,0 15,15 20,30' },
      { id: 'root-1', points: '0,30 10,50 20,30' },
    ]
  }
}

const getSymbolTransform = (
  position: 'center' | 'top' | 'root' = 'center',
  isLower: boolean
) => {
  // Base scale (because your SVG paths are 0–100)
  const scale = 0.25

  let x = 50
  let y = 25

  if (position === 'top') y = 10
  if (position === 'root') y = 40

  // Flip compensation for lower teeth
  if (isLower) {
    y = 50 - y
  }

  return `translate(${x}, ${y}) scale(${scale}) translate(-50,-50)`
}

const debugSymbols = (num: number) => {
  console.log('TOOTH SYMBOLS', num, state.value[num])
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
            <svg
              :width="toothConfigs[getToothType(num)].width"
              :height="toothConfigs[getToothType(num)].height"
            >
              <!-- SINGLE COORDINATE SYSTEM -->
              <g>
                <!-- TOOTH -->
                <polygon
                  v-for="p in toothConfigs[getToothType(num)].parts"
                  :key="p.id"
                  :points="p.points"
                  class="tooth-part"
                  :style="getPartStyle(num, p.id)"
                  @click="togglePart(num, p.id)"
                />

                <!-- SYMBOLS -->
                <g class="symbol-layer">
                  <g
                    v-for="symbol in state[num]?.symbols || []"
                    :key="symbol.id"
                    :transform="getSymbolTransform(symbol.position, false)"
                  >
                    <path
                      :d="symbol.svg"
                      :stroke="symbol.color || '#000'"
                      fill="none"
                      stroke-width="2"
                      vector-effect="non-scaling-stroke"
                    />
                  </g>
                </g>
              </g>
            </svg>
          </td>
        </tr>

        <!-- LOWER TEETH -->
        <tr>
          <td v-for="num in lowerRow" :key="num">
            <svg
              :width="toothConfigs[getToothType(num)].width"
              :height="toothConfigs[getToothType(num)].height"
            >
              <!-- FLIP EVERYTHING TOGETHER -->
              <g :transform="`translate(0, ${toothConfigs[getToothType(num)].height}) scale(1,-1)`">

                <!-- TOOTH -->
                <polygon
                  v-for="p in toothConfigs[getToothType(num)].parts"
                  :key="p.id"
                  :points="p.points"
                  class="tooth-part"
                  :style="getPartStyle(num, p.id, true)"
                  @click="togglePart(num, p.id)"
                />

                <!-- SYMBOLS (same transform space now!) -->
                <g class="symbol-layer">
                  <g
                    v-for="symbol in state[num]?.symbols || []"
                    :key="symbol.id"
                    :transform="getSymbolTransform(symbol.position, true)"
                  >
                    <path
                      :d="symbol.svg"
                      :stroke="symbol.color || '#000'"
                      fill="none"
                      stroke-width="2"
                      vector-effect="non-scaling-stroke"
                    />
                  </g>
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

.symbol-wrapper {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
}

/* IMPORTANT: make SVG inside scale properly */
.symbol-wrapper svg {
  width: 60%;
  height: 60%;
}
</style>
