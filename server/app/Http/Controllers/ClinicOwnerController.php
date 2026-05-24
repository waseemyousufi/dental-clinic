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
            'total-amount-due' => 'required|numeric|min:0',
            'total-amount-paid' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($data) {
            $clinicOwner = ClinicOwner::create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'total_amount_due' => $data['total-amount-due'],
                'total_amount_paid' => $data['total-amount-paid'],
            ]);

            // If an email was provided, create (or attach) a User and return a password reset token
            if (!empty($data['email'])) {
                $email = $data['email'];

                $user = User::where('email', $email)->first();

                if (!$user) {
                    $user = User::create([
                        'name' => $clinicOwner->name,
                        'email' => $email,
                        'password' => Hash::make('temp_pass'),
                        'clinic_owner_id' => $clinicOwner->id,
                    ]);
                } else {
                    // attach clinic_owner_id if missing
                    if ($user->clinic_owner_id !== $clinicOwner->id) {
                        $user->clinic_owner_id = $clinicOwner->id;
                        $user->save();
                    }
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
            'total-amount-due' => 'sometimes|numeric|min:0',
            'total-amount-paid' => 'sometimes|numeric|min:0',
        ]);

        $clinicOwner->update([
            'name' => $data['name'] ?? $clinicOwner->name,
            'phone' => $data['phone'] ?? $clinicOwner->phone,
            'email' => $data['email'] ?? $clinicOwner->email,
            'total_amount_due' => $data['total-amount-due'] ?? $clinicOwner->total_amount_due,
            'total_amount_paid' => $data['total-amount-paid'] ?? $clinicOwner->total_amount_paid,
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
