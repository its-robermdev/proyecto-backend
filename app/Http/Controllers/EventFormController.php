<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateEventFormRequest;
use App\Http\Resources\EventFormResource;
use App\Http\Resources\EventFormSchemaValidationResource;
use App\Models\Event;
use App\Services\EventFormService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EventFormController extends Controller
{
    // Entrega el schema del formulario segun reglas publicas o de visibilidad del evento.
    public function show(Request $request, Event $event): JsonResponse
    {
        $publicCondition = $event->status === 'published' && $event->form_is_active === true;

        if ($publicCondition) {
            return $this->schemaResponse($event, 'Event form schema retrieved successfully.');
        }

        if ($request->user() instanceof \App\Models\User && Gate::allows('view', $event)) {
            return $this->schemaResponse($event, 'Event form schema retrieved successfully.');
        }

        return $this->notFoundResponse('Form is not available for this event.');
    }

    // Actualiza el schema y fuerza reactivacion manual del formulario.
    public function update(UpdateEventFormRequest $request, Event $event, EventFormService $eventFormService): JsonResponse
    {
        if (! Gate::allows('updateForm', $event)) {
            return $this->forbiddenResponse('You are not allowed to update this event form.');
        }

        $updatedEvent = $eventFormService->updateSchema($event, $request->validated('form_schema'));

        return response()->json([
            'message' => 'Event form schema updated successfully.',
            'data' => new EventFormResource($updatedEvent),
            'status' => 200,
        ], 200);
    }

    // Ejecuta validacion estructural del schema persistido.
    public function validateSchema(Request $request, Event $event, EventFormService $eventFormService): JsonResponse
    {
        if (! Gate::allows('updateForm', $event)) {
            return $this->forbiddenResponse('You are not allowed to validate this event form.');
        }

        $errors = $eventFormService->validateSchema($event->form_schema ?? []);

        return response()->json([
            'message' => 'Event form schema validation completed.',
            'data' => new EventFormSchemaValidationResource([
                'event_id' => $event->id,
                'is_valid' => $errors === [],
                'errors' => $errors,
            ]),
            'status' => 200,
        ], 200);
    }

    // Activa el formulario cuando el evento y schema cumplen reglas.
    public function activate(Request $request, Event $event, EventFormService $eventFormService): JsonResponse
    {
        if (! Gate::allows('updateForm', $event)) {
            return $this->forbiddenResponse('You are not allowed to activate this event form.');
        }

        $activatedEvent = $eventFormService->activate($event);

        return response()->json([
            'message' => 'Event form activated successfully.',
            'data' => new EventFormResource($activatedEvent),
            'status' => 200,
        ], 200);
    }

    // Desactiva formulario sin alterar el schema guardado.
    public function deactivate(Request $request, Event $event, EventFormService $eventFormService): JsonResponse
    {
        if (! Gate::allows('updateForm', $event)) {
            return $this->forbiddenResponse('You are not allowed to deactivate this event form.');
        }

        $deactivatedEvent = $eventFormService->deactivate($event);

        return response()->json([
            'message' => 'Event form deactivated successfully.',
            'data' => new EventFormResource($deactivatedEvent),
            'status' => 200,
        ], 200);
    }

    private function schemaResponse(Event $event, string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => new EventFormResource($event),
            'status' => 200,
        ], 200);
    }
}
