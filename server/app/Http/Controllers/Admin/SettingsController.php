<?php

namespace App\Http\Controllers\Admin;

use App\Models\Setting;
use Illuminate\Http\Request;
use App\Http\Resources\SettingResource;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use Symfony\Component\Process\Process;

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

    public function update(Request $request, $branchId = null)
    {
        $effectiveBranchId = $this->effectiveBranchId($request);
        if (!$effectiveBranchId) {
            abort(403, 'User not assigned to a branch');
        }

        $validated = $request->validate([
            'clinic_name' => 'sometimes|string|max:255',
            'address' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:50',
            'currency' => 'string|nullable',
            'working_hours' => 'sometimes|array',
            'wa_patient_reminder' => 'sometimes|string|nullable',
            'wa_patient_cancel' => 'sometimes|string|nullable',
            'wa_patient_complete' => 'sometimes|string|nullable',
            'wa_supplier_order' => 'sometimes|string|nullable',
            'wa_supplier_cancel' => 'sometimes|string|nullable',
            'reception_cost' => 'sometimes|numeric|min:0',
            'rec_can_edit_whatsapp' => 'sometimes|nullable|boolean',
            'rec_can_view_phones' => 'sometimes|nullable|boolean',
            'rec_show_kpi' => 'sometimes|nullable|boolean',
            'rec_show_suppliers' => 'sometimes|nullable|boolean',
            'rec_log_actions' => 'sometimes|nullable|boolean',
            'rec_can_void_transactions' => 'sometimes|nullable|boolean',
            'rec_can_edit_devices' => 'sometimes|nullable|boolean',
            'rec_can_contact_support' => 'sometimes|nullable|boolean',
            'doc_view_appointments' => 'sometimes|nullable|boolean',
            'doc_save_xrays' => 'sometimes|nullable|boolean',
            'doc_view_files' => 'sometimes|nullable|boolean',
            'doc_view_contact' => 'sometimes|nullable|boolean',
            'doc_edit_assets' => 'sometimes|nullable|boolean',
            'doc_issue_prescriptions' => 'sometimes|nullable|boolean',
            'prescription_template' => 'sometimes|array',
            'prescription_template.header' => 'nullable|string',
            'prescription_template.footer' => 'nullable|string',
        ]);

        $branch = Branch::where('clinic_owner_id', auth()->user()?->clinic_owner_id)
            ->find($effectiveBranchId);
        if (!$branch) {
            abort(404, 'Branch not found');
        } else {
            $branch->update([
                'branch_name' => $validated['clinic_name'] ?? $branch->branch_name,
                'region' => $validated['address'] ?? $branch->address,
                'phone' => $validated['phone'] ?? $branch->phone,
            ]);

            $branch->save();
        }

        $setting = Setting::updateOrCreate(['branch_id' => $effectiveBranchId], $validated);
        return new SettingResource($setting);
    }



    public function backupDatabase()
    {
        $db = config('database.connections.mysql');

        $filename = 'backup_' . now()->format('Y_m_d_His') . '.sql';
        $path = storage_path("app/temp/$filename");

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        $mysqldump = config('backup.mysqldump');
        $command = "\"{$mysqldump}\" -u root -h 127.0.0.1 {$db['database']} > \"{$path}\" 2>&1";
        $output = [];
        $code = 0;

        exec($command, $output, $code);

        if ($code !== 0 || !file_exists($path)) {
            return response()->json([
                'success' => false,
                'error' => $output,
                'exit_code' => $code,
            ], 500);
        }

        return response()->download($path)->deleteFileAfterSend(true);
    }

    // protected function effectiveBranchId(Request $request): int
    // {
    //     $branchId = $request->query('branchId')
    //         ?? $request->input('branch_id')
    //         ?? $request->route('branch')
    //         ?? $request->user()?->employee?->branch_id
    //         ?? $request->user()?->branch_id;

    //     if (is_numeric($branchId)) {
    //         return (int) $branchId;
    //     }

    //     abort(403, 'User not assigned to a branch');
    // }
}
