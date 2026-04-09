<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateEventStatusRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Services\EventLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class EventStatusController extends Controller
{
    // Cambia el estado del evento aplicando reglas de transicion.
    public function __invoke(
        UpdateEventStatusRequest $request,
        int $event,
        EventLifecycleService $eventLifecycleService,
    ): JsonResponse {
        $targetEvent = Event::find($event);

        if (! $targetEvent instanceof Event) {
            return $this->notFoundResponse('Event not found.');
        }

        if (! Gate::allows('updateStatus', $targetEvent)) {
            return $this->forbiddenResponse('This action is unauthorized.');
        }

        $updatedEvent = $eventLifecycleService->transitionStatus($targetEvent, (string) $request->validated('status'));

        return response()->json([
            'message' => 'Event status updated successfully.',
            'data' => new EventResource($updatedEvent),
            'status' => 200,
        ], 200);
    }
}
