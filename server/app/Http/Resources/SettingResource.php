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
            'position_id' => $this->position_id,
            'clinic_info' => [
                'name' => $this->clinic_name,
                'address' => $this->address,
                'phone' => $this->phone,
                'currency' => $this->currency,
                'working_hours' => $this->working_hours,
            ],
            'whatsapp_templates' => [
                'patient_reminder' => $this->wa_patient_reminder,
                'patient_cancel' => $this->wa_patient_cancel,
                'patient_complete' => $this->wa_patient_complete,
                'supplier_order' => $this->wa_supplier_order,
                'supplier_cancel' => $this->wa_supplier_cancel,
            ],
            'permissions' => [
                'receptionist' => [
                    'edit_whatsapp' => $this->rec_can_edit_whatsapp,
                    'view_phones' => $this->rec_can_view_phones,
                    'show_kpi' => $this->rec_show_kpi,
                    'show_suppliers' => $this->rec_show_suppliers,
                    'log_actions' => $this->rec_log_actions,
                    'can_void' => $this->rec_can_void_transactions,
                    'edit_devices' => $this->rec_can_edit_devices,
                ],
                'doctor' => [
                    'view_appointments' => $this->doc_view_appointments,
                    'view_contact' => $this->doc_view_contact,
                    'edit_assets' => $this->doc_edit_assets,
                    'issue_prescriptions' => $this->doc_issue_prescriptions,
                ]
            ]
        ];
    }
}
