<?php
namespace App\Http\Controllers\Admin;

use App\Models\Setting;
use Illuminate\Http\Request;
use App\Http\Resources\SettingResource;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Support\Facades\Process;

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
            'currency' => 'sometimes|string',
            'working_hours' => 'sometimes|array',
            'wa_patient_reminder' => 'sometimes|string|nullable',
            'wa_patient_cancel' => 'sometimes|string|nullable',
            'wa_patient_complete' => 'sometimes|string|nullable',
            'wa_supplier_order' => 'sometimes|string|nullable',
            'wa_supplier_cancel' => 'sometimes|string|nullable',
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

        $branch = Branch::find($effectiveBranchId);
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

    public function backupDatabase(Request $request)
 {
    // $this->authorize('admin');

    $dbConfig = config('database.connections.mysql');
    $filename = "backup_full_" . now()->format('Y_m_d_His') . ".sql";
    $tempPath = storage_path("app/temp/{$filename}");

    // Ensure the temp directory exists
    if (!file_exists(storage_path('app/temp'))) {
        mkdir(storage_path('app/temp'), 0755, true);
    }

    // Build the mysqldump command
    // --single-transaction is used to avoid locking tables during backup
    $command = sprintf(
        'mysqldump --user=%s --password=%s --host=%s --single-transaction %s > %s',
        escapeshellarg($dbConfig['username']),
        escapeshellarg($dbConfig['password']),
        escapeshellarg($dbConfig['host']),
        escapeshellarg($dbConfig['database']),
        escapeshellarg($tempPath)
    );

    $process = Process::run($command);

    if ($process->successful()) {
        Log::info("SQL backup downloaded by user {$request->user()->id}");

        // Return the file and delete it from the server after the download completes
        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }

    Log::error("Database backup failed: " . $process->errorOutput());

    return response()->json([
        'message' => 'Failed to generate backup.',
        'errorDetails' => $process->errorOutput()
    ], 500);
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
