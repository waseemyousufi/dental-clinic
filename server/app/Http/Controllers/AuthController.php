<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    // public function register(Request $request)
    // {
    //     $fields = $request->validate([
    //         'name' => 'required|string',
    //         'email' => 'required|string|unique:users,email',
    //     ]);

    //     return DB::transaction(function () use ($fields) {
    //         $user = User::create([
    //             'name' => $fields['name'],
    //             'email' => $fields['email'],
    //             'password' => Hash::make($fields['password'])
    //         ]);

    //         $user->employee()->create([
    //             'department' => $fields['department'],
    //         ]);

    //         $token = $user->createToken('clinic-token')->plainTextToken;

    //         return response(['user' => $user->load('employee'), 'token' => $token], 201);
    //     });
    // }

    public function login(Request $request)
    {
        $fields = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string'
        ]);

        $user = User::where('email', $fields['email'])->first();

        if (!$user || !Hash::check($fields['password'], $user->password)) {
            return response(['message' => 'Bad credentials'], 401);
        }

        $token = $user->createToken('clinic-token')->plainTextToken;

        return response(['user' => $user->load(['employee.Position']), 'token' => $token], 200);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Password has been set successfully. You can now log in.']);
        }

        return response()->json(['message' => __($status)], 400);
    }

    // public function logout(Request $request) {
    //     $request->user()->currentAccessToken()->delete();
    //     return response(['message' => 'Logged out'], 200);
    // }
}
