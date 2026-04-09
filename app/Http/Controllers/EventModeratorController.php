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
        int $event,
        EventModeratorService $eventModeratorService,
    ): JsonResponse {
        $targetEvent = Event::find($event);

        if (! $targetEvent instanceof Event) {
            return $this->notFoundResponse('Event not found.');
        }

        if (! Gate::allows('assignModerators', $targetEvent)) {
            return $this->forbiddenResponse('This action is unauthorized.');
        }

        $moderatorId = (int) $request->validated('user_id');
        $isAlreadyAssigned = $targetEvent->moderators()
            ->where('users.id', $moderatorId)
            ->exists();

        if ($isAlreadyAssigned) {
            return $this->conflictResponse('Moderator is already assigned to this event.');
        }

        $assignedModerator = $eventModeratorService->assign(
            $targetEvent,
            $moderatorId,
        );

        return response()->json([
            'message' => 'Moderator assigned to event successfully.',
            'data' => [
                'event' => new EventResource($targetEvent),
                'moderator' => new UserResource($assignedModerator),
            ],
            'status' => 201,
        ], 201);
    }

    // Remueve la asignacion de moderador para un evento.
    public function destroy(
        Request $request,
        int $event,
        int $user,
        EventModeratorService $eventModeratorService,
    ): JsonResponse {
        $targetEvent = Event::find($event);

        if (! $targetEvent instanceof Event) {
            return $this->notFoundResponse('Event not found.');
        }

        if (! Gate::allows('assignModerators', $targetEvent)) {
            return $this->forbiddenResponse('This action is unauthorized.');
        }

        $targetModerator = User::find($user);

        if (! $targetModerator instanceof User) {
            return $this->notFoundResponse('Moderator not found for this event.');
        }

        $isAssignedModerator = $targetEvent->moderators()
            ->where('users.id', $targetModerator->id)
            ->exists();

        if (! $isAssignedModerator) {
            return $this->notFoundResponse('Moderator not found for this event.');
        }

        $targetModerator->load('roles');
        $eventModeratorService->remove($targetEvent, $targetModerator);

        return response()->json([
            'message' => 'Moderator removed from event successfully.',
            'data' => new UserResource($targetModerator),
            'status' => 200,
        ], 200);
    }
}
