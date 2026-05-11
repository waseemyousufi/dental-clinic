<?php
namespace App\Http\Controllers\Admin;

use App\Models\Setting;
use Illuminate\Http\Request;
use App\Http\Resources\SettingResource;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $branchId = $this->effectiveBranchId($request);
        if (!$branchId) {
            abort(403, 'User not assigned to a branch');
        }
        $setting = Setting::firstOrCreate(['branch_id' => $branchId]);
        return new SettingResource($setting);
    }

    public function update(Request $request, $branchId)
    {
        $effectiveBranchId = $this->effectiveBranchId($request);
        if (!$effectiveBranchId) {
            abort(403, 'User not assigned to a branch');
        }

        $validated = $request->validate([
            'clinic_name' => 'sometimes|string|max:255',
            'address' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:50',
            'currency' => 'sometimes|string',
            'working_hours' => 'sometimes|array',
            'wa_patient_reminder' => 'sometimes|string|nullable',
            'wa_patient_cancel' => 'sometimes|string|nullable',
            'wa_patient_complete' => 'sometimes|string|nullable',
            'wa_supplier_order' => 'sometimes|string|nullable',
            'wa_supplier_cancel' => 'sometimes|string|nullable',
            'rec_can_edit_whatsapp' => 'sometimes|boolean',
            'rec_can_view_phones' => 'sometimes|boolean',
            'rec_show_kpi' => 'sometimes|boolean',
            'rec_show_suppliers' => 'sometimes|boolean',
            'rec_log_actions' => 'sometimes|boolean',
            'rec_can_void_transactions' => 'sometimes|boolean',
            'rec_can_edit_devices' => 'sometimes|boolean',
            'doc_view_appointments' => 'sometimes|boolean',
            'doc_save_xrays' => 'sometimes|boolean',
            'doc_view_files' => 'sometimes|boolean',
            'doc_view_contact' => 'sometimes|boolean',
            'doc_edit_assets' => 'sometimes|boolean',
            'doc_issue_prescriptions' => 'sometimes|boolean',
            'prescription_template' => 'sometimes|array',
            'prescription_template.header' => 'nullable|string',
            'prescription_template.footer' => 'nullable|string',
        ]);

        $setting = Setting::updateOrCreate(['branch_id' => $effectiveBranchId], $validated);
        return new SettingResource($setting);
    }

    public function backupDatabase(Request $request)
    {
        // $this->authorize('admin');
        $type = $request->input('type', 'full'); // full or monthly
        $filename = "backup_{$type}_" . now()->format('Y_m_d_His') . ".sql";

        // In production, use: php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"
        Artisan::call('backup:run', ['--only-db' => true, '--filename' => $filename]);

        Log::info("Database backup triggered: {$type} by user {$request->user()->id}");
        return response()->json(['message' => 'Backup process initiated.', 'file' => $filename]);
    }

    protected function effectiveBranchId(Request $request): int
    {
        $branchId = $request->query('branchId')
            ?? $request->input('branch_id')
            ?? $request->route('branch')
            ?? $request->user()?->employee?->branch_id
            ?? $request->user()?->branch_id;

        if (is_numeric($branchId)) {
            return (int) $branchId;
        }

        abort(403, 'User not assigned to a branch');
    }
}
