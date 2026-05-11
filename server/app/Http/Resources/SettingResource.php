<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'clinic_name' => $this->clinic_name,
            'address' => $this->address,
            'phone' => $this->phone,
            'currency' => $this->currency,
            'working_hours' => $this->working_hours,
            'wa_patient_reminder' => $this->wa_patient_reminder,
            'wa_patient_cancel' => $this->wa_patient_cancel,
            'wa_patient_complete' => $this->wa_patient_complete,
            'wa_supplier_order' => $this->wa_supplier_order,
            'wa_supplier_cancel' => $this->wa_supplier_cancel,
            'prescription_template' => $this->prescription_template,
            'rec_can_edit_whatsapp' => $this->rec_can_edit_whatsapp,
            'rec_can_view_phones' => $this->rec_can_view_phones,
            'rec_show_kpi' => $this->rec_show_kpi,
            'rec_show_suppliers' => $this->rec_show_suppliers,
            'rec_log_actions' => $this->rec_log_actions,
            'rec_can_void_transactions' => $this->rec_can_void_transactions,
            'rec_can_edit_devices' => $this->rec_can_edit_devices,
            'rec_can_contact_support' => $this->rec_can_contact_support,
            'doc_view_appointments' => $this->doc_view_appointments,
            'doc_save_xrays' => $this->doc_save_xrays,
            'doc_view_files' => $this->doc_view_files,
            'doc_view_contact' => $this->doc_view_contact,
            'doc_edit_assets' => $this->doc_edit_assets,
            'doc_issue_prescriptions' => $this->doc_issue_prescriptions,
        ];
    }
}
