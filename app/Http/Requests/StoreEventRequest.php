<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
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
        // Reglas mínimas para crear un evento válido en draft.
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('events', 'slug')],
            'type' => ['required', Rule::in(['hackathon', 'bootcamp', 'workshop', 'conference', 'job_fair', 'other'])],
            'modality' => ['required', Rule::in(['online', 'in-person', 'hybrid'])],
            'description' => ['required', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'registration_deadline' => ['required', 'date', 'before_or_equal:start_date'],
            'capacity' => ['required', 'integer', 'min:1'],
            'requires_approval' => ['sometimes', 'boolean'],
            'allows_teams' => ['sometimes', 'boolean'],
        ];
    }
}
