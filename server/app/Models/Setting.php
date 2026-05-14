<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    protected $fillable = [
        'branch_id', 'clinic_name', 'address', 'phone', 'currency', 'working_hours',
        'wa_patient_reminder', 'wa_patient_cancel', 'wa_patient_complete',
        'wa_supplier_order', 'wa_supplier_cancel', 'reception_cost',
        'rec_can_edit_whatsapp', 'rec_can_view_phones', 'rec_show_kpi',
        'rec_show_suppliers', 'rec_log_actions', 'rec_can_void_transactions', 'rec_can_edit_devices', 'rec_can_contact_support',
        'doc_view_appointments', 'doc_save_xrays', 'doc_view_files',
        'doc_view_contact', 'doc_edit_assets', 'doc_issue_prescriptions',
        'clinic_items', 'clinic_procedures', 'prescription_template'
    ];

    protected $casts = [
        'working_hours' => 'array',
        'clinic_items' => 'array',
        'clinic_procedures' => 'array',
        'prescription_template' => 'array',
        'reception_cost' => 'float',
        'rec_can_edit_whatsapp' => 'boolean', 'rec_can_view_phones' => 'boolean',
        'rec_show_kpi' => 'boolean', 'rec_show_suppliers' => 'boolean',
        'rec_log_actions' => 'boolean', 'rec_can_void_transactions' => 'boolean',
        'rec_can_edit_devices' => 'boolean', 'rec_can_contact_support' => 'boolean',
        'doc_view_appointments' => 'boolean', 'doc_save_xrays' => 'boolean',
        'doc_view_files' => 'boolean', 'doc_view_contact' => 'boolean',
        'doc_edit_assets' => 'boolean', 'doc_issue_prescriptions' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Parse WhatsApp templates with dynamic variables
     * Usage: Setting::parseTemplate('wa_patient_reminder', ['patient_name' => 'John', 'date' => '2024-01-01'])
     */
    public static function parseTemplate(string $templateKey, array $variables, ?int $branchId = null): string
    {
        $branchId ??= auth()->user()?->branch_id;
        $setting = $branchId ? self::where('branch_id', $branchId)->first() : null;

        if (!$setting || empty($setting->$templateKey)) return '';

        $template = $setting->$templateKey;
        foreach ($variables as $key => $value) {
            $template = str_replace("{{{$key}}}", (string) $value, $template);
        }
        return $template;
    }
}
