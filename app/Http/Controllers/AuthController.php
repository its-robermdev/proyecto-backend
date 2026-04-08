<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Autentica al usuario y devuelve un token de API.
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user instanceof User || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Email or password incorrect.',
                'data' => null,
                'status' => 422,
            ], 422);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => 'This user account is inactive.',
                'data' => null,
                'status' => 403,
            ], 403);
        }

        $token = $user->createToken('api-token');

        return response()->json([
            'message' => 'Login successful.',
            'data' => [
                'token' => $token->plainTextToken,
                'user' => new UserResource($user),
            ],
            'status' => 200,
        ], 200);
    }

    // Revoca todos los tokens activos del usuario autenticado.
    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Logout successful.',
            'data' => new UserResource($user),
            'status' => 200,
        ], 200);
    }

    // Retorna los datos del usuario autenticado.
    public function profile(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Profile retrieved successfully.',
            'data' => new UserResource($request->user()),
            'status' => 200,
        ], 200);
    }
}
