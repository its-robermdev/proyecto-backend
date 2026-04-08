<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEventModeratorRequest extends FormRequest
{
    // Placeholder: request reservado para cambios adicionales de asignación.
    public function authorize(): bool
    {
        return false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Sin reglas porque el endpoint aún no está implementado.
        return [
            //
        ];
    }
}
