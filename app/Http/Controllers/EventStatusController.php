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
        Event $event,
        EventLifecycleService $eventLifecycleService,
    ): JsonResponse {
        if (! Gate::allows('updateStatus', $event)) {
            return $this->forbiddenResponse('You are not allowed to update this event status.');
        }

        $updatedEvent = $eventLifecycleService->transitionStatus($event, (string) $request->validated('status'));

        return response()->json([
            'message' => 'Event status updated successfully.',
            'data' => new EventResource($updatedEvent),
            'status' => 200,
        ], 200);
    }
}
