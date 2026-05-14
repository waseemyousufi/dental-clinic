<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;
use App\Models\Branch;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $branches = Branch::all();

        $defaultHours = collect(['monday','tuesday','wednesday','thursday','friday'])->mapWithKeys(fn($d) => [$d => ['start'=>'09:00','end'=>'17:00','is_off'=>false]])->toArray();
        $weekend = collect(['saturday','sunday'])->mapWithKeys(fn($d) => [$d => ['start'=>null,'end'=>null,'is_off'=>true]])->toArray();

        $defaults = [
            'working_hours' => array_merge($defaultHours, $weekend),
            'wa_patient_reminder' => "Hi {{patient_name}}, reminder for your appointment on {{date}} at {{time}}. Reply C to cancel.",
            'wa_patient_cancel' => "Your appointment on {{date}} at {{time}} has been cancelled. Contact us to reschedule.",
            'wa_patient_complete' => "Thank you {{patient_name}}! Your appointment today is complete. See you soon.",
            'wa_supplier_order' => "New order placed: {{order_id}}. Total: {{currency}} {{amount}}. Please confirm delivery.",
            'wa_supplier_cancel' => "Order {{order_id}} has been cancelled. Please disregard.",
            'reception_cost' => 0,
            'rec_can_view_phones' => true,
            'doc_view_appointments' => true,
            'doc_issue_prescriptions' => true,
            'prescription_template' => ['header' => "{{clinic_name}}\n{{address}}\n{{phone}}", 'footer' => "Thank you for choosing us."],
            'clinic_items' => [],
            'clinic_procedures' => []
        ];

        foreach ($branches as $branch) {
            Setting::updateOrCreate(['branch_id' => $branch->id], $defaults);
        }
    }
}
