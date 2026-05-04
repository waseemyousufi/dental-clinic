<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'branch_id', 'clinic_name', 'address', 'phone', 'currency', 'working_hours',
        'wa_patient_reminder', 'wa_patient_cancel', 'wa_patient_complete',
        'wa_supplier_order', 'wa_supplier_cancel',
        'rec_can_edit_whatsapp', 'rec_can_view_phones', 'rec_show_kpi',
        'rec_show_suppliers', 'rec_log_actions', 'rec_can_void_transactions', 'rec_can_edit_devices',
        'doc_view_appointments', 'doc_save_xrays', 'doc_view_files',
        'doc_view_contact', 'doc_edit_assets', 'doc_issue_prescriptions'
    ];

    protected $casts = [
        'working_hours' => 'array',
        'rec_can_edit_whatsapp' => 'boolean',
        'rec_can_view_phones' => 'boolean',
        'rec_show_kpi' => 'boolean',
        'rec_show_suppliers' => 'boolean',
        'rec_log_actions' => 'boolean',
        'rec_can_void_transactions' => 'boolean',
        'rec_can_edit_devices' => 'boolean',
        'doc_view_appointments' => 'boolean',
        'doc_save_xrays' => 'boolean',
        'doc_view_files' => 'boolean',
        'doc_view_contact' => 'boolean',
        'doc_edit_assets' => 'boolean',
        'doc_issue_prescriptions' => 'boolean',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
