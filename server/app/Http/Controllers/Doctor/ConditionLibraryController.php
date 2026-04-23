<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\ConditionLibrary; // Ensure your model name matches
use Illuminate\Http\JsonResponse;

class ConditionLibraryController extends Controller
{
    /**
     * Display a listing of the condition library.
     * This feeds the frontend toolbar/legend.
     */
    public function index(): JsonResponse
    {
        // Fetch all conditions ordered by their ID or a custom sort order
        $conditions = ConditionLibrary::select([
            'id',
            'label',
            'slug',
            'ui_color',
            // 'description'
        ])->get();

        return response()->json([
            'success' => true,
            'data' => $conditions
        ]);
    }
}
