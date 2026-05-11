<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'branch_id' => $this->branch_id,
            'clinic_info' => [
                'name' => $this->clinic_name,
                'address' => $this->address,
                'phone' => $this->phone,
                'currency' => $this->currency,
            ],
            'working_hours' => $this->working_hours,
            'whatsapp_templates' => [
                'patient_reminder' => $this->wa_patient_reminder,
                'patient_cancel' => $this->wa_patient_cancel,
                'patient_complete' => $this->wa_patient_complete,
                'supplier_order' => $this->wa_supplier_order,
                'supplier_cancel' => $this->wa_supplier_cancel,
            ],
            'permissions' => [
                'receptionist' => [
                    'edit_whatsapp' => (bool) $this->rec_can_edit_whatsapp,
                    'view_phones' => (bool) $this->rec_can_view_phones,
                    'show_kpi' => (bool) $this->rec_show_kpi,
                    'show_suppliers' => (bool) $this->rec_show_suppliers,
                    'log_actions' => (bool) $this->rec_log_actions,
                    'void_transactions' => (bool) $this->rec_can_void_transactions,
                    'edit_devices' => (bool) $this->rec_can_edit_devices,
                    'contact_support' => (bool) $this->rec_can_contact_support,
                ],
                'doctor' => [
                    'view_appointments' => (bool) $this->doc_view_appointments,
                    'save_xrays' => (bool) $this->doc_save_xrays,
                    'view_files' => (bool) $this->doc_view_files,
                    'view_contact' => (bool) $this->doc_view_contact,
                    'edit_assets' => (bool) $this->doc_edit_assets,
                    'issue_prescriptions' => (bool) $this->doc_issue_prescriptions,
                ],
            ],
            'services' => [
                'items' => $this->clinic_items ?? [],
                'procedures' => $this->clinic_procedures ?? [],
            ],
            'prescription_layout' => $this->prescription_template ?? [],
        ];
    }
}
