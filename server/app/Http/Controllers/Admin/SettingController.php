<?php

namespace App\Http\Controllers\Admin;

use App\Models\Setting;
use Illuminate\Http\Request;
use App\Http\Resources\SettingResource;
use App\Http\Controllers\Controller;

class SettingsController extends Controller
{
    // Fetches settings for the current branch
    public function index(Request $request)
    {
        $branchId = $this->effectiveBranchId($request); // Using your custom method
        $settings = Setting::where('branch_id', $branchId)->firstOrFail();

        return new SettingResource($settings);
    }

    // Updates or initializes settings for the branch
    public function store(Request $request)
    {
        $branchId = $this->effectiveBranchId($request);

        $settings = Setting::updateOrCreate(
            ['branch_id' => $branchId],
            $request->all()
        );

        return response()->json([
            'message' => 'Settings updated successfully',
            'data' => new SettingResource($settings)
        ]);
    }
}
