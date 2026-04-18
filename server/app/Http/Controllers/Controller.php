<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

abstract class Controller
{
    protected function effectiveBranchId(Request $request): int
    {
        $employeeBranchId = $request->user()?->employee?->branch_id;
        if (is_int($employeeBranchId)) return $employeeBranchId;

        $raw = $request->query('branchId');
        $branchId = is_string($raw) ? (int) $raw : (is_int($raw) ? $raw : null);

        if (!$branchId) {
            throw ValidationException::withMessages([
                'branchId' => ['branchId query parameter is required for admin users.'],
            ]);
        }

        return $branchId;
    }
}
