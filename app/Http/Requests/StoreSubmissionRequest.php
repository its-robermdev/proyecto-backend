<?php

namespace App\Http\Requests;

use App\Models\Event;
use App\Rules\EventHasAvailableSpots;
use App\Services\DynamicFormValidationService;
use App\Services\EventFormService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class StoreSubmissionRequest extends FormRequest
{
    // Endpoint publico: la autorizacion por usuario no aplica en esta capa.
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $event = $this->route('event');

        if ($event instanceof Event) {
            $this->merge([
                'event_capacity_guard' => $event->id,
            ]);
        }
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        // Reglas base de inscripcion + unicidad por evento/correo.
        $baseRules = [
            'submitted_by_email' => ['required', 'email', 'max:255'],
            'submitted_by_name' => ['required', 'string', 'max:255'],
            'participation_type' => ['required', Rule::in(['individual', 'team'])],
            'team_name' => ['required_if:participation_type,team', 'nullable', 'string', 'max:255'],
            'members' => ['exclude_unless:participation_type,team', 'required_if:participation_type,team', 'array', 'min:1'],
            'members.*.full_name' => ['required_with:members', 'string', 'max:255'],
            'members.*.email' => ['required_with:members', 'email', 'max:255'],
            'members.*.is_captain' => ['required_with:members', 'boolean'],
            'form_answers' => ['required', 'array'],
        ];

        $event = $this->route('event');

        if (! $event instanceof Event) {
            return $baseRules;
        }

        $baseRules['event_capacity_guard'] = [
            'required',
            'integer',
            new EventHasAvailableSpots($event),
        ];

        $baseRules['submitted_by_email'][] = Rule::unique('submissions', 'submitted_by_email')
            ->where(fn ($query) => $query
                ->where('event_id', $event->id)
                ->whereNull('deleted_at'));

        return array_merge(
            $baseRules,
            app(DynamicFormValidationService::class)->rulesForEvent($event),
        );
    }

    public function withValidator(Validator $validator): void
    {
        // Reglas de negocio tardias: disponibilidad del evento y estructura de equipo.
        $validator->after(function (Validator $validator): void {
            $event = $this->route('event');

            if ($event instanceof Event) {
                try {
                    app(EventFormService::class)->ensureSubmissionEnabled($event);
                } catch (ValidationException $exception) {
                    foreach ($exception->errors() as $field => $messages) {
                        foreach ($messages as $message) {
                            $validator->errors()->add($field, $message);
                        }
                    }
                }
            }

            if ($this->input('participation_type') === 'team') {
                if ($event instanceof Event && ! $event->allows_teams) {
                    $validator->errors()->add('participation_type', 'This event does not allow team submissions.');
                }

                $members = $this->input('members', []);

                if (! is_array($members)) {
                    return;
                }

                $captains = collect($members)
                    ->filter(fn (mixed $member): bool => is_array($member) && filter_var($member['is_captain'] ?? false, FILTER_VALIDATE_BOOL))
                    ->count();

                if ($captains !== 1) {
                    $validator->errors()->add('members', 'Team submissions must include exactly one captain.');
                }
            }
        });
    }
}
