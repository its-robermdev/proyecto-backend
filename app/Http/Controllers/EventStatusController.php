<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateEventStatusRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\User;
use App\Services\EventLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventStatusController extends Controller
{
    // Cambia el estado del evento aplicando reglas de transición.
    public function __invoke(
        UpdateEventStatusRequest $request,
        Event $event,
        EventLifecycleService $eventLifecycleService,
    ): JsonResponse {
        $this->ensureAdmin($request);

        $updatedEvent = $eventLifecycleService->transitionStatus($event, (string) $request->validated('status'));

        return response()->json([
            'message' => 'Event status updated successfully.',
            'data' => new EventResource($updatedEvent),
        ]);
    }

    // Garantiza que solo admin ejecute transiciones de estado.
    private function ensureAdmin(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User && $user->hasRole('admin'), 403, 'Only admins can perform this action.');

        return $user;
    }
}
