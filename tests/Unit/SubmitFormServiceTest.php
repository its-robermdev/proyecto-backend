<?php

namespace Tests\Unit;

use App\Models\Submission;
use App\Services\SubmitFormService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SubmitFormServiceTest extends TestCase
{
    public function test_crea_submission_individual(): void
    {
        $event = $this->createPublishedEvent([
            'capacity' => 2,
            'form_schema' => $this->validFormSchema(),
        ]);

        $submission = app(SubmitFormService::class)->submit($event, $this->individualSubmissionPayload());

        $this->assertSame(Submission::STATUS_PENDING, $submission->status);
        $this->assertSame('individual', $submission->participation_type);
        $this->assertCount(0, $submission->members);
    }

    public function test_crea_submission_de_equipo_con_miembros(): void
    {
        $event = $this->createPublishedEvent([
            'capacity' => 4,
            'allows_teams' => true,
            'form_schema' => $this->validFormSchema(),
        ]);

        $submission = app(SubmitFormService::class)->submit($event, $this->teamSubmissionPayload());

        $this->assertSame('team', $submission->participation_type);
        $this->assertCount(2, $submission->members);
    }

    public function test_falla_si_no_hay_cupos_disponibles(): void
    {
        $this->expectException(ValidationException::class);

        $event = $this->createPublishedEvent([
            'capacity' => 1,
            'form_schema' => $this->validFormSchema(),
        ]);
        $this->createSubmission($event, ['status' => Submission::STATUS_PENDING]);

        app(SubmitFormService::class)->submit($event, $this->individualSubmissionPayload());
    }

    public function test_cuenta_rechazadas_como_no_ocupantes_de_cupo(): void
    {
        $event = $this->createPublishedEvent([
            'capacity' => 1,
            'form_schema' => $this->validFormSchema(),
        ]);
        $this->createSubmission($event, ['status' => Submission::STATUS_REJECTED]);

        $submission = app(SubmitFormService::class)->submit($event, $this->individualSubmissionPayload());

        $this->assertSame(Submission::STATUS_PENDING, $submission->status);
    }
}
