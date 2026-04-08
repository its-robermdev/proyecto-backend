<?php

namespace App\Services;

use App\Models\Event;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class EventFormService
{
    /**
     * @param  array<int, mixed>  $schema
     */
    // Guarda schema y lo deja inactivo para exigir validacion/activacion explicita.
    public function updateSchema(Event $event, array $schema): Event
    {
        $event->update([
            'form_schema' => $schema,
            'form_is_active' => false,
        ]);

        return $event->refresh();
    }

    /**
     * @param  array<int, mixed>  $schema
     * @return array<int, string>
     */
    // Valida estructura del schema para evitar formularios incompletos o invalidos.
    public function validateSchema(array $schema): array
    {
        $errors = [];
        $fieldNames = [];

        if (! is_array($schema) || $schema === []) {
            return ['The form schema must be a non-empty array of steps.'];
        }

        foreach ($schema as $stepIndex => $step) {
            if (! is_array($step)) {
                $errors[] = sprintf('Step #%d must be an object.', $stepIndex + 1);

                continue;
            }

            $stepName = $step['step_name'] ?? null;
            $fields = $step['fields'] ?? null;

            if (! is_string($stepName) || trim($stepName) === '') {
                $errors[] = sprintf('Step #%d requires a non-empty "step_name".', $stepIndex + 1);
            }

            if (! is_array($fields) || $fields === []) {
                $errors[] = sprintf('Step #%d requires a non-empty "fields" array.', $stepIndex + 1);

                continue;
            }

            foreach ($fields as $fieldIndex => $field) {
                if (! is_array($field)) {
                    $errors[] = sprintf('Step #%d field #%d must be an object.', $stepIndex + 1, $fieldIndex + 1);

                    continue;
                }

                $name = $field['name'] ?? null;
                $label = $field['label'] ?? null;
                $validation = $field['validation'] ?? null;
                $type = $field['type'] ?? null;

                if (! is_string($name) || trim($name) === '') {
                    $errors[] = sprintf('Step #%d field #%d requires a non-empty "name".', $stepIndex + 1, $fieldIndex + 1);
                } elseif (in_array($name, $fieldNames, true)) {
                    $errors[] = sprintf('Field name "%s" is duplicated in schema.', $name);
                } else {
                    $fieldNames[] = $name;
                }

                if (! is_string($label) || trim($label) === '') {
                    $errors[] = sprintf('Step #%d field #%d requires a non-empty "label".', $stepIndex + 1, $fieldIndex + 1);
                }

                if (! is_string($type) || trim($type) === '') {
                    $errors[] = sprintf('Step #%d field #%d requires a non-empty "type".', $stepIndex + 1, $fieldIndex + 1);
                }

                if (! is_string($validation) || trim($validation) === '') {
                    $errors[] = sprintf('Step #%d field #%d requires a non-empty "validation".', $stepIndex + 1, $fieldIndex + 1);
                } else {
                    $this->validateRuleString($validation, $errors, $stepIndex, $fieldIndex);
                }

                if (($type === 'select' || $type === 'radio') && (! isset($field['options']) || ! is_array($field['options']) || $field['options'] === [])) {
                    $errors[] = sprintf('Step #%d field #%d requires non-empty "options" for type "%s".', $stepIndex + 1, $fieldIndex + 1, $type);
                }
            }
        }

        return $errors;
    }

    // Activa formulario solo si el evento esta publicado y el schema es valido.
    public function activate(Event $event): Event
    {
        if ($event->status !== 'published') {
            throw ValidationException::withMessages([
                'status' => 'Event must be published before activating the form.',
            ]);
        }

        $errors = $this->validateSchema($event->form_schema ?? []);

        if ($errors !== []) {
            throw ValidationException::withMessages([
                'form_schema' => $errors,
            ]);
        }

        $event->update(['form_is_active' => true]);

        return $event->refresh();
    }

    // Desactiva formulario sin perder configuracion.
    public function deactivate(Event $event): Event
    {
        $event->update(['form_is_active' => false]);

        return $event->refresh();
    }

    // Reglas de disponibilidad previas a crear una submission.
    public function ensureSubmissionEnabled(Event $event): void
    {
        $errors = [];

        if ($event->status !== 'published') {
            $errors['event'][] = 'This event is not published.';
        }

        if ($event->form_is_active !== true) {
            $errors['event'][] = 'The registration form is not active for this event.';
        }

        if ($event->registration_deadline !== null && now()->gt($event->registration_deadline)) {
            $errors['registration_deadline'][] = 'The registration deadline for this event has passed.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array<int, string>  $errors
     */
    // Verifica que un string de regla de Laravel sea interpretable.
    private function validateRuleString(string $rule, array &$errors, int $stepIndex, int $fieldIndex): void
    {
        try {
            Validator::make(
                ['field' => 'sample'],
                ['field' => $rule],
            )->passes();
        } catch (Throwable $throwable) {
            $errors[] = sprintf(
                'Step #%d field #%d has invalid validation rule "%s".',
                $stepIndex + 1,
                $fieldIndex + 1,
                $rule,
            );
        }
    }
}
