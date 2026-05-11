export interface WorkingHour {
  start: string | null
  end: string | null
  is_off: boolean
}

export interface SettingsData {
  id?: number
  branch_id: number
  clinic_name: string
  address: string
  phone: string
  currency: string
  working_hours: Record<string, WorkingHour>
  wa_patient_reminder: string
  wa_patient_cancel: string
  wa_patient_complete: string
  wa_supplier_order: string
  wa_supplier_cancel: string
  prescription_template: {
    header: string
    footer: string
  }
  rec_can_edit_whatsapp: boolean
  rec_can_view_phones: boolean
  rec_show_kpi: boolean
  rec_show_suppliers: boolean
  rec_log_actions: boolean
  rec_can_void_transactions: boolean
  rec_can_edit_devices: boolean
  rec_can_contact_support: boolean
  doc_view_appointments: boolean
  doc_save_xrays: boolean
  doc_view_files: boolean
  doc_view_contact: boolean
  doc_edit_assets: boolean
  doc_issue_prescriptions: boolean
}
