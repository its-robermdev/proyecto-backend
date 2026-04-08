<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSubmissionMemberRequest extends FormRequest
{
    // Placeholder: request reservado para creación manual de miembros.
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
