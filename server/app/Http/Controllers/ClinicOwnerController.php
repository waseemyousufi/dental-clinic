<?php

namespace App\Http\Controllers;

use App\Http\Resources\ClinicOwnerResource;
use App\Models\ClinicOwner;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

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
            'totalAmountDue' => 'required|numeric|min:0',
            'totalAmountPaid' => 'required|numeric|min:0',
        ]);
        return DB::transaction(function () use ($data) {
            $emailProvided = !empty($data['email']);

            if ($emailProvided) {
                $email = trim($data['email']);
                $normalized = strtolower($email);

                // find existing user case-insensitively
                $user = User::whereRaw('LOWER(email) = ?', [$normalized])->first();

                // If a user exists and is already linked to a clinic owner, do NOT create a new clinic owner.
                if ($user && $user->clinic_owner_id) {
                    $clinicOwner = ClinicOwner::find($user->clinic_owner_id);

                    // If clinic owner exists, update it with provided data (keep in sync)
                    if ($clinicOwner) {
                        $clinicOwner->update([
                            'name' => $data['name'] ?? $clinicOwner->name,
                            'phone' => $data['phone'] ?? $clinicOwner->phone,
                            'email' => $data['email'] ?? $clinicOwner->email,
                            'total_amount_due' => $data['totalAmountDue'] ?? $clinicOwner->total_amount_due,
                            'total_amount_paid' => $data['totalAmountPaid'] ?? $clinicOwner->total_amount_paid,
                        ]);
                    } else {
                        // Defensive: referenced clinic owner missing, create a new one and attach
                        $clinicOwner = ClinicOwner::create([
                            'name' => $data['name'],
                            'phone' => $data['phone'],
                            'email' => $data['email'] ?? null,
                            'total_amount_due' => $data['totalAmountDue'],
                            'total_amount_paid' => $data['totalAmountPaid'],
                        ]);
                        $user->clinic_owner_id = $clinicOwner->id;
                        $user->save();
                    }

                    $token = Password::createToken($user);

                    return response(['token' => $token, 'email' => $user->email, 'clinicOwnerId' => $clinicOwner->id], 200);
                }
            }

            // No existing linked user found (or no email provided) — create a new clinic owner
            $clinicOwner = ClinicOwner::create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'total_amount_due' => $data['totalAmountDue'],
                'total_amount_paid' => $data['totalAmountPaid'],
            ]);

            if ($emailProvided) {
                $email = trim($data['email']);
                $normalized = strtolower($email);

                // If there's an existing user without a clinic_owner_id, attach them.
                $user = User::whereRaw('LOWER(email) = ?', [$normalized])->first();

                if ($user) {
                    $user->clinic_owner_id = $clinicOwner->id;
                    $user->name = $clinicOwner->name;
                    $user->save();
                } else {
                    // create new user and attach
                    $user = User::create([
                        'name' => $clinicOwner->name,
                        'email' => $email,
                        'password' => Hash::make('temp_pass'),
                        'clinic_owner_id' => $clinicOwner->id,
                    ]);
                }

                $token = Password::createToken($user);
                return response(['token' => $token, 'email' => $user->email, 'clinicOwnerId' => $clinicOwner->id], 201);
            }

            return (new ClinicOwnerResource($clinicOwner))->response()->setStatusCode(201);
        });
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
            'totalAmountDue' => 'sometimes|numeric|min:0',
            'totalAmountPaid' => 'sometimes|numeric|min:0',
        ]);

        $clinicOwner->update([
            'name' => $data['name'] ?? $clinicOwner->name,
            'phone' => $data['phone'] ?? $clinicOwner->phone,
            'email' => $data['email'] ?? $clinicOwner->email,
            'total_amount_due' => $data['totalAmountDue'] ?? $clinicOwner->total_amount_due,
            'total_amount_paid' => $data['totalAmountPaid'] ?? $clinicOwner->total_amount_paid,
        ]);

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
