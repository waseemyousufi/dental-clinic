<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

interface CustomDateRange {
  start: string | null
  end: string | null
}

type Period = '1D' | '7D' | '30D' | '90D' | 'CUSTOM'

const props = defineProps<{
  selectedPeriod: Period
  customDateRange: CustomDateRange
  scopeLabel?: string
  loading?: boolean

  /**
   * Optional selector or element id
   * Example:
   * printTarget="#report-root"
   * printTarget="report-root"
   */
  printTarget?: string
}>()

const { t } = useI18n()

const emit = defineEmits<{
  (e: 'period-change', period: Period): void
  (e: 'custom-date-range-change', range: { start: string; end: string }): void
}>()

const periodOptions = computed(() => [
  { key: '1D', label: t('reportsView.periodLabels.today') },
  { key: '7D', label: t('reportsView.periodLabels.last7Days') },
  { key: '30D', label: t('reportsView.periodLabels.last30Days') },
  { key: '90D', label: t('reportsView.periodLabels.last90Days') },
  { key: 'CUSTOM', label: t('reportsView.periodLabels.customRange') },
]) as const

const hasCustomRange = computed(() => {
  return !!props.customDateRange.start && !!props.customDateRange.end
})

const onApplyRange = () => {
  if (!props.customDateRange.start || !props.customDateRange.end) return

  emit('custom-date-range-change', {
    start: props.customDateRange.start,
    end: props.customDateRange.end,
  })
}

const printReport = () => {
  const target =
    document.querySelector(props.printTarget || '') ||
    document.getElementById(props.printTarget || '') ||
    document.body

  if (!target) {
    window.print()
    return
  }

  const printWindow = window.open('', '_blank', 'width=1200,height=800')

  if (!printWindow) return

  const styles = Array.from(document.querySelectorAll('style, link[rel="stylesheet"]'))
    .map((node) => node.outerHTML)
    .join('')

  printWindow.document.write(`
    <html>
      <head>
        <title>Report</title>
        ${styles}
        <style>
          body {
            padding: 24px;
            background: white;
          }

          * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
          }
        </style>
      </head>
      <body>
        ${target.outerHTML}
      </body>
    </html>
  `)

  printWindow.document.close()

  printWindow.focus()

  setTimeout(() => {
    printWindow.print()
    printWindow.close()
  }, 350)
}
</script>

<template>
  <header class="report-control-header">
    <div class="top-row">
      <div class="left-content">
        <div class="control-group grow">
          <label class="control-label">{{ t('reportsView.controlHeader.dateRange') }}</label>

          <div class="period-pills">
            <button
              v-for="item in periodOptions"
              :key="item.key"
              :class="['pill', selectedPeriod === item.key ? 'active' : '']"
              :disabled="loading"
              @click="emit('period-change', item.key)"
            >
              {{ item.label }}
            </button>
          </div>
        </div>

        <Transition name="fade-slide">
          <div
            v-if="selectedPeriod === 'CUSTOM'"
            class="control-group custom-range"
          >
            <label class="control-label">{{ t('reportsView.controlHeader.customWindow') }}</label>

            <div class="date-row">
              <input
                class="date-input"
                type="date"
                :value="customDateRange.start ?? ''"
                @input="
                  emit('custom-date-range-change', {
                    start: ($event.target as HTMLInputElement).value,
                    end:
                      customDateRange.end ||
                      ($event.target as HTMLInputElement).value,
                  })
                "
              />

              <span class="date-divider">→</span>

              <input
                class="date-input"
                type="date"
                :value="customDateRange.end ?? ''"
                @input="
                  emit('custom-date-range-change', {
                    start:
                      customDateRange.start ||
                      ($event.target as HTMLInputElement).value,
                    end: ($event.target as HTMLInputElement).value,
                  })
                "
              />

              <button
                class="apply-btn"
                :disabled="!hasCustomRange || loading"
                @click="onApplyRange"
              >
                {{ t('reportsView.controlHeader.applyButton') }}
              </button>
            </div>
          </div>
        </Transition>
      </div>

      <div class="right-content">
        <div class="scope-chip">
          <span class="scope-dot"></span>

          <div class="scope-content">
            <span class="scope-label">{{ t('reportsView.controlHeader.scopeLabel') }}</span>
            <span class="scope-value">
              {{ scopeLabel || t('reportsView.scopeLabel') }}
            </span>
          </div>
        </div>
<!-- 
        <button
          class="print-btn"
          :disabled="loading"
          @click="printReport"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="btn-icon"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.8"
              d="M6 9V4h12v5M6 18h12v2H6v-2zm12-6h2v5h-2m-12 0H4v-5h2"
            />
          </svg>

          <span>Print PDF</span>
        </button> -->
      </div>
    </div>
  </header>
</template>

<style scoped>
:root {
  color-scheme: light;
}

.report-control-header {
  position: sticky;
  top: 0;
  z-index: 40;

  display: flex;
  flex-direction: column;
  gap: 1rem;

  padding: 1rem 1.1rem;

  border: 1px solid rgba(226, 232, 240, 0.8);
  border-radius: 1.25rem;

  background: rgba(255, 255, 255, 0.88);
  backdrop-filter: blur(16px);

  box-shadow:
    0 4px 20px rgba(15, 23, 42, 0.04),
    0 1px 2px rgba(15, 23, 42, 0.06);
}

.top-row {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 1rem;
}

.left-content {
  display: flex;
  flex: 1;
  flex-wrap: wrap;
  gap: 1rem;
  align-items: flex-end;
}

.right-content {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.control-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.grow {
  flex: 1;
}

.control-label {
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #64748b;
}

.period-pills {
  display: flex;
  flex-wrap: wrap;
  gap: 0.55rem;
}

.pill {
  border: 1px solid transparent;
  border-radius: 999px;

  background: #f1f5f9;
  color: #334155;

  min-height: 2.6rem;
  padding: 0.65rem 1rem;

  font-size: 0.875rem;
  font-weight: 700;

  transition:
    all 0.2s ease,
    transform 0.15s ease;

  cursor: pointer;
}

.pill:hover:not(:disabled) {
  background: #e2e8f0;
  transform: translateY(-1px);
}

.pill.active {
  background: linear-gradient(135deg, #0f766e, #115e59);
  color: white;

  box-shadow: 0 10px 20px rgba(15, 118, 110, 0.2);
}

.pill:disabled,
.apply-btn:disabled,
.print-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.custom-range {
  min-width: 320px;
}

.date-row {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.date-input {
  min-height: 2.7rem;

  border: 1px solid #dbe4ee;
  border-radius: 0.9rem;

  padding: 0.6rem 0.85rem;

  background: #fff;
  color: #0f172a;

  font-size: 0.9rem;
  font-weight: 500;

  transition: border-color 0.2s ease;
}

.date-input:focus {
  outline: none;
  border-color: #0f766e;
  box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.08);
}

.date-divider {
  color: #94a3b8;
  font-weight: 700;
}

.apply-btn,
.print-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.55rem;

  border: none;
  border-radius: 0.95rem;

  min-height: 2.75rem;

  padding: 0.7rem 1rem;

  font-size: 0.9rem;
  font-weight: 700;

  transition:
    transform 0.15s ease,
    box-shadow 0.2s ease,
    opacity 0.2s ease;

  cursor: pointer;
}

.apply-btn {
  background: #111827;
  color: white;
}

.apply-btn:hover:not(:disabled),
.print-btn:hover:not(:disabled) {
  transform: translateY(-1px);
}

.print-btn {
  background: linear-gradient(135deg, #0f172a, #1e293b);
  color: white;

  box-shadow: 0 10px 20px rgba(15, 23, 42, 0.12);
}

.btn-icon {
  width: 1rem;
  height: 1rem;
}

.scope-chip {
  display: flex;
  align-items: center;
  gap: 0.75rem;

  min-height: 2.75rem;

  padding: 0.7rem 0.95rem;

  border: 1px solid #e2e8f0;
  border-radius: 1rem;

  background: #ffffff;
}

.scope-dot {
  width: 0.7rem;
  height: 0.7rem;

  border-radius: 999px;
  background: #14b8a6;

  box-shadow: 0 0 0 4px rgba(20, 184, 166, 0.12);
}

.scope-content {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
}

.scope-label {
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #94a3b8;
}

.scope-value {
  font-size: 0.88rem;
  font-weight: 700;
  color: #0f172a;
}

.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.22s ease;
}

.fade-slide-enter-from,
.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

/* Tablet */
@media (max-width: 1024px) {
  .top-row {
    flex-direction: column;
    align-items: stretch;
  }

  .right-content {
    justify-content: space-between;
    flex-wrap: wrap;
  }

  .print-btn {
    flex: 1;
  }
}

/* Mobile */
@media (max-width: 640px) {
  .report-control-header {
    padding: 0.9rem;
    border-radius: 1rem;
  }

  .left-content,
  .right-content,
  .date-row,
  .period-pills {
    width: 100%;
  }

  .period-pills {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
  }

  .pill {
    width: 100%;
  }

  .custom-range {
    min-width: 100%;
  }

  .date-row {
    flex-direction: column;
    align-items: stretch;
  }

  .date-divider {
    display: none;
  }

  .date-input,
  .apply-btn,
  .print-btn,
  .scope-chip {
    width: 100%;
  }

  .right-content {
    flex-direction: column;
    align-items: stretch;
  }
}

@media print {
  .report-control-header {
    display: none !important;
  }
}
</style>
