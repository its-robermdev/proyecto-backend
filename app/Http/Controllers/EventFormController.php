<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateEventFormRequest;
use App\Models\Event;
use App\Models\User;
use App\Services\EventFormService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventFormController extends Controller
{
    // Entrega el schema del formulario según reglas de visibilidad.
    public function show(Request $request, Event $event): JsonResponse
    {
        $user = $request->user();

        if ($user instanceof User && $user->hasRole('admin')) {
            return $this->schemaResponse($event, 'Event form schema retrieved successfully.');
        }

        if ($user instanceof User && $user->hasRole('moderator') && $this->canModeratorInspectForm($user, $event)) {
            return $this->schemaResponse($event, 'Event form schema retrieved successfully.');
        }

        abort_unless(
            $event->status === 'published' && $event->form_is_active === true,
            404,
            'Form is not available for this event.',
        );

        return $this->schemaResponse($event, 'Event form schema retrieved successfully.');
    }

    // Actualiza el schema y fuerza reactivación manual del formulario.
    public function update(UpdateEventFormRequest $request, Event $event, EventFormService $eventFormService): JsonResponse
    {
        $this->ensureAdmin($request);

        $updatedEvent = $eventFormService->updateSchema($event, $request->validated('form_schema'));

        return response()->json([
            'message' => 'Event form schema updated successfully.',
            'data' => [
                'event_id' => $updatedEvent->id,
                'form_is_active' => $updatedEvent->form_is_active,
                'form_schema' => $updatedEvent->form_schema,
            ],
        ]);
    }

    // Ejecuta validación estructural del schema persistido.
    public function validateSchema(Request $request, Event $event, EventFormService $eventFormService): JsonResponse
    {
        $this->ensureAdmin($request);

        $errors = $eventFormService->validateSchema($event->form_schema ?? []);

        return response()->json([
            'message' => 'Event form schema validation completed.',
            'data' => [
                'event_id' => $event->id,
                'is_valid' => $errors === [],
                'errors' => $errors,
            ],
        ]);
    }

    // Activa el formulario cuando el evento y schema cumplen reglas.
    public function activate(Request $request, Event $event, EventFormService $eventFormService): JsonResponse
    {
        $this->ensureAdmin($request);

        $activatedEvent = $eventFormService->activate($event);

        return response()->json([
            'message' => 'Event form activated successfully.',
            'data' => [
                'event_id' => $activatedEvent->id,
                'form_is_active' => $activatedEvent->form_is_active,
            ],
        ]);
    }

    // Desactiva formulario sin alterar el schema guardado.
    public function deactivate(Request $request, Event $event, EventFormService $eventFormService): JsonResponse
    {
        $this->ensureAdmin($request);

        $deactivatedEvent = $eventFormService->deactivate($event);

        return response()->json([
            'message' => 'Event form deactivated successfully.',
            'data' => [
                'event_id' => $deactivatedEvent->id,
                'form_is_active' => $deactivatedEvent->form_is_active,
            ],
        ]);
    }

    // Normaliza respuesta pública del schema.
    private function schemaResponse(Event $event, string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => [
                'event_id' => $event->id,
                'form_is_active' => $event->form_is_active,
                'form_schema' => $event->form_schema ?? [],
            ],
        ]);
    }

    // Reutiliza la guardia de admin en operaciones de configuración.
    private function ensureAdmin(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User && $user->hasRole('admin'), 403, 'Only admins can perform this action.');

        return $user;
    }

    // Permite a moderador ver formularios publicados o de eventos asignados.
    private function canModeratorInspectForm(User $user, Event $event): bool
    {
        if ($event->status === 'published') {
            return true;
        }

        return $event->moderators()
            ->where('users.id', $user->id)
            ->exists();
    }
}
