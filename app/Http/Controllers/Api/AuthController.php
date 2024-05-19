<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
        ]);

        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'token' => '',
            'token_expired' => now(),
        ]);

        // Generate token
        $token = $user->createToken('auth_token')->plainTextToken;
        $tokenExpired = Carbon::now()->addDays(7); // Token expired in 7 days

        $user->update([
            'token' => $token,
            'token_expired' => $tokenExpired,
        ]);

        return response()->json([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer'
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Generate token
        $token = $user->createToken('auth_token')->plainTextToken;
        $tokenExpired = Carbon::now()->addDays(7); // Token expired in 7 days

        $user->update([
            'token' => $token,
            'token_expired' => $tokenExpired,
        ]);

        return response()->json([
            'message' => 'Login success',
            'access_token' => $token,
            'token_type' => 'Bearer'
        ], 200);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();
        $user->update(['token' => '', 'token_expired' => now()]);

        return response()->json(['message' => 'Logged out successfully'], 200);
    }
}
