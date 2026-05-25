<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

abstract class Controller
{
    protected function effectiveBranchId(Request $request): int
    {
        $user = $request->user() ?? Auth::user();

        $requestedBranchId =
            $request->query('branchId')
            ?? $request->query('branch_id');

        // If branchId is explicitly requested in URL
        if (is_numeric($requestedBranchId)) {
            $branchId = (int) $requestedBranchId;

            // =========================
            // Clinic owner / admin flow
            // =========================
            if ($user?->clinic_owner_id) {
                $branch = Branch::query()
                    ->whereKey($branchId)
                    ->where('clinic_owner_id', $user->clinic_owner_id)
                    ->first();

                if (! $branch) {
                    throw ValidationException::withMessages([
                        'branchId' => ['You do not own the requested branch.'],
                    ]);
                }

                return $branch->id;
            }

            // =========================
            // Employee flow
            // (doctor/receptionist/assistant)
            // =========================
            $employeeBranchId = $user?->employee?->branch_id;

            if ($employeeBranchId && (int) $employeeBranchId === $branchId) {
                return $branchId;
            }

            throw ValidationException::withMessages([
                'branchId' => ['You are not assigned to this branch.'],
            ]);
        }

        // =========================
        // Fallback to employee branch
        // =========================
        $employeeBranchId = $user?->employee?->branch_id;

        if ($employeeBranchId) {
            return (int) $employeeBranchId;
        }

        // =========================
        // Fallback to clinic owner's first branch
        // =========================
        $ownedBranchId = Branch::query()
            ->where('clinic_owner_id', $user?->clinic_owner_id)
            ->value('id');

        if ($ownedBranchId) {
            return (int) $ownedBranchId;
        }

        throw ValidationException::withMessages([
            'branchId' => [
                'A branchId is required but was not found in the URL or your profile.'
            ],
        ]);
    }
    protected function branchOwnedByCurrentUser(int $branchId): bool
    {
        $user = Auth::user();

        return Branch::query()
            ->whereKey($branchId)
            ->where('clinic_owner_id', $user?->clinic_owner_id)
            ->exists();
    }

    protected function authorizeBranchAccess(int $branchId): void
    {
        if (! $this->branchOwnedByCurrentUser($branchId)) {
            throw ValidationException::withMessages([
                'branchId' => ['You do not own this branch.'],
            ]);
        }
    }
}
