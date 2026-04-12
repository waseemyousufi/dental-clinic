<script setup lang="ts">
import { computed } from 'vue'

export interface ToothState {
  [partId: string]: string | null
}

export interface OdontogramState {
  [toothNumber: number]: ToothState
}

const props = defineProps<{
  modelValue: OdontogramState
  activeFinding: string
  readonly?: boolean
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: OdontogramState): void
}>()

const state = computed({
  get: () => props.modelValue || {},
  set: (val) => emit('update:modelValue', val)
})

const togglePart = (toothNumber: number, partId: string) => {
  if (props.readonly) return

  const newState = { ...state.value }
  if (!newState[toothNumber]) newState[toothNumber] = {}

  // Toggle Logic: If it has the color, remove it. Otherwise, apply active color.
  const current = newState[toothNumber][partId]
  newState[toothNumber][partId] = (current === props.activeFinding) ? null : props.activeFinding

  state.value = newState
}

const getPartStyle = (toothNumber: number, partId: string) => {
  const color = state.value[toothNumber]?.[partId]
  return {
    fill: color || '#ffffff',
    transition: 'fill 0.2s ease'
  }
}

// Primary Tooth Rows (FDI Notation)
// Quadrant 5 (upper right), 6 (upper left), 7 (lower left), 8 (lower right)
const upperRow1 = [55, 54, 53, 52, 51] // Upper right primary
const upperRow2 = [61, 62, 63, 64, 65] // Upper left primary
const lowerRow1 = [85, 84, 83, 82, 81] // Lower right primary
const lowerRow2 = [71, 72, 73, 74, 75] // Lower left primary

// Combined rows for template iteration
const upperRow = [...upperRow1, ...upperRow2]
const lowerRow = [...lowerRow1, ...lowerRow2]

const getToothType = (num: number) => {
  const d = num % 10
  if (d === 4 || d === 5) return 'molar' // Primary molars
  return 'incisor' // Primary incisors and canines (using incisor config)
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
</script>

<template>
  <div class="odontogram-wrapper">
    <table class="odontogram-table">
      <tbody>
        <tr class="num-row">
          <td v-for="num in upperRow" :key="num">{{ num }}</td>
        </tr>
        <tr>
          <td v-for="num in upperRow" :key="num">
            <svg :width="toothConfigs[getToothType(num)].width" :height="toothConfigs[getToothType(num)].height">
              <g transform="translate(0, 50) scale(1, -1)">
                <polygon v-for="p in toothConfigs[getToothType(num)].parts" :key="p.id" :points="p.points"
                  class="tooth-part" :style="getPartStyle(num, p.id)" @click="togglePart(num, p.id)" />
              </g>
            </svg>
          </td>
        </tr>
        <tr>
          <td v-for="num in lowerRow" :key="num">
            <svg :width="toothConfigs[getToothType(num)].width" :height="toothConfigs[getToothType(num)].height">
              <polygon v-for="p in toothConfigs[getToothType(num)].parts" :key="p.id" :points="p.points"
                class="tooth-part" :style="getPartStyle(num, p.id)" @click="togglePart(num, p.id)" />
            </svg>
          </td>
        </tr>
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

/* Adjust the border for primary teeth - there are 5 teeth per quadrant, so after 5th and before 6th */
.odontogram-table td:nth-child(5) {
  border-right: 3px solid #bfbfbf;
}

/* Remove the old nth-child(8) rule if it exists and is no longer needed */
.odontogram-table td:nth-child(8) {
  border-right: 1px solid #f0f0f0; /* Resetting or ensuring no thick border */
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
</style>
