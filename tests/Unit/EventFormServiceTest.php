<?php

namespace Tests\Unit;

use App\Services\EventFormService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EventFormServiceTest extends TestCase
{
    public function test_update_schema_guarda_y_desactiva_formulario(): void
    {
        $event = $this->createPublishedEvent(['form_is_active' => true]);

        $updated = app(EventFormService::class)->updateSchema($event, $this->validFormSchema());

        $this->assertFalse($updated->form_is_active);
        $this->assertSame($this->validFormSchema(), $updated->form_schema);
    }

    public function test_validate_schema_falla_si_esta_vacio(): void
    {
        $errors = app(EventFormService::class)->validateSchema([]);

        $this->assertNotEmpty($errors);
    }

    public function test_validate_schema_detecta_validation_invalida(): void
    {
        $errors = app(EventFormService::class)->validateSchema([
            [
                'step_name' => 'Paso',
                'fields' => [
                    ['name' => 'field', 'label' => 'Campo', 'type' => 'text', 'validation' => 'regla_rara:??'],
                ],
            ],
        ]);

        $this->assertNotEmpty($errors);
    }

    public function test_validate_schema_exige_options_para_select_y_radio(): void
    {
        $errors = app(EventFormService::class)->validateSchema([
            [
                'step_name' => 'Paso',
                'fields' => [
                    ['name' => 'select_field', 'label' => 'Campo', 'type' => 'select', 'validation' => 'required'],
                    ['name' => 'radio_field', 'label' => 'Campo', 'type' => 'radio', 'validation' => 'required'],
                ],
            ],
        ]);

        $this->assertCount(2, $errors);
    }

    public function test_activate_activa_formulario_publicado_y_valido(): void
    {
        $event = $this->createPublishedEvent([
            'form_is_active' => false,
            'form_schema' => $this->validFormSchema(),
        ]);

        $activated = app(EventFormService::class)->activate($event);

        $this->assertTrue($activated->form_is_active);
    }

    public function test_activate_falla_si_evento_no_esta_publicado(): void
    {
        $this->expectException(ValidationException::class);

        $event = $this->createDraftEvent(['form_schema' => $this->validFormSchema()]);

        app(EventFormService::class)->activate($event);
    }

    public function test_ensure_submission_enabled_valida_estado_formulario_y_deadline(): void
    {
        $this->expectException(ValidationException::class);

        $event = $this->createPublishedEvent([
            'form_is_active' => false,
            'registration_deadline' => now()->subMinute(),
        ]);

        app(EventFormService::class)->ensureSubmissionEnabled($event);
    }
}
