<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventResource;
use App\Http\Resources\UserResource;
use App\Http\Requests\StoreEventModeratorRequest;
use App\Models\Event;
use App\Models\User;
use App\Services\EventModeratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EventModeratorController extends Controller
{
    // Lista moderadores/responsables asociados al evento.
    public function index(Request $request, int $event): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $targetEvent = Event::find($event);

        if (! $targetEvent instanceof Event) {
            return $this->notFoundResponse('Event not found.');
        }

        if ($user->cannot('inspectModerators', $targetEvent)) {
            return $this->forbiddenResponse('This action is unauthorized.');
        }

        $targetEvent->load('moderators:id,name,email');

        return response()->json([
            'message' => 'Event moderators retrieved successfully.',
            'data' => [
                'event' => new EventResource($targetEvent),
                'moderators' => UserResource::collection($targetEvent->moderators),
            ],
            'status' => 200,
        ], 200);
    }

    // Asigna un usuario moderador al evento.
    public function store(
        StoreEventModeratorRequest $request,
        Event $event,
        EventModeratorService $eventModeratorService,
    ): JsonResponse {
        if (! Gate::allows('assignModerators', $event)) {
            return $this->forbiddenResponse('This action is unauthorized.');
        }

        $assignedModerator = $eventModeratorService->assign(
            $event,
            (int) $request->validated('user_id'),
        );

        return response()->json([
            'message' => 'Moderator assigned to event successfully.',
            'data' => [
                'event' => new EventResource($event),
                'moderator' => new UserResource($assignedModerator),
            ],
            'status' => 201,
        ], 201);
    }

    // Remueve la asignacion de moderador para un evento.
    public function destroy(
        Request $request,
        Event $event,
        User $user,
        EventModeratorService $eventModeratorService,
    ): JsonResponse {
        if (! Gate::allows('assignModerators', $event)) {
            return $this->forbiddenResponse('This action is unauthorized.');
        }

        $user->load('roles');
        $eventModeratorService->remove($event, $user);

        return response()->json([
            'message' => 'Moderator removed from event successfully.',
            'data' => new UserResource($user),
            'status' => 200,
        ], 200);
    }
}
