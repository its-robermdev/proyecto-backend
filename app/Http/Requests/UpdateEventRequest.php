<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateEventRequest extends FormRequest
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
        // Reglas parciales para update con ignore de slug actual.
        $event = $this->route('event');
        $eventId = $event instanceof Event ? $event->id : null;

        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('events', 'slug')->ignore($eventId)],
            'type' => ['sometimes', Rule::in(['hackathon', 'bootcamp', 'workshop', 'conference', 'job_fair', 'other'])],
            'modality' => ['sometimes', Rule::in(['online', 'in-person', 'hybrid'])],
            'description' => ['sometimes', 'string'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['nullable', 'date'],
            'registration_deadline' => ['sometimes', 'date'],
            'capacity' => ['sometimes', 'integer', 'min:1'],
            'requires_approval' => ['sometimes', 'boolean'],
            'allows_teams' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        // Valida relaciones temporales aún cuando start_date no venga en payload.
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $event = $this->route('event');
            $referenceStart = $this->input('start_date');

            if ($referenceStart === null && $event instanceof Event && $event->start_date !== null) {
                $referenceStart = $event->start_date->toISOString();
            }

            if ($referenceStart === null) {
                return;
            }

            $startDate = Carbon::parse($referenceStart);

            if ($this->filled('end_date') && Carbon::parse((string) $this->input('end_date'))->lt($startDate)) {
                $validator->errors()->add('end_date', 'End date must be after or equal to start date.');
            }

            if ($this->filled('registration_deadline') && Carbon::parse((string) $this->input('registration_deadline'))->gt($startDate)) {
                $validator->errors()->add('registration_deadline', 'Registration deadline must be before or equal to start date.');
            }
        });
    }
}
