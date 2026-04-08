<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventStatusRequest extends FormRequest
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
        // Estados permitidos por contrato de API.
        return [
            'status' => ['required', Rule::in(['draft', 'published', 'closed', 'cancelled', 'archived'])],
        ];
    }
}
