<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import Odontogram, { type OdontogramState } from '../../components/Odontogram.vue'
import PrimaryOdontogram from '../../components/PrimaryOdontogram.vue'
import patientApi from '@/api/patient'
import type PatientData from '@/api/interfaces/Patient'
import { useMessage } from 'naive-ui'

// Add isPrimaryTeeth to PatientData interface temporarily for this file if not present globally
interface DentistPatientData extends PatientData {
  isPrimaryTeeth?: boolean; // Imaginary field
}

// --- 1. DATA DEFINITIONS ---
const findingTypes = [
  { id: 'diagnostic', color: '#ff4d4f', label: 'Diagnostic Findings (Existing)', description: 'Red' },
  { id: 'material', color: '#333333', label: 'Material Findings (Existing)', description: 'Black' },
  { id: 'planned', color: '#73d13d', label: 'Procedures (Planned/In Progress)', description: 'Green' },
  { id: 'completed', color: '#40a9ff', label: 'Procedures (Completed/Treated)', description: 'Blue' },
]

// --- 2. STATE MANAGEMENT ---
const route = useRoute()
const message = useMessage()
const patientId = Number(route.params.id)

const patient = ref<DentistPatientData | null>(null)
const odontogramData = ref<OdontogramState>({})
const activeFinding = ref<string>(findingTypes[0].color)
const clinicalNotes = ref('')
const isSaving = ref(false)
const loadingPatient = ref(true)

// Conditional Odontogram Rendering
const usePrimaryOdontogram = computed(() => patient.value?.isPrimaryTeeth === true)

// --- 3. FETCH PATIENT DATA ---
const fetchPatientData = async () => {
  loadingPatient.value = true
  try {
    const { data } = await patientApi.getPatient(patientId)
    patient.value = { ...data.data, isPrimaryTeeth: (data.data.age <= 12) } as DentistPatientData; // Mocking isPrimaryTeeth based on age

    // Populate odontogram data from fetched patient data if available
    // For now, setting a mock state
    if (patient.value?.isPrimaryTeeth) {
      odontogramData.value = { 51: { center: "#ff4d4f" }, 62: { top: "#333333" } };
    } else {
      odontogramData.value = { 18: { center: "#ff4d4f" }, 17: { top: "#333333" } };
    }

  } catch (error) {
    console.error("Failed to fetch patient data:", error)
    message.error("Failed to load patient data.")
    patient.value = null // Ensure patient is null on error
  } finally {
    loadingPatient.value = false
  }
}

onMounted(fetchPatientData)

// --- 4. SAVE FUNCTION ---
const handleSave = async () => {
  isSaving.value = true

  const payload = {
    patientId: patient.value?.id,
    findings: odontogramData.value,
    notes: clinicalNotes.value,
    updatedAt: new Date().toISOString()
  }

  try {
    console.log("Saving odontogram data and notes...", payload)
    // Simulate API call to save odontogram data
    await new Promise(resolve => setTimeout(resolve, 1200)) // Replace with actual API call
    message.success("Patient record updated successfully.")
  } catch (err) {
    console.error("Save failed:", err)
    message.error("Failed to save changes.")
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <div class="view-patient-container">
    <div v-if="loadingPatient" class="loading-state">
      Loading patient data...
    </div>
    <div v-else-if="!patient" class="error-state">
      Patient not found or an error occurred.
    </div>
    <div v-else>
      <header class="patient-header">
        <div class="info">
          <h1>{{ patient.fName }} {{ patient.lName }} <span class="id">({{ patient.id }})</span></h1>
          <p>Age: {{ patient.age || 'N/A' }} | Phone: {{ patient.phone || 'N/A' }}</p>
        </div>
        <div class="actions">
          <button class="btn-secondary">Print Chart</button>
          <button @click="handleSave" :disabled="isSaving" class="btn-primary">
            {{ isSaving ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>
      </header>

      <hr class="divider" />

      <main class="content-grid">
        <section class="chart-section">
          <div class="section-header">
            <h3>Dental Odontogram ({{ usePrimaryOdontogram ? 'Primary Teeth' : 'Permanent Teeth' }})</h3>
            <div class="legend">
              <div v-for="f in findingTypes" :key="f.id" class="legend-pill"
                :class="{ active: activeFinding === f.color }" @click="activeFinding = f.color">
                <span class="dot" :style="{ backgroundColor: f.color }"></span>
                {{ f.label }}
              </div>
            </div>
          </div>

          <div class="odontogram-card">
            <PrimaryOdontogram v-if="usePrimaryOdontogram" v-model="odontogramData" :active-finding="activeFinding" />
            <Odontogram v-else v-model="odontogramData" :active-finding="activeFinding" />
          </div>
        </section>

        <section class="notes-section">
          <div class="card">
            <h3>Clinical Notes</h3>
            <textarea v-model="clinicalNotes" placeholder="Enter observations or treatment details..."></textarea>
          </div>

          <div class="card summary-card">
            <h3>Current Findings Summary</h3>
            <div v-if="Object.keys(odontogramData).length === 0" class="empty">
              No findings marked yet.
            </div>
            <ul v-else class="findings-list">
              <li v-for="(parts, tooth) in odontogramData" :key="tooth">
                <strong>Tooth {{ tooth }}:</strong>
                <span v-for="(color, part) in parts" :key="part" class="part-tag">
                  {{ part }}
                </span>
              </li>
            </ul>
          </div>
        </section>
      </main>
    </div>
  </div>
</template>

<style scoped lang="scss">
.view-patient-container {
  padding: 24px;
  max-width: 1400px;
  margin: 0 auto;
  font-family: sans-serif;
}

.loading-state,
.error-state {
  text-align: center;
  padding: 50px;
  font-size: 1.2em;
  color: #666;
}

.patient-header {
  display: flex;
  justify-content: space-between;
  align-items: center;

  h1 {
    margin: 0;
    font-size: 24px;
  }

  .id {
    color: #8c8c8c;
    font-weight: normal;
    font-size: 18px;
  }

  .actions {
    display: flex;
    gap: 12px;
  }
}

.divider {
  border: 0;
  border-top: 1px solid #f0f0f0;
  margin: 24px 0;
}

.content-grid {
  display: grid;
  grid-template-columns: 1fr 350px;
  gap: 24px;
}

.section-header {
  margin-bottom: 16px;

  h3 {
    margin-bottom: 12px;
  }
}

.legend {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.legend-pill {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 12px;
  background: #f5f5f5;
  border: 1px solid #d9d9d9;
  border-radius: 16px;
  font-size: 12px;
  cursor: pointer;
  transition: all 0.2s;

  &:hover {
    background: #e8e8e8;
  }

  &.active {
    background: #e6f7ff;
    border-color: #91d5ff;
    color: #1890ff;
    font-weight: bold;
  }

  .dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
  }
}

.odontogram-card {
  background: white;
  border: 1px solid #f0f0f0;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.card {
  background: white;
  border: 1px solid #f0f0f0;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 20px;

  h3 {
    margin-top: 0;
    font-size: 16px;
    margin-bottom: 12px;
  }

  textarea {
    width: 100%;
    height: 120px;
    border: 1px solid #d9d9d9;
    border-radius: 4px;
    padding: 8px;
    resize: none;

    &:focus {
      outline: none;
      border-color: #40a9ff;
    }
  }
}

.findings-list {
  list-style: none;
  padding: 0;
  font-size: 13px;

  li {
    margin-bottom: 8px;
    border-bottom: 1px solid #fafafa;
    padding-bottom: 4px;
  }
}

.part-tag {
  background: #f0f5ff;
  color: #2f54eb;
  padding: 2px 6px;
  border-radius: 4px;
  margin-left: 4px;
  font-size: 11px;
}

.btn-primary {
  background: #1890ff;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 4px;
  cursor: pointer;

  &:disabled {
    background: #bfbfbf;
  }
}

.btn-secondary {
  background: white;
  border: 1px solid #d9d9d9;
  padding: 8px 16px;
  border-radius: 4px;
  cursor: pointer;
}
</style>
