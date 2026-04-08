<?php

namespace Tests\Unit;

use App\Services\EventLifecycleService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EventLifecycleServiceTest extends TestCase
{
    public function test_permite_transicion_valida(): void
    {
        $event = $this->createDraftEvent();

        $updated = app(EventLifecycleService::class)->transitionStatus($event, 'published');

        $this->assertSame('published', $updated->status);
    }

    public function test_rechaza_transicion_invalida(): void
    {
        $this->expectException(ValidationException::class);

        $event = $this->createDraftEvent();

        app(EventLifecycleService::class)->transitionStatus($event, 'closed');
    }

    public function test_no_modifica_nada_si_el_estado_destino_es_igual(): void
    {
        $event = $this->createDraftEvent();

        $same = app(EventLifecycleService::class)->transitionStatus($event, 'draft');

        $this->assertTrue($same->is($event));
        $this->assertSame('draft', $same->status);
    }

    public function test_publicar_exige_titulo_y_descripcion(): void
    {
        $this->expectException(ValidationException::class);

        $event = $this->createDraftEvent([
            'title' => '',
            'description' => '',
        ]);

        app(EventLifecycleService::class)->transitionStatus($event, 'published');
    }

    public function test_publicar_exige_registration_deadline_y_start_date(): void
    {
        $this->expectException(ValidationException::class);

        $event = $this->createDraftEvent();
        $event->registration_deadline = null;
        $event->start_date = null;

        app(EventLifecycleService::class)->transitionStatus($event, 'published');
    }

    public function test_publicar_exige_capacity_mayor_a_cero(): void
    {
        $this->expectException(ValidationException::class);

        $event = $this->createDraftEvent(['capacity' => 0]);

        app(EventLifecycleService::class)->transitionStatus($event, 'published');
    }

    public function test_publicar_exige_deadline_antes_o_igual_a_fecha_de_inicio(): void
    {
        $this->expectException(ValidationException::class);

        $event = $this->createDraftEvent([
            'start_date' => now()->addDays(3),
            'registration_deadline' => now()->addDays(4),
        ]);

        app(EventLifecycleService::class)->transitionStatus($event, 'published');
    }
}
