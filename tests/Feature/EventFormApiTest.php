<?php

namespace Tests\Feature;

use Tests\TestCase;

class EventFormApiTest extends TestCase
{
    public function test_formulario_publico_activo_se_puede_consultar(): void
    {
        $event = $this->createPublishedEvent([
            'form_is_active' => true,
            'form_schema' => $this->validFormSchema(),
        ]);

        $this->getJson("/api/v1/events/{$event->id}/form")
            ->assertOk()
            ->assertJsonPath('data.event_id', $event->id)
            ->assertJsonPath('data.form_is_active', true);
    }

    public function test_formulario_no_activo_responde_404_para_invitado(): void
    {
        $event = $this->createPublishedEvent(['form_is_active' => false]);

        $this->getJson("/api/v1/events/{$event->id}/form")
            ->assertNotFound()
            ->assertJsonPath('message', 'Form is not available for this event.');
    }

    public function test_moderador_asignado_puede_ver_formulario_no_publico(): void
    {
        $moderator = $this->createModerator();
        $event = $this->createDraftEvent([
            'form_is_active' => false,
            'form_schema' => $this->validFormSchema(),
        ]);
        $this->assignModerator($event, $moderator);
        $this->actingAsApi($moderator);

        $this->getJson("/api/v1/events/{$event->id}/form")
            ->assertOk()
            ->assertJsonPath('data.event_id', $event->id);
    }

    public function test_actualizar_schema_desactiva_formulario(): void
    {
        $admin = $this->createAdmin();
        $event = $this->createPublishedEvent(['form_is_active' => true]);
        $this->actingAsApi($admin);

        $this->putJson("/api/v1/events/{$event->id}/form", [
            'form_schema' => $this->validFormSchema(),
        ])->assertOk()
            ->assertJsonPath('data.form_is_active', false);
    }

    public function test_actualizar_schema_falla_sin_permiso(): void
    {
        $user = $this->createUser();
        $event = $this->createPublishedEvent();
        $this->actingAsApi($user);

        $this->putJson("/api/v1/events/{$event->id}/form", [
            'form_schema' => $this->validFormSchema(),
        ])->assertForbidden();
    }

    public function test_validar_schema_correcto_devuelve_is_valid_true(): void
    {
        $admin = $this->createAdmin();
        $event = $this->createDraftEvent(['form_schema' => $this->validFormSchema()]);
        $this->actingAsApi($admin);

        $this->postJson("/api/v1/events/{$event->id}/form/validation")
            ->assertOk()
            ->assertJsonPath('data.is_valid', true)
            ->assertJsonPath('data.errors', []);
    }

    public function test_validar_schema_incorrecto_devuelve_errores(): void
    {
        $admin = $this->createAdmin();
        $event = $this->createDraftEvent([
            'form_schema' => [
                ['step_name' => '', 'fields' => [['name' => 'duplicate', 'label' => '', 'type' => '', 'validation' => 'bad_rule']]],
                ['step_name' => 'Paso 2', 'fields' => [['name' => 'duplicate', 'label' => 'Otra', 'type' => 'select', 'validation' => 'required', 'options' => []]]],
            ],
        ]);
        $this->actingAsApi($admin);

        $response = $this->postJson("/api/v1/events/{$event->id}/form/validation");

        $response->assertOk()
            ->assertJsonPath('data.is_valid', false);

        $this->assertNotEmpty($response->json('data.errors'));
    }

    public function test_activar_formulario_publicado_y_valido(): void
    {
        $admin = $this->createAdmin();
        $event = $this->createPublishedEvent([
            'form_is_active' => false,
            'form_schema' => $this->validFormSchema(),
        ]);
        $this->actingAsApi($admin);

        $this->putJson("/api/v1/events/{$event->id}/form/activation")
            ->assertOk()
            ->assertJsonPath('data.form_is_active', true);
    }

    public function test_activar_formulario_falla_si_evento_no_esta_publicado(): void
    {
        $admin = $this->createAdmin();
        $event = $this->createDraftEvent(['form_schema' => $this->validFormSchema()]);
        $this->actingAsApi($admin);

        $this->putJson("/api/v1/events/{$event->id}/form/activation")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    public function test_activar_formulario_falla_si_schema_no_es_valido(): void
    {
        $admin = $this->createAdmin();
        $event = $this->createPublishedEvent([
            'form_is_active' => false,
            'form_schema' => [],
        ]);
        $this->actingAsApi($admin);

        $this->putJson("/api/v1/events/{$event->id}/form/activation")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['form_schema']);
    }

    public function test_desactivar_formulario_funciona(): void
    {
        $admin = $this->createAdmin();
        $event = $this->createPublishedEvent(['form_is_active' => true]);
        $this->actingAsApi($admin);

        $this->deleteJson("/api/v1/events/{$event->id}/form/activation")
            ->assertOk()
            ->assertJsonPath('data.form_is_active', false);
    }
}
