<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Event;

class DynamicFormValidationService
{
    /**
     * Convierte el form_schema del evento en reglas Laravel para form_answers.
     *
     * @return array<string, string>
     */
    public function rulesForEvent(Event $event): array
    {
        $rules = [];

        // El schema del formulario pertenece al evento, no al controlador.
        // Centralizar este parseo aqui permite reutilizarlo y defender que la API
        // valida formularios dinamicos sin acoplar la capa HTTP a la estructura JSON.
        foreach ($event->form_schema ?? [] as $step) {
            if (! is_array($step) || ! isset($step['fields']) || ! is_array($step['fields'])) {
                continue;
            }

            foreach ($step['fields'] as $field) {
                if (! is_array($field)) {
                    continue;
                }

                $name = $field['name'] ?? null;
                $validation = $field['validation'] ?? null;

                if (! is_string($name) || $name === '' || ! is_string($validation) || $validation === '') {
                    continue;
                }

                $rules["form_answers.{$name}"] = $validation;
            }
        }

        return $rules;
    }
}
