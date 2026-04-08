<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewSubmissionRequest extends FormRequest
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
        // Solo se permiten decisiones finales de revisión.
        return [
            'status' => ['required', Rule::in(['approved', 'rejected'])],
            'review_comment' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
