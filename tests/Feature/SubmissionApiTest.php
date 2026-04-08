<?php

namespace Tests\Feature;

use App\Models\Submission;
use Tests\TestCase;

class SubmissionApiTest extends TestCase
{
    public function test_crea_submission_individual_valida(): void
    {
        $event = $this->createPublishedEvent([
            'allows_teams' => true,
            'capacity' => 3,
            'form_schema' => $this->validFormSchema(),
        ]);

        $response = $this->postJson("/api/v1/events/{$event->id}/submissions", $this->individualSubmissionPayload());

        $response->assertCreated()
            ->assertJsonPath('data.event_id', $event->id)
            ->assertJsonPath('data.status', Submission::STATUS_PENDING)
            ->assertJsonPath('data.participation_type', 'individual');
    }

    public function test_crea_submission_de_equipo_valida(): void
    {
        $event = $this->createPublishedEvent([
            'allows_teams' => true,
            'capacity' => 5,
            'form_schema' => $this->validFormSchema(),
        ]);

        $response = $this->postJson("/api/v1/events/{$event->id}/submissions", $this->teamSubmissionPayload());

        $response->assertCreated()
            ->assertJsonPath('data.participation_type', 'team')
            ->assertJsonPath('data.team_name', 'Los Builders');

        $submissionId = $response->json('data.id');

        $this->assertDatabaseHas('submission_members', [
            'submission_id' => $submissionId,
            'full_name' => 'Capitana Uno',
            'is_captain' => true,
        ]);
    }

    public function test_submission_falla_si_evento_no_esta_publicado(): void
    {
        $event = $this->createDraftEvent([
            'form_schema' => $this->validFormSchema(),
        ]);

        $this->postJson("/api/v1/events/{$event->id}/submissions", $this->individualSubmissionPayload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['event']);
    }

    public function test_submission_falla_si_formulario_no_esta_activo(): void
    {
        $event = $this->createPublishedEvent([
            'form_is_active' => false,
            'form_schema' => $this->validFormSchema(),
        ]);

        $this->postJson("/api/v1/events/{$event->id}/submissions", $this->individualSubmissionPayload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['event']);
    }

    public function test_submission_falla_si_deadline_vencio(): void
    {
        $event = $this->createPublishedEvent([
            'registration_deadline' => now()->subMinute(),
            'form_schema' => $this->validFormSchema(),
        ]);

        $this->postJson("/api/v1/events/{$event->id}/submissions", $this->individualSubmissionPayload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['registration_deadline']);
    }

    public function test_submission_falla_si_no_hay_cupos(): void
    {
        $event = $this->createPublishedEvent([
            'capacity' => 1,
            'form_schema' => $this->validFormSchema(),
        ]);
        $this->createSubmission($event, ['status' => Submission::STATUS_PENDING]);

        $this->postJson("/api/v1/events/{$event->id}/submissions", $this->individualSubmissionPayload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['event_capacity_guard']);
    }

    public function test_submission_falla_si_no_permite_equipos(): void
    {
        $event = $this->createPublishedEvent([
            'allows_teams' => false,
            'form_schema' => $this->validFormSchema(),
        ]);

        $this->postJson("/api/v1/events/{$event->id}/submissions", $this->teamSubmissionPayload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['participation_type']);
    }

    public function test_submission_de_equipo_requiere_un_solo_capitan(): void
    {
        $event = $this->createPublishedEvent([
            'allows_teams' => true,
            'form_schema' => $this->validFormSchema(),
        ]);

        $payload = $this->teamSubmissionPayload([
            'members' => [
                ['full_name' => 'Uno', 'email' => 'uno@example.com', 'is_captain' => false],
                ['full_name' => 'Dos', 'email' => 'dos@example.com', 'is_captain' => false],
            ],
        ]);

        $this->postJson("/api/v1/events/{$event->id}/submissions", $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['members']);
    }

    public function test_submission_valida_campos_dinamicos_del_schema(): void
    {
        $event = $this->createPublishedEvent(['form_schema' => $this->validFormSchema()]);

        $payload = $this->individualSubmissionPayload([
            'form_answers' => $this->validFormAnswers([
                'portfolio_url' => 'no-es-url',
            ]),
        ]);

        $this->postJson("/api/v1/events/{$event->id}/submissions", $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['form_answers.portfolio_url']);
    }

    public function test_submission_no_repite_email_dentro_del_mismo_evento(): void
    {
        $event = $this->createPublishedEvent(['form_schema' => $this->validFormSchema()]);
        $payload = $this->individualSubmissionPayload([
            'submitted_by_email' => 'repetido@example.com',
        ]);

        $this->postJson("/api/v1/events/{$event->id}/submissions", $payload)->assertCreated();

        $this->postJson("/api/v1/events/{$event->id}/submissions", $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['submitted_by_email']);
    }

    public function test_submission_permite_mismo_email_en_eventos_distintos(): void
    {
        $firstEvent = $this->createPublishedEvent(['form_schema' => $this->validFormSchema()]);
        $secondEvent = $this->createPublishedEvent(['form_schema' => $this->validFormSchema()]);
        $payload = $this->individualSubmissionPayload([
            'submitted_by_email' => 'reutilizado@example.com',
        ]);

        $this->postJson("/api/v1/events/{$firstEvent->id}/submissions", $payload)->assertCreated();
        $this->postJson("/api/v1/events/{$secondEvent->id}/submissions", $payload)->assertCreated();
    }

    public function test_admin_puede_listar_submissions_de_evento(): void
    {
        $admin = $this->createAdmin();
        $event = $this->createPublishedEvent();
        $submission = $this->createSubmission($event);
        $this->actingAsApi($admin);

        $this->getJson("/api/v1/events/{$event->id}/submissions")
            ->assertOk()
            ->assertJsonFragment(['id' => $submission->id]);
    }

    public function test_moderador_asignado_puede_listar_y_ver_submission(): void
    {
        $moderator = $this->createModerator();
        $event = $this->createPublishedEvent();
        $submission = $this->createSubmission($event);
        $this->assignModerator($event, $moderator);
        $this->actingAsApi($moderator);

        $this->getJson("/api/v1/events/{$event->id}/submissions")
            ->assertOk()
            ->assertJsonFragment(['id' => $submission->id]);

        $this->getJson("/api/v1/submissions/{$submission->id}")
            ->assertOk()
            ->assertJsonPath('data.event.id', $event->id);
    }

    public function test_usuario_sin_acceso_no_puede_listar_ni_ver_submission(): void
    {
        $user = $this->createUser();
        $event = $this->createPublishedEvent();
        $submission = $this->createSubmission($event);
        $this->actingAsApi($user);

        $this->getJson("/api/v1/events/{$event->id}/submissions")
            ->assertForbidden();

        $this->getJson("/api/v1/submissions/{$submission->id}")
            ->assertNotFound();
    }

    public function test_revisar_submission_aprobada_guarda_auditoria(): void
    {
        $moderator = $this->createModerator();
        $event = $this->createPublishedEvent();
        $submission = $this->createSubmission($event);
        $this->assignModerator($event, $moderator);
        $this->actingAsApi($moderator);

        $this->patchJson("/api/v1/submissions/{$submission->id}/review", [
            'status' => 'approved',
            'review_comment' => 'Cumple criterios.',
        ])->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.reviewer.id', $moderator->id);

        $this->assertDatabaseHas('submissions', [
            'id' => $submission->id,
            'status' => 'approved',
            'reviewed_by' => $moderator->id,
        ]);
    }

    public function test_revisar_submission_falla_sin_permiso_o_asignacion(): void
    {
        $moderator = $this->createModerator();
        $otherModerator = $this->createModerator();
        $event = $this->createPublishedEvent();
        $submission = $this->createSubmission($event);
        $this->assignModerator($event, $moderator);
        $this->actingAsApi($otherModerator);

        $this->patchJson("/api/v1/submissions/{$submission->id}/review", [
            'status' => 'approved',
        ])->assertForbidden();
    }

    public function test_revisar_submission_valida_status_y_longitud_de_comentario(): void
    {
        $moderator = $this->createModerator();
        $event = $this->createPublishedEvent();
        $submission = $this->createSubmission($event);
        $this->assignModerator($event, $moderator);
        $this->actingAsApi($moderator);

        $this->patchJson("/api/v1/submissions/{$submission->id}/review", [
            'status' => 'pending',
            'review_comment' => str_repeat('a', 1001),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['status', 'review_comment']);
    }
}
