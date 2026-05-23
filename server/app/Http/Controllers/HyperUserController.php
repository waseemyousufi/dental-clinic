<?php

namespace App\Http\Controllers;

use App\Http\Resources\BranchResource;
use Illuminate\Http\Request;
use App\Models\Branch; // Assuming this is your Branch model
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class HyperUserController extends Controller
{
    /**
     * Handle the Hyper-User Login
     * POST /api/admin/login
     */
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

    /**
     * Fetch your branch data securely via POST
     * POST /api/admin/fetch-branches-securely
     */
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
            $branches = Branch::all();

            return BranchResource::collection($branches);

        } catch (Exception $e) {
            // Catches expired tokens, modified strings, or fakes
            return response()->json(['error' => 'Token is expired or invalid.'], 401);
        }
    }
}
