<template>
  <div class="settings-page">
    <n-card class="settings-shell" :bordered="false">
      <template #header>
        <div class="header-bar">
          <div>
            <h2 class="page-title">Branch Settings</h2>
            <p class="page-subtitle">Manage clinic details, templates, permissions, and procedures.</p>
          </div>
          <n-space class="header-actions" wrap>
            <n-button @click="resetForm" secondary>Reset</n-button>
            <n-button type="primary" @click="submitForm" :loading="loading">Save Changes</n-button>
          </n-space>
        </div>
      </template>

      <n-form ref="formRef" :model="formData" :rules="rules" label-placement="top" @submit.prevent>
      <n-tabs v-model:value="activeTab" type="line" animated placement="top" class="settings-tabs">
        <n-tab-pane name="branch" tab="Branch">
          <div class="section-stack">
            <div class="responsive-grid">
              <n-form-item label="Clinic Name" path="clinic_name">
                <n-input v-model:value="formData.clinic_name" placeholder="e.g., SmileCare Dental" />
              </n-form-item>
              <!-- <n-form-item label="Currency" path="currency">
                <n-select v-model:value="formData.currency" :options="currencyOptions" />
              </n-form-item> -->
              <n-form-item class="full-span" label="Address" path="address">
                <n-input v-model:value="formData.address" type="textarea" placeholder="Full clinic address" :autosize="{ minRows: 3, maxRows: 5 }" />
              </n-form-item>
              <n-form-item label="Phone" path="phone">
                <n-input v-model:value="formData.phone" placeholder="+1 234 567 8900" />
              </n-form-item>
            </div>

            <!-- <n-divider title-placement="left">Working Hours</n-divider>
            <div class="hours-grid">
              <n-card v-for="(day, key) in formData.working_hours" :key="key" size="small" class="day-card">
                <div class="day-header">
                  <span class="day-name">{{ capitalize(key) }}</span>
                  <n-switch v-model:value="day.is_off" size="small">
                    <template #checked>Off</template>
                    <template #unchecked>Open</template>
                  </n-switch>
                </div>
                <div v-if="!day.is_off" class="day-inputs">
                  <n-input-group>
                    <n-input-group-label>Start</n-input-group-label>
                    <input v-model="day.start" class="time-input" type="time" />
                  </n-input-group>
                  <n-input-group>
                    <n-input-group-label>End</n-input-group-label>
                    <input v-model="day.end" class="time-input" type="time" />
                  </n-input-group>
                </div>
              </n-card>
            </div> -->
          </div>
        </n-tab-pane>

        <n-tab-pane name="whatsapp" tab="WhatsApp">
          <div class="section-stack">
            <n-alert type="info" show-icon>
              <template #header>Dynamic Variables</template>
              Use placeholders like
              <n-tag size="small" type="info">patient_name</n-tag>
              <n-tag size="small" type="info">date</n-tag>
              <n-tag size="small" type="info">time</n-tag>
              <n-tag size="small" type="info">clinic_name</n-tag>
            </n-alert>

            <div class="responsive-grid">
              <n-form-item label="Patient Reminder">
                <n-input v-model:value="formData.wa_patient_reminder" type="textarea" :autosize="{ minRows: 3, maxRows: 6 }" />
              </n-form-item>
              <n-form-item label="Patient Cancellation">
                <n-input v-model:value="formData.wa_patient_cancel" type="textarea" :autosize="{ minRows: 3, maxRows: 6 }" />
              </n-form-item>
              <n-form-item label="Patient Completion">
                <n-input v-model:value="formData.wa_patient_complete" type="textarea" :autosize="{ minRows: 3, maxRows: 6 }" />
              </n-form-item>
              <n-form-item label="Supplier Order">
                <n-input v-model:value="formData.wa_supplier_order" type="textarea" :autosize="{ minRows: 3, maxRows: 6 }" />
              </n-form-item>
              <n-form-item class="full-span" label="Supplier Cancel">
                <n-input v-model:value="formData.wa_supplier_cancel" type="textarea" :autosize="{ minRows: 3, maxRows: 6 }" />
              </n-form-item>
            </div>
          </div>
        </n-tab-pane>

        <n-tab-pane name="procedures" tab="Procedures">
          <div class="service-section">
            <n-card size="small" title="Add Procedure">
              <div class="service-form-grid">
                <n-form-item label="Procedure Name">
                  <n-input v-model:value="newProcedure.name" placeholder="Procedure name" />
                </n-form-item>
                <n-form-item label="Category">
                  <n-input v-model:value="newProcedure.category" placeholder="Category" />
                </n-form-item>
                <n-form-item label="Base Price">
                  <n-input-number v-model:value="newProcedure.base_price" :min="0" :precision="2" placeholder="Base price" />
                </n-form-item>
                <!-- <n-form-item label="Minimum Price">
                  <n-input-number v-model:value="newProcedure.min_price" :min="0" :precision="2" placeholder="Min price" />
                </n-form-item> -->
                <n-form-item label="Appointments Needed">
                  <n-input-number v-model:value="newProcedure.appointments_needed" :min="0" placeholder="Appointments needed" />
                </n-form-item>
                <!-- <n-form-item label="Dentist Commission">
                  <n-input-number v-model:value="newProcedure.dentist_commission" :min="0" :precision="2" placeholder="Dentist commission" />
                </n-form-item>
                <n-form-item label="Assistant Commission">
                  <n-input-number v-model:value="newProcedure.assistant_commission" :min="0" :precision="2" placeholder="Assistant commission" />
                </n-form-item> -->
                <n-form-item label="Active Status">
                  <n-switch v-model:value="newProcedure.is_active">
                    <template #checked>Active</template>
                    <template #unchecked>Inactive</template>
                  </n-switch>
                </n-form-item>
              </div>
              <n-button class="section-action" attr-type="button" type="primary" @click="createProcedure" :loading="procedureSubmitting">Add Procedure</n-button>
            </n-card>

            <div class="service-list">
              <n-card v-for="procedure in procedures" :key="procedure.id" size="small" class="service-card">
                <div class="service-form-grid">
                  <n-form-item label="Procedure Name">
                    <n-input v-model:value="procedure.name" placeholder="Procedure name" />
                  </n-form-item>
                  <n-form-item label="Category">
                    <n-input v-model:value="procedure.category" placeholder="Category" />
                  </n-form-item>
                  <n-form-item label="Base Price">
                    <n-input-number v-model:value="procedure.base_price" :min="0" :precision="2" placeholder="Base price" />
                  </n-form-item>
                  <!-- <n-form-item label="Minimum Price">
                    <n-input-number v-model:value="procedure.min_price" :min="0" :precision="2" placeholder="Min price" />
                  </n-form-item> -->
                  <n-form-item label="Appointments Needed">
                    <n-input-number v-model:value="procedure.appointments_needed" :min="0" placeholder="Appointments needed" />
                  </n-form-item>
                  <!-- <n-form-item label="Dentist Commission">
                    <n-input-number v-model:value="procedure.dentist_commission" :min="0" :precision="2" placeholder="Dentist commission" />
                  </n-form-item>
                  <n-form-item label="Assistant Commission">
                    <n-input-number v-model:value="procedure.assistant_commission" :min="0" :precision="2" placeholder="Assistant commission" />
                  </n-form-item> -->
                  <n-form-item label="Active Status">
                    <n-switch v-model:value="procedure.is_active">
                      <template #checked>Active</template>
                      <template #unchecked>Inactive</template>
                    </n-switch>
                  </n-form-item>
                </div>
                <div class="service-actions">
                  <n-button attr-type="button" secondary @click="updateProcedure(procedure)" :loading="pendingProcedureId === procedure.id">Save</n-button>
                  <n-button attr-type="button" type="error" quaternary @click="removeProcedure(procedure)" :loading="pendingDeleteProcedureId === procedure.id">Delete</n-button>
                </div>
              </n-card>
            </div>
          </div>
        </n-tab-pane>

        <!-- <n-tab-pane name="procedures" tab="Procedures">
          <div class="service-section">
            <n-card size="small" title="Add Procedure">
              <div class="service-form-grid">
                <n-form-item label="Procedure Name">
                  <n-input v-model:value="newProcedure.name" />
                </n-form-item>
                <n-form-item label="Category">
                  <n-input v-model:value="newProcedure.category" />
                </n-form-item>
                <n-form-item label="Base Price">
                  <n-input-number v-model:value="newProcedure.base_price" />
                </n-form-item>
                <n-form-item label="Appointments Needed">
                  <n-input-number v-model:value="newProcedure.appointments_needed" />
                </n-form-item>
                <n-form-item label="Active Status">
                  <n-switch v-model:value="newProcedure.is_active" />
                </n-form-item>
              </div>
              <n-button class="section-action" attr-type="button" type="primary" @click="createProcedure" :loading="procedureSubmitting">Add Procedure</n-button>
            </n-card>

            <div class="service-list">
              <n-card v-for="procedure in procedures" :key="procedure.id" size="small" class="service-card">
                <div class="service-form-grid">
                  <n-form-item label="Name">
                    <n-input v-model:value="procedure.name" />
                  </n-form-item>
                  <n-form-item label="Category">
                    <n-input v-model:value="procedure.category" />
                  </n-form-item>
                  <n-form-item label="Base Price">
                    <n-input-number v-model:value="procedure.base_price" />
                  </n-form-item>
                  <n-form-item label="Appointments Needed">
                    <n-input-number v-model:value="procedure.appointments_needed" />
                  </n-form-item>
                  <n-form-item label="Active Status">
                    <n-switch v-model:value="procedure.is_active" />
                  </n-form-item>
                </div>
                <div class="service-actions">
                  <n-button @click="updateProcedure(procedure)" type="primary" size="small" :loading="pendingProcedureId === procedure.id">Update</n-button>
                  <n-button @click="removeProcedure(procedure)" type="error" size="small" :loading="pendingDeleteProcedureId === procedure.id">Delete</n-button>
                </div>
              </n-card>
            </div>
          </div>
        </n-tab-pane> -->

        <n-tab-pane name="prescriptions" tab="Prescriptions">
          <div class="service-section">
            <n-card size="small" title="Add Prescription">
              <div class="service-form-grid">
                <n-form-item label="Drug Name">
                  <n-input v-model:value="newPrescription.name" placeholder="Drug name" />
                </n-form-item>
              </div>
              <n-button class="section-action" attr-type="button" type="primary" @click="createPrescription" :loading="prescriptionSubmitting">Add Prescription</n-button>
            </n-card>

            <div class="service-list">
              <n-card v-for="prescription in prescriptions" :key="prescription.id" size="small" class="service-card">
                <div class="service-form-grid">
                  <n-form-item label="Drug Name">
                    <n-input v-model:value="prescription.name" placeholder="Drug name" />
                  </n-form-item>
                </div>
                <div class="service-actions">
                  <n-button @click="updatePrescription(prescription)" type="primary" size="small" :loading="pendingPrescriptionId === prescription.id">Update</n-button>
                  <n-button @click="removePrescription(prescription)" type="error" size="small" :loading="pendingDeletePrescriptionId === prescription.id">Delete</n-button>
                </div>
              </n-card>
            </div>
          </div>
        </n-tab-pane>

        <n-tab-pane name="permissions" tab="Permissions">
          <div class="permission-grid">
            <n-card title="Receptionist" size="small">
              <div class="perm-list">
                <n-form-item v-for="(_val, key) in receptionistPerms" :key="key" :label="permLabels.rec[key]" label-placement="left">
                  <n-switch v-model:value="formData[key]" />
                </n-form-item>
              </div>
            </n-card>
            <n-card title="Doctor" size="small">
              <div class="perm-list">
                <n-form-item v-for="(_val, key) in doctorPerms" :key="key" :label="permLabels.doc[key]" label-placement="left">
                  <n-switch v-model:value="formData[key]" />
                </n-form-item>
              </div>
            </n-card>
          </div>
        </n-tab-pane>

        <n-tab-pane name="backup" tab="Backup">
          <div class="section-stack">
            <!-- <n-form-item label="Header Template">
              <n-input v-model:value="formData.prescription_template.header" type="textarea" :autosize="{ minRows: 3, maxRows: 6 }" />
            </n-form-item>
            <n-form-item label="Footer Template">
              <n-input v-model:value="formData.prescription_template.footer" type="textarea" :autosize="{ minRows: 2, maxRows: 5 }" />
            </n-form-item> -->

            <n-card title="Database Backup" size="small">
              <p class="backup-copy">Create a full database snapshot or export a monthly archive.</p>
              <n-space wrap>
                <n-button attr-type="button" @click="triggerBackup('full')" :loading="backupLoading === 'full'">Full Backup</n-button>
                <n-button attr-type="button" @click="triggerBackup('monthly')" :loading="backupLoading === 'monthly'">Monthly Data</n-button>
              </n-space>
            </n-card>
          </div>
        </n-tab-pane>
      </n-tabs>
      </n-form>
    </n-card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, toRaw } from 'vue'
import {
  NAlert,
  NButton,
  NCard,
  NDivider,
  NForm,
  NFormItem,
  NInput,
  NInputGroup,
  NInputGroupLabel,
  NInputNumber,
  NSelect,
  NSpace,
  NSwitch,
  NTabPane,
  NTabs,
  NTag,
  useDialog,
  useMessage,
} from 'naive-ui'
import type { FormInst, SelectOption } from 'naive-ui'
import settingsApi from '@/api/settings'
import procedureApi from '@/api/procedure'
import type ProcedureData from '@/api/interfaces/Procedure'
import type PrescriptionData from '@/api/interfaces/Prescription'
import type { SettingsData } from '@/api/interfaces/Settings'
import { resolveBranchId } from '@/api/utils/branchParams'
import useUserStore from '@/stores/user'
import PrescriptionTemplate from '@/components/PrescriptionTemplate.vue'
import prescriptionApi from '@api/prescription'

const message = useMessage()
const dialog = useDialog()
const formRef = ref<FormInst | null>(null)
const loading = ref(false)
const backupLoading = ref<'full' | 'monthly' | null>(null)
const procedureSubmitting = ref(false)
const pendingProcedureId = ref<number | null>(null)
const pendingDeleteProcedureId = ref<number | null>(null)
const activeTab = ref('branch')
const userStore = useUserStore()

const defaultHours = {
  monday: { start: '09:00', end: '17:00', is_off: false },
  tuesday: { start: '09:00', end: '17:00', is_off: false },
  wednesday: { start: '09:00', end: '17:00', is_off: false },
  thursday: { start: '09:00', end: '17:00', is_off: false },
  friday: { start: '09:00', end: '17:00', is_off: false },
  saturday: { start: null, end: null, is_off: true },
  sunday: { start: null, end: null, is_off: true },
}

const createDefaultSettings = (): SettingsData => ({
  branch_id: 1,
  clinic_name: '',
  address: '',
  phone: '',
  currency: 'USD',
  working_hours: JSON.parse(JSON.stringify(defaultHours)),
  wa_patient_reminder: '',
  wa_patient_cancel: '',
  wa_patient_complete: '',
  wa_supplier_order: '',
  wa_supplier_cancel: '',
  prescription_template: {
    header: '{{clinic_name}}\n{{address}}\n{{phone}}',
    footer: 'Thank you for choosing {{clinic_name}}.',
  },
  rec_can_edit_whatsapp: false,
  rec_can_view_phones: true,
  rec_show_kpi: false,
  rec_show_suppliers: false,
  rec_log_actions: true,
  rec_can_void_transactions: false,
  rec_can_edit_devices: false,
  rec_can_contact_support: true,
  doc_view_appointments: true,
  doc_save_xrays: false,
  doc_view_files: true,
  doc_view_contact: true,
  doc_edit_assets: false,
  doc_issue_prescriptions: true,
})

const formData = reactive<SettingsData>(createDefaultSettings())
const initialSettings = ref<SettingsData | null>(null)

const procedures = ref<ProcedureData[]>([])

const newProcedure = reactive<ProcedureData>({
  name: '',
  slug: '',
  category: '',
  base_price: 0,
  min_price: 0,
  appointments_needed: 1,
  dentist_commission: 0,
  assistant_commission: 0,
  is_active: true,
})

const prescriptions = ref<PrescriptionData[]>([])

const newPrescription = reactive<PrescriptionData>({
  name: '',
})

const prescriptionSubmitting = ref(false)
const pendingPrescriptionId = ref<number | null>(null)
const pendingDeletePrescriptionId = ref<number | null>(null)

const rules = {
  clinic_name: { required: true, message: 'Clinic name is required', trigger: 'blur' },
  address: { required: true, message: 'Address is required', trigger: 'blur' },
  phone: { required: true, message: 'Phone is required', trigger: 'blur' },
}

const currencyOptions: SelectOption[] = [
  { label: 'USD ($)', value: 'USD' },
  { label: 'EUR (€)', value: 'EUR' },
  { label: 'GBP (£)', value: 'GBP' },
  { label: 'SAR (﷼)', value: 'SAR' },
  { label: 'AED (د.إ)', value: 'AED' },
]

const receptionistPerms = computed(() => ({
  rec_can_edit_whatsapp: formData.rec_can_edit_whatsapp,
  rec_can_view_phones: formData.rec_can_view_phones,
  rec_show_kpi: formData.rec_show_kpi,
  // rec_show_suppliers: formData.rec_show_suppliers,
  rec_log_actions: formData.rec_log_actions,
  rec_can_void_transactions: formData.rec_can_void_transactions,
  rec_can_edit_devices: formData.rec_can_edit_devices,
  rec_can_contact_support: formData.rec_can_contact_support,
}))

const doctorPerms = computed(() => ({
  doc_view_appointments: formData.doc_view_appointments,
  // doc_save_xrays: formData.doc_save_xrays,
  // doc_view_files: formData.doc_view_files,
  doc_view_contact: formData.doc_view_contact,
  doc_edit_assets: formData.doc_edit_assets,
  doc_issue_prescriptions: formData.doc_issue_prescriptions,
}))

const permLabels = {
  rec: {
    rec_can_edit_whatsapp: 'Edit WA Templates',
    rec_can_view_phones: 'View Bulk Phones',
    rec_show_kpi: 'View KPI Dashboard',
    // rec_show_suppliers: 'Access Suppliers',
    rec_log_actions: 'Log Receptionist Actions',
    rec_can_void_transactions: 'Void Transactions',
    rec_can_edit_devices: 'Edit Device Status',
    rec_can_contact_support: 'Contact Tech Support',
  } as Record<string, string>,
  doc: {
    doc_view_appointments: 'View Appointments',
    // doc_save_xrays: 'Save X-Rays',
    // doc_view_files: 'View Patient Files',
    doc_view_contact: 'View Patient\'s Contact Details ',
    doc_edit_assets: 'Edit Clinic Assets',
    doc_issue_prescriptions: 'Issue Prescriptions',
  } as Record<string, string>,
}

function capitalize(str: string) {
  return str.charAt(0).toUpperCase() + str.slice(1)
}

function asBoolean(value: unknown, fallback = false) {
  return typeof value === 'boolean' ? value : fallback
}

function applySettings(data: SettingsData) {
  const defaults = createDefaultSettings()

  Object.assign(formData, createDefaultSettings(), data, {
    working_hours: {
      ...JSON.parse(JSON.stringify(defaultHours)),
      ...(data.working_hours || {}),
    },
    prescription_template: {
      header: data.prescription_template?.header || defaults.prescription_template.header,
      footer: data.prescription_template?.footer || defaults.prescription_template.footer,
    },
    rec_can_edit_whatsapp: asBoolean(data.rec_can_edit_whatsapp, defaults.rec_can_edit_whatsapp),
    rec_can_view_phones: asBoolean(data.rec_can_view_phones, defaults.rec_can_view_phones),
    rec_show_kpi: asBoolean(data.rec_show_kpi, defaults.rec_show_kpi),
    rec_show_suppliers: asBoolean(data.rec_show_suppliers, defaults.rec_show_suppliers),
    rec_log_actions: asBoolean(data.rec_log_actions, defaults.rec_log_actions),
    rec_can_void_transactions: asBoolean(data.rec_can_void_transactions, defaults.rec_can_void_transactions),
    rec_can_edit_devices: asBoolean(data.rec_can_edit_devices, defaults.rec_can_edit_devices),
    rec_can_contact_support: asBoolean(data.rec_can_contact_support, defaults.rec_can_contact_support),
    doc_view_appointments: asBoolean(data.doc_view_appointments, defaults.doc_view_appointments),
    doc_save_xrays: asBoolean(data.doc_save_xrays, defaults.doc_save_xrays),
    doc_view_files: asBoolean(data.doc_view_files, defaults.doc_view_files),
    doc_view_contact: asBoolean(data.doc_view_contact, defaults.doc_view_contact),
    doc_edit_assets: asBoolean(data.doc_edit_assets, defaults.doc_edit_assets),
    doc_issue_prescriptions: asBoolean(data.doc_issue_prescriptions, defaults.doc_issue_prescriptions),
  })
}

function unwrapSettingsResponse(payload: SettingsData | { data: SettingsData }): SettingsData {
  return 'data' in payload ? payload.data : payload
}

function normalizeProcedure(procedure: any): ProcedureData {
  return {
    id: procedure.id,
    name: procedure.name || '',
    slug: procedure.slug || '',
    category: procedure.category || '',
    base_price: Number(procedure.base_price || 0),
    min_price: Number(procedure.min_price || 0),
    appointments_needed: Number(procedure.appointments_needed || 0),
    dentist_commission: Number(procedure.dentist_commission || 0),
    assistant_commission: Number(procedure.assistant_commission || 0),
    is_active: Boolean(procedure.is_active),
    inventory_requirements: procedure.inventory_requirements || [],
  }
}

function normalizePrescription(prescription: any): PrescriptionData {
  return {
    id: prescription.id,
    name: prescription.name || '',
  }
}

function buildSettingsPayload(): Partial<SettingsData> {
  return {
    clinic_name: formData.clinic_name,
    address: formData.address,
    phone: formData.phone,
    currency: formData.currency,
    working_hours: formData.working_hours,
    wa_patient_reminder: formData.wa_patient_reminder,
    wa_patient_cancel: formData.wa_patient_cancel,
    wa_patient_complete: formData.wa_patient_complete,
    wa_supplier_order: formData.wa_supplier_order,
    wa_supplier_cancel: formData.wa_supplier_cancel,
    prescription_template: formData.prescription_template,
    rec_can_edit_whatsapp: Boolean(formData.rec_can_edit_whatsapp),
    rec_can_view_phones: Boolean(formData.rec_can_view_phones),
    rec_show_kpi: Boolean(formData.rec_show_kpi),
    rec_show_suppliers: Boolean(formData.rec_show_suppliers),
    rec_log_actions: Boolean(formData.rec_log_actions),
    rec_can_void_transactions: Boolean(formData.rec_can_void_transactions),
    rec_can_edit_devices: Boolean(formData.rec_can_edit_devices),
    rec_can_contact_support: Boolean(formData.rec_can_contact_support),
    doc_view_appointments: Boolean(formData.doc_view_appointments),
    doc_save_xrays: Boolean(formData.doc_save_xrays),
    doc_view_files: Boolean(formData.doc_view_files),
    doc_view_contact: Boolean(formData.doc_view_contact),
    doc_edit_assets: Boolean(formData.doc_edit_assets),
    doc_issue_prescriptions: Boolean(formData.doc_issue_prescriptions),
  }
}

async function loadData() {
  const [settingsResponse, proceduresResponse, prescriptionsResponse] = await Promise.all([
    settingsApi.getSettings(resolveBranchId()),
    procedureApi.getProcedures(),
    prescriptionApi.getBranchPrescriptions(),
  ])

  const settings = unwrapSettingsResponse(settingsResponse.data)
  applySettings(settings)
  initialSettings.value = JSON.parse(JSON.stringify(settings))

  procedures.value = Array.isArray(proceduresResponse.data?.data)
    ? proceduresResponse.data.data.map(normalizeProcedure)
    : Array.isArray(proceduresResponse.data)
      ? proceduresResponse.data.map(normalizeProcedure)
      : []

  prescriptions.value = Array.isArray(prescriptionsResponse.data?.data)
    ? prescriptionsResponse.data.data.map(normalizePrescription)
    : Array.isArray(prescriptionsResponse.data)
      ? prescriptionsResponse.data.map(normalizePrescription)
      : []
}

async function submitForm() {
  try {
    await formRef.value?.validate()
  } catch {
    return
  }

  loading.value = true
  try {
    const response = await settingsApi.updateSettings(formData.branch_id, buildSettingsPayload())
    const settings = unwrapSettingsResponse(response.data)
    applySettings(settings)
    initialSettings.value = JSON.parse(JSON.stringify(settings))
    message.success('Settings saved successfully')
  } catch {
    message.error('Failed to save settings')
  } finally {
    loading.value = false
  }
}



function resetNewProcedure() {
  Object.assign(newProcedure, {
    name: '',
    slug: '',
    category: '',
    base_price: 0,
    min_price: 0,
    appointments_needed: 1,
    dentist_commission: 0,
    assistant_commission: 0,
    is_active: true,
  })
}

async function createProcedure() {
  procedureSubmitting.value = true
  try {
    const response = await procedureApi.postProcedure(toRaw(newProcedure))
    procedures.value.unshift(normalizeProcedure(response.data.data ?? response.data))
    resetNewProcedure()
    message.success('Procedure created')
  } catch {
    message.error('Failed to create procedure')
  } finally {
    procedureSubmitting.value = false
  }
}

async function updateProcedure(procedure: ProcedureData) {
  if (!procedure.id) return
  pendingProcedureId.value = procedure.id
  try {
    const response = await procedureApi.putProcedure(procedure.id, procedure)
    const index = procedures.value.findIndex((entry) => entry.id === procedure.id)
    if (index !== -1) procedures.value[index] = normalizeProcedure(response.data.data ?? response.data)
    message.success('Procedure updated')
  } catch {
    message.error('Failed to update procedure')
  } finally {
    pendingProcedureId.value = null
  }
}

function removeProcedure(procedure: ProcedureData) {
  if (!procedure.id) return
  dialog.warning({
    title: 'Delete procedure?',
    content: `Delete ${procedure.name || 'this procedure'}?`,
    positiveText: 'Delete',
    negativeText: 'Cancel',
    async onPositiveClick() {
      pendingDeleteProcedureId.value = procedure.id || null
      try {
        await procedureApi.deleteProcedure(procedure.id!)
        procedures.value = procedures.value.filter((entry) => entry.id !== procedure.id)
        message.success('Procedure deleted')
      } catch {
        message.error('Failed to delete procedure')
      } finally {
        pendingDeleteProcedureId.value = null
      }
    },
  })
}

function resetNewPrescription() {
  Object.assign(newPrescription, {
    name: '',
    instructions: '',
    category: '',
    is_active: true,
  })
}

async function createPrescription() {
  prescriptionSubmitting.value = true
  try {
    const response = await prescriptionApi.postPrescription({ name: newPrescription.name })
    prescriptions.value.unshift(normalizePrescription(response.data.data ?? response.data))
    resetNewPrescription()
    message.success('Prescription created')
  } catch {
    message.error('Failed to create prescription')
  } finally {
    prescriptionSubmitting.value = false
  }
}

async function updatePrescription(prescription: PrescriptionData) {
  if (!prescription.id) return
  pendingPrescriptionId.value = prescription.id
  try {
    const response = await prescriptionApi.updatePrescription(prescription.id, { name: prescription.name })
    const index = prescriptions.value.findIndex((entry) => entry.id === prescription.id)
    if (index !== -1) prescriptions.value[index] = normalizePrescription(response.data.data ?? response.data)
    message.success('Prescription updated')
  } catch {
    message.error('Failed to update prescription')
  } finally {
    pendingPrescriptionId.value = null
  }
}

function removePrescription(prescription: any) {
  if (!prescription.id) return
  dialog.warning({
    title: 'Delete prescription?',
    content: `Delete ${prescription.name || 'this prescription'}?`,
    positiveText: 'Delete',
    negativeText: 'Cancel',
    async onPositiveClick() {
      pendingDeletePrescriptionId.value = prescription.id || null
      try {
        await prescriptionApi.deletePrescriptionn(prescription.id)
        prescriptions.value = prescriptions.value.filter((entry) => entry.id !== prescription.id)
        message.success('Prescription deleted')
      } catch {
        message.error('Failed to delete prescription')
      } finally {
        pendingDeletePrescriptionId.value = null
      }
    },
  })
}

function resetForm() {
  dialog.warning({
    title: 'Reset changes?',
    content: 'All unsaved settings changes will be discarded.',
    positiveText: 'Reset',
    negativeText: 'Cancel',
    onPositiveClick() {
      if (initialSettings.value) applySettings(initialSettings.value)
    },
  })
}

async function triggerBackup(type: 'full' | 'monthly') {
  dialog.info({
    title: `Start ${type} backup?`,
    content: 'This will generate and download the SQL backup.',
    positiveText: 'Proceed',
    negativeText: 'Cancel',

    async onPositiveClick() {
      backupLoading.value = type

      try {
        const response = await settingsApi.backupDatabase(type)

        // Create downloadable blob
        const blob = new Blob([response.data], {
          type: 'application/sql',
        })

        // Extract filename from headers
        const disposition = response.headers['content-disposition']

        let filename = `backup_${type}.sql`

        if (disposition) {
          const match = disposition.match(/filename="?([^"]+)"?/)
          if (match?.[1]) {
            filename = match[1]
          }
        }

        // Trigger browser download
        const url = window.URL.createObjectURL(blob)

        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', filename)

        document.body.appendChild(link)
        link.click()

        link.remove()

        window.URL.revokeObjectURL(url)

        message.success('Backup downloaded successfully')
      } catch (error) {
        console.error(error)
        message.error('Failed to download backup')
      } finally {
        backupLoading.value = null
      }
    },
  })
}

onMounted(async () => {
  try {
    await loadData()
  } catch {
    message.error('Failed to load settings data')
  }
})
</script>

<style scoped>
.settings-page {
  min-height: 100%;
  padding: 16px;
  background:
    radial-gradient(circle at top left, rgba(59, 130, 246, 0.08), transparent 28%),
    linear-gradient(180deg, #f8fbff 0%, #f3f6fb 100%);
}

.settings-shell {
  border-radius: 20px;
  box-shadow: 0 18px 50px rgba(15, 23, 42, 0.08);
}

.header-bar {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  align-items: flex-start;
  flex-wrap: wrap;
}

.page-title {
  margin: 0;
  font-size: 1.4rem;
  font-weight: 700;
  color: #0f172a;
}

.page-subtitle {
  margin: 6px 0 0;
  color: #64748b;
}

.header-actions {
  justify-content: flex-end;
}

.settings-tabs {
  margin-top: 8px;
}

.section-stack {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.responsive-grid,
.service-form-grid,
.permission-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.full-span {
  grid-column: 1 / -1;
}

.hours-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
  gap: 16px;
}

.day-card {
  background: #fbfdff;
}

.day-header {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  align-items: center;
  margin-bottom: 12px;
}

.day-name {
  font-weight: 600;
  color: #1e293b;
}

.day-inputs,
.perm-list,
.service-section,
.service-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.service-card {
  border-radius: 14px;
}

.service-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 12px;
}

.section-action {
  margin-top: 16px;
}

.backup-copy {
  margin: 0 0 16px;
  color: #64748b;
}

.time-input {
  width: 100%;
  min-width: 0;
  padding: 0 12px;
  border: 1px solid rgb(224, 224, 230);
  border-radius: 0 3px 3px 0;
  min-height: 34px;
  color: #0f172a;
  background: #fff;
}

@media (max-width: 900px) {
  .responsive-grid,
  .service-form-grid,
  .permission-grid {
    grid-template-columns: 1fr;
  }

  .header-actions {
    width: 100%;
    justify-content: flex-start;
  }
}

@media (max-width: 640px) {
  .settings-page {
    padding: 10px;
  }

  .service-actions {
    justify-content: stretch;
    flex-direction: column;
  }
}
</style>
