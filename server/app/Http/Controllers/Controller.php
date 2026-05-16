<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

abstract class Controller
{
    protected function effectiveBranchId(Request $request): int
{
    // 1. Grab raw input
    $raw = $request->query('branchId');

    // 2. Immediate log - if you don't see this, the method isn't being called
    // Log::info('EffectiveBranchId Debug', ['raw_query' => $raw]);


    // 3. Logic: URL first, User fallback second
    if (is_numeric($raw)) {
        return (int) $raw;
    }

    $employeeBranchId = $request->user()?->employee?->branch_id;
    if ($employeeBranchId) {
        return (int) $employeeBranchId;
    }

    // 4. Final Fallback
    throw ValidationException::withMessages([
        'branchId' => ['A branchId is required but was not found in the URL or your profile.'],
    ]);
}
}
