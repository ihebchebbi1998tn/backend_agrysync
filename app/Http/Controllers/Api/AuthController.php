<?php

/**
 * Authentication endpoints.
 *
 * Handles register / login / me / logout for the SPA and mobile clients.
 * Auth is token-based (Sanctum personal access tokens) — no cookies, no
 * CSRF dance. The plain-text token is returned once at login or register
 * and the client must send it back as `Authorization: Bearer <token>`.
 */

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

final class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:120'],
            'email'          => ['required', 'string', 'email:rfc', 'max:180', 'unique:users,email'],
            'password'       => ['required', 'confirmed', Password::min(8)],
            'preferred_lang' => ['nullable', 'in:fr,en'],
            'device_name'    => ['nullable', 'string', 'max:120'],
        ]);

        $user = User::create([
            'name'           => $data['name'],
            'email'          => strtolower($data['email']),
            'password'       => $data['password'],
            'preferred_lang' => $data['preferred_lang'] ?? 'fr',
            'is_active'      => true,
        ]);

        $token = $user->createToken($data['device_name'] ?? 'api-token')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'       => ['required', 'string', 'email:rfc'],
            'password'    => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $user = User::where('email', strtolower($data['email']))->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => __('auth.failed')], 401);
        }

        if ($user->is_active === false) {
            return response()->json(['message' => 'Account disabled.'], 403);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        $token = $user->createToken($data['device_name'] ?? 'api-token')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $request->user()]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out']);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()?->tokens()->delete();

        return response()->json(['message' => 'All sessions revoked']);
    }
}
