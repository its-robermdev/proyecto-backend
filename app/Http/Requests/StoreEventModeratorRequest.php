<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventModeratorRequest extends FormRequest
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
        // Valida que el moderador exista antes de intentar asignarlo.
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
