<?php

namespace App\Http\Controllers;

use App\Http\Resources\ClinicOwnerResource;
use App\Models\ClinicOwner;
use Illuminate\Http\Request;

class ClinicOwnerController extends Controller
{
    /**
     * Display all clinic owners
     */
    public function index(Request $request)
    {
        $query = ClinicOwner::query();

        // Search by name or phone
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%');
        }

        $clinicOwners = $query->get();
        return ClinicOwnerResource::collection($clinicOwners);
    }

    /**
     * Store a newly created clinic owner
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'total_amount_due' => 'required|numeric|min:0',
            'total_amount_paid' => 'required|numeric|min:0',
        ]);

        $clinicOwner = ClinicOwner::create($data);

        return new ClinicOwnerResource($clinicOwner);
    }

    /**
     * Display the specified clinic owner
     */
    public function show($id)
    {
        $clinicOwner = ClinicOwner::with('branches')->findOrFail($id);
        return new ClinicOwnerResource($clinicOwner);
    }

    /**
     * Update the specified clinic owner
     */
    public function update(Request $request, $id)
    {
        $clinicOwner = ClinicOwner::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'email' => 'nullable|email|max:255',
            'total_amount_due' => 'sometimes|numeric|min:0',
            'total_amount_paid' => 'sometimes|numeric|min:0',
        ]);

        $clinicOwner->update($data);

        return new ClinicOwnerResource($clinicOwner);
    }

    /**
     * Delete the specified clinic owner
     */
    public function destroy($id)
    {
        $clinicOwner = ClinicOwner::findOrFail($id);
        $clinicOwner->delete();

        return response()->json(['message' => 'Clinic Owner deleted successfully'], 200);
    }
}
