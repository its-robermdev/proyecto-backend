<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    // La autorización fina se delega al controlador/policies.
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        // Reglas parciales con validación de email único excluyendo el usuario actual.
        $user = $this->route('user');
        $userId = $user instanceof User ? $user->id : (is_numeric($user) ? (int) $user : null);

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['sometimes', 'string', Password::min(8)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
