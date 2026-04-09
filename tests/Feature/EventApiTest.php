<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Tests\TestCase;

class EventApiTest extends TestCase
{
    public function test_listado_publico_solo_devuelve_eventos_publicados(): void
    {
        $published = $this->createPublishedEvent(['title' => 'Publicado']);
        $this->createDraftEvent(['title' => 'Borrador']);

        $response = $this->getJson('/api/v1/events');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $published->id);
    }

    public function test_admin_con_permiso_global_ve_todos_los_eventos(): void
    {
        $admin = $this->createAdmin();
        $published = $this->createPublishedEvent();
        $draft = $this->createDraftEvent();

        $this->actingAsApi($admin);

        $this->getJson('/api/v1/events')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $published->id])
            ->assertJsonFragment(['id' => $draft->id]);
    }

    public function test_moderador_en_listado_solo_ve_eventos_asignados(): void
    {
        $moderator = $this->createModerator();
        $published = $this->createPublishedEvent();
        $assignedDraft = $this->createDraftEvent(['title' => 'Asignado']);
        $hiddenDraft = $this->createDraftEvent(['title' => 'Oculto']);
        $this->assignModerator($assignedDraft, $moderator);

        $this->actingAsApi($moderator);

        $this->getJson('/api/v1/events')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $assignedDraft->id])
            ->assertJsonMissing(['id' => $published->id])
            ->assertJsonMissing(['id' => $hiddenDraft->id]);
    }

    public function test_detalle_publico_de_evento_publicado(): void
    {
        $event = $this->createPublishedEvent(['title' => 'Evento Visible']);

        $this->getJson("/api/v1/events/{$event->id}")
            ->assertOk()
            ->assertJsonPath('data.title', 'Evento Visible');
    }

    public function test_detalle_de_evento_no_publicado_responde_404_para_invitado(): void
    {
        $event = $this->createDraftEvent();

        $this->getJson("/api/v1/events/{$event->id}")
            ->assertNotFound()
            ->assertJsonPath('message', 'Event not found.');
    }

    public function test_moderador_asignado_puede_ver_evento_no_publicado(): void
    {
        $moderator = $this->createModerator();
        $event = $this->createDraftEvent();
        $this->assignModerator($event, $moderator);

        $this->actingAsApi($moderator);

        $this->getJson("/api/v1/events/{$event->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $event->id);
    }

    public function test_crear_evento_exitosamente_lo_deja_en_draft(): void
    {
        $admin = $this->createAdmin();
        $this->actingAsApi($admin);

        $payload = $this->eventPayload();

        $response = $this->postJson('/api/v1/events', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.form_is_active', false)
            ->assertJsonPath('data.created_by', $admin->name);

        $this->assertDatabaseHas('events', [
            'slug' => $payload['slug'],
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);
    }

    public function test_crear_evento_requiere_autenticacion(): void
    {
        $this->postJson('/api/v1/events', $this->eventPayload())
            ->assertUnauthorized();
    }

    public function test_crear_evento_falla_sin_permiso(): void
    {
        $user = $this->createUser();
        $this->actingAsApi($user);

        $this->postJson('/api/v1/events', $this->eventPayload())
            ->assertForbidden();
    }

    public function test_crear_evento_valida_slug_y_fechas(): void
    {
        $admin = $this->createAdmin();
        $existing = $this->createPublishedEvent(['slug' => 'evento-duplicado']);
        $this->actingAsApi($admin);

        $payload = $this->eventPayload([
            'slug' => $existing->slug,
            'registration_deadline' => now()->addDays(20)->toISOString(),
            'start_date' => now()->addDays(10)->toISOString(),
        ]);

        $this->postJson('/api/v1/events', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['slug', 'registration_deadline']);
    }

    public function test_actualizar_evento_con_permiso(): void
    {
        $admin = $this->createAdmin();
        $event = $this->createDraftEvent([
            'title' => 'Antes',
            'start_date' => now()->addDays(12),
        ]);
        $this->actingAsApi($admin);

        $this->patchJson("/api/v1/events/{$event->id}", [
            'title' => 'Despues',
            'end_date' => now()->addDays(13)->toISOString(),
        ])->assertOk()
            ->assertJsonPath('data.title', 'Despues');
    }

    public function test_actualizar_evento_falla_sin_permiso(): void
    {
        $event = $this->createDraftEvent();
        $user = $this->createUser();
        $this->actingAsApi($user);

        $this->patchJson("/api/v1/events/{$event->id}", ['title' => 'No'])
            ->assertForbidden();
    }

    public function test_actualizar_evento_valida_end_date_y_registration_deadline(): void
    {
        $admin = $this->createAdmin();
        $event = $this->createDraftEvent(['start_date' => now()->addDays(12)]);
        $this->actingAsApi($admin);

        $this->patchJson("/api/v1/events/{$event->id}", [
            'end_date' => $event->start_date->copy()->subDay()->toISOString(),
            'registration_deadline' => $event->start_date->copy()->addDay()->toISOString(),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['end_date', 'registration_deadline']);
    }

    public function test_eliminar_evento_hace_soft_delete(): void
    {
        $admin = $this->createAdmin();
        $event = $this->createDraftEvent();
        $this->actingAsApi($admin);

        $this->deleteJson("/api/v1/events/{$event->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Event deleted successfully.');

        $this->assertSoftDeleted(Event::class, ['id' => $event->id]);
    }

    public function test_actualizar_estado_del_evento_publica_si_cumple_reglas(): void
    {
        $admin = $this->createAdmin();
        $event = $this->createDraftEvent();
        $this->actingAsApi($admin);

        $this->putJson("/api/v1/events/{$event->id}/status", [
            'status' => 'published',
        ])->assertOk()
            ->assertJsonPath('data.status', 'published');
    }

    public function test_actualizar_estado_falla_en_transicion_invalida(): void
    {
        $admin = $this->createAdmin();
        $event = $this->createDraftEvent();
        $this->actingAsApi($admin);

        $this->putJson("/api/v1/events/{$event->id}/status", [
            'status' => 'closed',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    public function test_publicar_evento_falla_si_esta_incompleto(): void
    {
        $admin = $this->createAdmin();
        $event = $this->createDraftEvent([
            'title' => '',
            'capacity' => 0,
        ]);
        $this->actingAsApi($admin);

        $this->putJson("/api/v1/events/{$event->id}/status", [
            'status' => 'published',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'capacity']);
    }
}
