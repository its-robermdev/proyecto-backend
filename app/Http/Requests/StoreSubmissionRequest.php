<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Event;
use App\Services\DynamicFormValidationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSubmissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
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

        // Laravel ya resolvio `{event}` por Route Model Binding; usarlo aqui mantiene
        // la regla de negocio cerca de la validacion y evita contaminar el controlador.
        if (! $event instanceof Event) {
            return $baseRules;
        }

        return array_merge(
            $baseRules,
            app(DynamicFormValidationService::class)->rulesForEvent($event),
        );
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('participation_type') !== 'team') {
                return;
            }

            $event = $this->route('event');

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
        });
    }
}
