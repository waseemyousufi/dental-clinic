<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use Illuminate\Support\Facades\Auth;

class BranchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();

        $branches = Branch::query()
            ->where('clinic_owner_id', $user?->clinic_owner_id)
            ->get();

        return BranchResource::collection($branches);
    }
}
