<template>
  <div class="settings-page">
    <n-card class="settings-shell" :bordered="false">
      <template #header>
        <div class="header-bar">
          <div>
            <h2 class="page-title">{{ t('settingsView.pageTitle') }}</h2>
            <p class="page-subtitle">{{ t('settingsView.pageSubtitle') }}</p>
          </div>
          <n-space class="header-actions" wrap>
            <n-button @click="resetForm" secondary>{{ t('settingsView.resetButton') }}</n-button>
            <n-button type="primary" @click="submitForm" :loading="loading">{{ t('settingsView.saveChangesButton') }}</n-button>
          </n-space>
        </div>
      </template>

      <n-form ref="formRef" :model="formData" :rules="rules" label-placement="top" @submit.prevent>
        <n-tabs v-model:value="activeTab" type="line" animated placement="top" class="settings-tabs">
          <n-tab-pane name="branch" :tab="t('settingsView.tabs.branch')">
            <div class="section-stack">
              <div class="responsive-grid">
                <n-form-item :label="t('settingsView.branchForm.clinicNameLabel')" path="clinic_name">
                  <n-input v-model:value="formData.clinic_name" :placeholder="t('settingsView.branchForm.clinicNamePlaceholder')" />
                </n-form-item>
                <n-form-item class="full-span" :label="t('settingsView.branchForm.addressLabel')" path="address">
                  <n-input v-model:value="formData.address" type="textarea" :placeholder="t('settingsView.branchForm.addressPlaceholder')" :autosize="{ minRows: 3, maxRows: 5 }" />
                </n-form-item>
                <n-form-item :label="t('settingsView.branchForm.phoneLabel')" path="phone">
                  <n-input v-model:value="formData.phone" :placeholder="t('settingsView.branchForm.phonePlaceholder')" />
                </n-form-item>
                <n-form-item :label="t('settingsView.branchForm.receptionFeeLabel')" path="reception">
                  <n-input-number v-model:value="formData.reception" :min="0" :step="50" />
                </n-form-item>
              </div>
            </div>
          </n-tab-pane>

          <n-tab-pane name="whatsapp" :tab="t('settingsView.tabs.whatsapp')">
            <div class="section-stack whatsapp-section" :dir="whatsappDir">
              <n-alert type="info" show-icon>
                <template #header>{{ t('settingsView.whatsappSection.dynamicVariablesTitle') }}</template>
                {{ t('settingsView.whatsappSection.dynamicVariablesCopy') }}
                <n-tag size="small" type="info">{patient_name}</n-tag>
                <n-tag size="small" type="info">{appointment_date}</n-tag>
                <n-tag size="small" type="info">{clinic_name}</n-tag>
                <n-tag size="small" type="info">{appointment_time}</n-tag>
                <n-tag size="small" type="info">{doctor_name}</n-tag>
                <n-tag size="small" type="info">{description}</n-tag>
              </n-alert>

              <div class="responsive-grid">
                <n-form-item :label="t('settingsView.whatsappForm.patientReminderLabel')">
                  <n-input
                    v-model:value="formData.wa_patient_reminder"
                    type="textarea"
                    :autosize="{ minRows: 3, maxRows: 6 }"
                    :input-props="{ dir: whatsappDir }"
                    :placeholder="t('settingsView.whatsappForm.patientReminderPlaceholder')"
                  />
                </n-form-item>
                <n-form-item :label="t('settingsView.whatsappForm.patientCancellationLabel')">
                  <n-input
                    v-model:value="formData.wa_patient_cancel"
                    type="textarea"
                    :autosize="{ minRows: 3, maxRows: 6 }"
                    :input-props="{ dir: whatsappDir }"
                    :placeholder="t('settingsView.whatsappForm.patientCancellationPlaceholder')"
                  />
                </n-form-item>
                <n-form-item :label="t('settingsView.whatsappForm.patientCompletionLabel')">
                  <n-input
                    v-model:value="formData.wa_patient_complete"
                    type="textarea"
                    :autosize="{ minRows: 3, maxRows: 6 }"
                    :input-props="{ dir: whatsappDir }"
                    :placeholder="t('settingsView.whatsappForm.patientCompletionPlaceholder')"
                  />
                </n-form-item>
              </div>
            </div>
          </n-tab-pane>

          <n-tab-pane name="procedures" :tab="t('settingsView.tabs.procedures')">
            <div class="service-section">
              <n-card size="small" :title="t('settingsView.procedureForm.addProcedureTitle')">
                <div class="service-form-grid">
                  <n-form-item :label="t('settingsView.procedureForm.procedureNameLabel')">
                    <n-input v-model:value="newProcedure.name" :placeholder="t('settingsView.procedureForm.procedureNamePlaceholder')" />
                  </n-form-item>
                  <n-form-item :label="t('settingsView.procedureForm.categoryLabel')">
                    <n-input v-model:value="newProcedure.category" :placeholder="t('settingsView.procedureForm.categoryPlaceholder')" />
                  </n-form-item>
                  <n-form-item :label="t('settingsView.procedureForm.basePriceLabel')">
                    <n-input-number v-model:value="newProcedure.base_price" :min="0" :precision="2" :placeholder="t('settingsView.procedureForm.basePricePlaceholder')" />
                  </n-form-item>
                  <n-form-item :label="t('settingsView.procedureForm.appointmentsNeededLabel')">
                    <n-input-number v-model:value="newProcedure.appointments_needed" :min="0" :placeholder="t('settingsView.procedureForm.appointmentsNeededPlaceholder')" />
                  </n-form-item>
                  <n-form-item :label="t('settingsView.procedureForm.activeStatusLabel')">
                    <n-switch v-model:value="newProcedure.is_active">
                      <template #checked>{{ t('settingsView.procedureForm.activeStatusChecked') }}</template>
                      <template #unchecked>{{ t('settingsView.procedureForm.activeStatusUnchecked') }}</template>
                    </n-switch>
                  </n-form-item>
                </div>
                <n-button class="section-action" attr-type="button" type="primary" @click="createProcedure" :loading="procedureSubmitting">{{ t('settingsView.procedureForm.addProcedureButton') }}</n-button>
              </n-card>

              <div class="service-list">
                <n-card v-for="procedure in procedures" :key="procedure.id" size="small" class="service-card">
                  <div class="service-form-grid">
                    <n-form-item :label="t('settingsView.procedureForm.procedureNameLabel')">
                      <n-input v-model:value="procedure.name" :placeholder="t('settingsView.procedureForm.procedureNamePlaceholder')" />
                    </n-form-item>
                    <n-form-item :label="t('settingsView.procedureForm.categoryLabel')">
                      <n-input v-model:value="procedure.category" :placeholder="t('settingsView.procedureForm.categoryPlaceholder')" />
                    </n-form-item>
                    <n-form-item :label="t('settingsView.procedureForm.basePriceLabel')">
                      <n-input-number v-model:value="procedure.base_price" :min="0" :precision="2" :placeholder="t('settingsView.procedureForm.basePricePlaceholder')" />
                    </n-form-item>
                    <n-form-item :label="t('settingsView.procedureForm.appointmentsNeededLabel')">
                      <n-input-number v-model:value="procedure.appointments_needed" :min="0" :placeholder="t('settingsView.procedureForm.appointmentsNeededPlaceholder')" />
                    </n-form-item>
                    <n-form-item :label="t('settingsView.procedureForm.activeStatusLabel')">
                      <n-switch v-model:value="procedure.is_active">
                        <template #checked>{{ t('settingsView.procedureForm.activeStatusChecked') }}</template>
                        <template #unchecked>{{ t('settingsView.procedureForm.activeStatusUnchecked') }}</template>
                      </n-switch>
                    </n-form-item>
                  </div>
                  <div class="service-actions">
                    <n-button attr-type="button" secondary @click="updateProcedure(procedure)" :loading="pendingProcedureId === procedure.id">{{ t('settingsView.procedureForm.saveProcedureButton') }}</n-button>
                    <n-button attr-type="button" type="error" quaternary @click="removeProcedure(procedure)" :loading="pendingDeleteProcedureId === procedure.id">{{ t('settingsView.procedureForm.deleteProcedureButton') }}</n-button>
                  </div>
                </n-card>
              </div>
            </div>
          </n-tab-pane>

          <n-tab-pane name="prescriptions" :tab="t('settingsView.tabs.prescriptions')">
            <div class="service-section">
              <n-card size="small" :title="t('settingsView.prescriptionForm.addPrescriptionTitle')">
                <div class="service-form-grid">
                  <n-form-item :label="t('settingsView.prescriptionForm.medicineNameLabel')">
                    <n-input v-model:value="newPrescription.name" :placeholder="t('settingsView.prescriptionForm.medicineNamePlaceholder')" />
                  </n-form-item>
                </div>
                <n-button class="section-action" attr-type="button" type="primary" @click="createPrescription" :loading="prescriptionSubmitting">{{ t('settingsView.prescriptionForm.addPrescriptionButton') }}</n-button>
              </n-card>

              <div class="service-list">
                <n-card v-for="prescription in prescriptions" :key="prescription.id" size="small" class="service-card">
                  <div class="service-form-grid">
                    <n-form-item :label="t('settingsView.prescriptionForm.medicineNameLabel')">
                      <n-input v-model:value="prescription.name" :placeholder="t('settingsView.prescriptionForm.medicineNamePlaceholder')" />
                    </n-form-item>
                  </div>
                  <div class="service-actions">
                    <n-button @click="updatePrescription(prescription)" type="primary" size="small" :loading="pendingPrescriptionId === prescription.id">{{ t('settingsView.prescriptionForm.updatePrescriptionButton') }}</n-button>
                    <n-button @click="removePrescription(prescription)" type="error" size="small" :loading="pendingDeletePrescriptionId === prescription.id">{{ t('settingsView.prescriptionForm.deletePrescriptionButton') }}</n-button>
                  </div>
                </n-card>
              </div>
            </div>
          </n-tab-pane>

          <n-tab-pane name="permissions" :tab="t('settingsView.tabs.permissions')">
            <div class="permission-grid">
              <n-card :title="t('settingsView.permissionCards.receptionistTitle')" size="small">
                <div class="perm-list">
                  <n-form-item v-for="(_val, key) in receptionistPerms" :key="key" :label="permLabels.rec[key]" label-placement="left">
                    <n-switch v-model:value="formData[key]" />
                  </n-form-item>
                </div>
              </n-card>
              <n-card :title="t('settingsView.permissionCards.doctorTitle')" size="small">
                <div class="perm-list">
                  <n-form-item v-for="(_val, key) in doctorPerms" :key="key" :label="permLabels.doc[key]" label-placement="left">
                    <n-switch v-model:value="formData[key]" />
                  </n-form-item>
                </div>
              </n-card>
            </div>
          </n-tab-pane>

          <n-tab-pane name="backup" :tab="t('settingsView.tabs.backup')">
            <div class="section-stack">
              <n-card :title="t('settingsView.backupCard.title')" size="small">
                <p style="margin-bottom: .5em;">{{ t('settingsView.backupCard.description') }}</p>
                <!-- <p class="backup-copy">{{ t('settingsView.backupCard.description') }}</p>
                <p>{{ t('settingsView.backupCard.freePlanAlert') }}</p> -->
                <n-space wrap>
                  <n-button attr-type="button" @click="handleDownloadBackup" :loading="isBackingUp">
                    {{ t('settingsView.backupCard.fullBackupButton') }}
                  </n-button>
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
  NForm,
  NFormItem,
  NInput,
  NInputNumber,
  NSpace,
  NSwitch,
  NTabPane,
  NTabs,
  NTag,
  useDialog,
  useMessage,
} from 'naive-ui'
import type { FormInst } from 'naive-ui'
import settingsApi from '@/api/settings'
import procedureApi from '@/api/procedure'
import type ProcedureData from '@/api/interfaces/Procedure'
import type { ProcedureInventory } from '@/api/interfaces/InventoryStock'
import type PrescriptionData from '@/api/interfaces/Prescription'
import type { SettingsData } from '@/api/interfaces/Settings'
import { resolveBranchId } from '@/api/utils/branchParams'
import prescriptionApi from '@api/prescription'
import { useI18n } from 'vue-i18n'

const message = useMessage()
const dialog = useDialog()
const formRef = ref<FormInst | null>(null)
const loading = ref(false)
const procedureSubmitting = ref(false)
const pendingProcedureId = ref<number | null>(null)
const pendingDeleteProcedureId = ref<number | null>(null)
const activeTab = ref('branch')
const { t, locale } = useI18n()
const isRtl = computed(() => locale.value === 'dr' || locale.value === 'ps')
const whatsappDir = computed(() => (isRtl.value ? 'rtl' : 'ltr'))

// ADDED: Tracks backup generation processing state
const isBackingUp = ref(false)

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
  currency: 'AFN',
  reception: 0,
  working_hours: JSON.parse(JSON.stringify(defaultHours)),
  wa_patient_reminder: '',
  wa_patient_cancel: '',
  wa_patient_complete: '',
  wa_supplier_order: '',
  wa_supplier_cancel: '',
  prescription_template: {
    header: '{clinic_name}\n{address}\n{phone}',
    footer: 'Thank you for choosing our clinic.',
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
const newPrescription = reactive<PrescriptionData>({ name: '' })

const prescriptionSubmitting = ref(false)
const pendingPrescriptionId = ref<number | null>(null)
const pendingDeletePrescriptionId = ref<number | null>(null)

const rules = {
  clinic_name: { required: true, message: computed(() => t('settingsView.validation.clinicNameRequired')).value, trigger: 'blur' },
  address: { required: true, message: computed(() => t('settingsView.validation.addressRequired')).value, trigger: 'blur' },
  phone: { required: true, message: computed(() => t('settingsView.validation.phoneRequired')).value, trigger: 'blur' },
}

const receptionistPerms = computed(() => ({
  rec_can_view_phones: formData.rec_can_view_phones,
  rec_show_kpi: formData.rec_show_kpi,
  rec_can_void_transactions: formData.rec_can_void_transactions,
  rec_can_edit_devices: formData.rec_can_edit_devices,
}))

const doctorPerms = computed(() => ({
  doc_view_contact: formData.doc_view_contact,
  doc_edit_assets: formData.doc_edit_assets,
  doc_issue_prescriptions: formData.doc_issue_prescriptions,
}))

const permLabels = computed(() => ({
  rec: {
    rec_can_edit_whatsapp: t('settingsView.permissionLabels.rec_can_edit_whatsapp'),
    rec_can_view_phones: t('settingsView.permissionLabels.rec_can_view_phones'),
    rec_show_kpi: t('settingsView.permissionLabels.rec_show_kpi'),
    rec_log_actions: t('settingsView.permissionLabels.rec_log_actions'),
    rec_can_void_transactions: t('settingsView.permissionLabels.rec_can_void_transactions'),
    rec_can_edit_devices: t('settingsView.permissionLabels.rec_can_edit_devices'),
    rec_can_contact_support: t('settingsView.permissionLabels.rec_can_contact_support'),
  },
  doc: {
    doc_view_appointments: t('settingsView.permissionLabels.doc_view_appointments'),
    doc_view_contact: t('settingsView.permissionLabels.doc_view_contact'),
    doc_edit_assets: t('settingsView.permissionLabels.doc_edit_assets'),
    doc_issue_prescriptions: t('settingsView.permissionLabels.doc_issue_prescriptions'),
  },
}))

function asBoolean(value: unknown, fallback = false) {
  return typeof value === 'boolean' ? value : fallback
}

function normalizeText(value: unknown, fallback = '') {
  return typeof value === 'string' ? value : fallback
}

function applySettings(data: SettingsData) {
  const defaults = createDefaultSettings()

  Object.assign(formData, createDefaultSettings(), data, {
    working_hours: {
      ...JSON.parse(JSON.stringify(defaultHours)),
      ...data.working_hours,
    },
    prescription_template: {
      header: data.prescription_template?.header || defaults.prescription_template.header,
      footer: data.prescription_template?.footer || defaults.prescription_template.footer,
    },
    wa_patient_reminder: normalizeText(data.wa_patient_reminder),
    wa_patient_cancel: normalizeText(data.wa_patient_cancel),
    wa_patient_complete: normalizeText(data.wa_patient_complete),
    wa_supplier_order: normalizeText(data.wa_supplier_order),
    wa_supplier_cancel: normalizeText(data.wa_supplier_cancel),
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
    reception: Number(((data as SettingsData & { reception_cost?: number })).reception_cost ?? data.reception ?? 0),
    doc_view_contact: asBoolean(data.doc_view_contact, defaults.doc_view_contact),
    doc_edit_assets: asBoolean(data.doc_edit_assets, defaults.doc_edit_assets),
    doc_issue_prescriptions: asBoolean(data.doc_issue_prescriptions, defaults.doc_issue_prescriptions),
  })
}

function unwrapSettingsResponse(payload: SettingsData | { data: SettingsData }): SettingsData {
  return 'data' in payload ? payload.data : payload
}

function normalizeProcedure(procedure: Record<string, unknown>): ProcedureData {
  return {
    id: Number(procedure.id as number) || undefined,
    name: String(procedure.name ?? ''),
    slug: String(procedure.slug ?? ''),
    category: String(procedure.category ?? ''),
    base_price: Number(procedure.base_price ?? 0),
    min_price: Number(procedure.min_price ?? 0),
    appointments_needed: Number(procedure.appointments_needed ?? 0),
    dentist_commission: Number(procedure.dentist_commission ?? 0),
    assistant_commission: Number(procedure.assistant_commission ?? 0),
    is_active: Boolean(procedure.is_active),
    inventory_requirements: Array.isArray(procedure.inventory_requirements)
      ? (procedure.inventory_requirements as ProcedureInventory[])
      : [],
  }
}

function normalizePrescription(prescription: Record<string, unknown>): PrescriptionData {
  return {
    id: Number(prescription.id as number) || undefined,
    name: String(prescription.name ?? prescription.drug_name ?? ''),
  }
}

function buildSettingsPayload(): Record<string, unknown> {
  return {
    clinic_name: formData.clinic_name,
    address: formData.address,
    phone: formData.phone,
    currency: formData.currency,
    working_hours: formData.working_hours,
    wa_patient_reminder: normalizeText(formData.wa_patient_reminder),
    wa_patient_cancel: normalizeText(formData.wa_patient_cancel),
    wa_patient_complete: normalizeText(formData.wa_patient_complete),
    wa_supplier_order: normalizeText(formData.wa_supplier_order),
    wa_supplier_cancel: normalizeText(formData.wa_supplier_cancel),
    reception_cost: Number(formData.reception),
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
    procedureApi.getProcedures(true),
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
    drug_name: '',
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

function removePrescription(prescription: PrescriptionData) {
  if (!prescription.id) return
  dialog.warning({
    title: 'Delete prescription?',
    content: `Delete ${prescription.name || 'this prescription'}?`,
    positiveText: 'Delete',
    negativeText: 'Cancel',
    async onPositiveClick() {
      pendingDeletePrescriptionId.value = prescription.id || null
      try {
        await prescriptionApi.deletePrescription(prescription.id!)
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

// ADDED: Programmatic download trigger mapping that links with responseType: 'blob'
async function handleDownloadBackup() {
  isBackingUp.value = true
  try {
    const response = await settingsApi.triggerBackup()

    // Package stream array segments into binary Blob configuration
    const blob = new Blob([response.data], { type: 'application/json' })
    const downloadUrl = window.URL.createObjectURL(blob)

    // Construct automated backup naming pattern
    const timestamp = new Date().toISOString().replace(/[-:T]/g, '').split('.')[0]
    const filename = `backup-branch-${formData.branch_id || resolveBranchId()}-${timestamp}.json`

    // Generate transient Anchor node element, inject into viewport, click, and discard
    const link = document.createElement('a')
    link.href = downloadUrl
    link.setAttribute('download', filename)
    document.body.appendChild(link)
    link.click()

    document.body.removeChild(link)
    window.URL.revokeObjectURL(downloadUrl)
    message.success('Database backup downloaded successfully')
  } catch (error) {
    console.error('Database backup generation aborted:', error)
    message.error('Failed to generate structural backup file')
  } finally {
    isBackingUp.value = false
  }
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
