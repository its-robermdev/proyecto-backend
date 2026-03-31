<?php

namespace App\Http\Controllers;

use App\Models\User; // <-- Importante: Añadir el modelo
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash; // <-- Importante: Añadir el Facade Hash

class AuthController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Email or password incorrect',
            ], 422);
        }

        $token = $user->createToken('token-name');

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'token' => $token->plainTextToken,
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        /** @var \App\Models\User */
        $user = Auth::user();
        $user->tokens()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout successful',
        ]);
    }

    public function profile(Request $request)
    {
        return response()->json([
            'profile' => Auth::user(),
        ]);
    }
}