<?php

namespace App\Http\Controllers;

use App\Http\Resources\BranchResource;
use Illuminate\Http\Request;
use App\Models\Branch; // Assuming this is your Branch model
use App\Models\ClinicOwner;
use App\Models\Employee;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class HyperUserController extends Controller
{

    public function login(Request $request)
    {
        // 1. Snag the password from your custom POST payload
        $password = $request->input('password');

        // 2. Direct check against your secure backend .env file
        if (!$password || $password !== env('HYPER_USER_PASSWORD')) {
            return response()->json(['error' => 'Nice try.'], 401);
        }

        // 3. Setup the self-contained token payload (No DB users needed!)
        $payload = [
            'iss' => url('/'),                      // Issuer (your current domain)
            'role' => 'hyper_user',                 // Your custom admin flag
            'iat' => time(),                        // Timestamp issued
            'exp' => time() + (60 * 60 * 24 * 7)    // Token expires automatically in 7 days
        ];

        // 4. Mathematically sign the token using your JWT secret
        $token = JWT::encode($payload, env('JWT_SECRET_KEY'), 'HS256');

        return response()->json([
            'message' => 'Welcome back, Hyper-User.',
            'token' => $token
        ]);
    }

    public function fetchBranches(Request $request)
    {
        // 1. Grab the stateless token from the HTTP Authorization header
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Missing token.'], 401);
        }

        try {
            // 2. Decode and verify the signature using core math
            $decoded = JWT::decode($token, new Key(env('JWT_SECRET_KEY'), 'HS256'));

            // 3. Double-check that the token actually belongs to the hyper-user
            if ($decoded->role !== 'hyper_user') {
                return response()->json(['error' => 'Unauthorized role.'], 403);
            }

            // 4. Success! If the math clears, pull your data from the database
            $branches = Branch::with('clinicOwner')->get();

            return BranchResource::collection($branches);
        } catch (Exception $e) {
            // Catches expired tokens, modified strings, or fakes
            return response()->json(['error' => 'Token is expired or invalid.'], 401);
        }
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'branchName' => 'required|string|max:20',
            'region' => 'required|string|max:100',
            'phone' => 'required|string|max:12',
            'ownerId' => 'required|integer|exists:clinic_owners,id',
        ]);

        $owner = ClinicOwner::find($validatedData['ownerId']);
        $ownerNameParts = $this->splitOwnerName($owner?->name);

        return DB::transaction(function () use ($validatedData, $ownerNameParts) {
            $branch = Branch::create([
                'branch_name' => $validatedData['branchName'],
                'region' => $validatedData['region'],
                'phone' => $validatedData['phone'],
                'clinic_owner_id' => $validatedData['ownerId'],
            ]);

            Setting::updateOrCreate(
                ['branch_id' => $branch->id],
                [
                    'branch_id' => $branch->id,
                    'clinic_name' => $validatedData['branchName'],
                    'address' => $validatedData['region'],
                    'phone' => $validatedData['phone'],
                ]
            );

            $branch_admin = User::where('clinic_owner_id', $validatedData['ownerId'])->first();

            if (!$branch_admin) {
                return response()->json(['error' => 'No user found for the selected clinic owner.'], 404);
            }

            Employee::create([
                'f_name' => $ownerNameParts['f_name'],
                'l_name' => $ownerNameParts['l_name'],
                'qualification' => 'Administrator',
                'speciality' => 'Management',
                'gender' => 'male',
                'medical_license_number' => 'ADM-001',
                'hire_date' => '2019/01/01',
                'work_start_time' => '09:00',
                'work_end_time' => '17:00',
                'position_id' => 4, // admin
                'branch_id' => $branch->id,
                'user_id' => $branch_admin->id,
            ]);

            return response()->json($branch, 201);
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $branch = Branch::findOrFail($id);
        return response()->json($branch);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $branch = Branch::findOrFail($id);

        $validatedData = $request->validate([
            'branchName' => 'sometimes|required|string|max:20',
            'region' => 'sometimes|required|string|max:100',
            'phone' => 'sometimes|required|string|max:12',
            'ownerId' => 'sometimes|required|integer|exists:clinic_owners,id',
            'email' => 'nullable|email|max:255',
        ]);

        $branch->update([
            'branch_name' => $validatedData['branchName'] ?? $branch->branch_name,
            'region' => $validatedData['region'] ?? $branch->region,
            'phone' => $validatedData['phone'] ?? $branch->phone,
            'clinic_owner_id' => $validatedData['ownerId'] ?? $branch->clinic_owner_id,
        ]);

        $branch->refresh();

        $branchUser = User::whereNotNull('clinic_owner_id')
            ->whereHas('employee', fn($query) => $query->where('branch_id', $branch->id))
            ->first();

        if ($branchUser) {
            $updateData = ['clinic_owner_id' => $validatedData['ownerId']];
            if (!empty($validatedData['email'])) {
                $updateData['email'] = $validatedData['email'];
            }
            $branchUser->update($updateData);
        }

        return response()->json($branch);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Branch::find($id)->delete();
        return response()->json(['message' => 'Branch deleted successfully']);
    }

    private function splitOwnerName(?string $ownerName): array
    {
        $parts = preg_split('/\s+/', trim((string) $ownerName), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return [
            'f_name' => $parts[0] ?? '',
            'l_name' => count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '',
        ];
    }
}
