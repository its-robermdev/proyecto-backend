<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventModeratorRequest;
use App\Models\Event;
use App\Models\User;
use App\Services\EventModeratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventModeratorController extends Controller
{
    // Lista moderadores/responsables asociados al evento.
    public function index(Request $request, Event $event): JsonResponse
    {
        $user = $request->user();

        abort_unless(
            $user instanceof User && $this->canInspectModerators($user, $event),
            403,
            'You are not allowed to inspect event moderators.',
        );

        $event->load('moderators:id,name,email');

        return response()->json([
            'message' => 'Event moderators retrieved successfully.',
            'data' => [
                'event_id' => $event->id,
                'moderators' => $event->moderators->map(fn (User $moderator): array => [
                    'id' => $moderator->id,
                    'name' => $moderator->name,
                    'email' => $moderator->email,
                ])->values(),
            ],
        ]);
    }

    // Asigna un usuario moderador al evento.
    public function store(
        StoreEventModeratorRequest $request,
        Event $event,
        EventModeratorService $eventModeratorService,
    ): JsonResponse {
        $this->ensureAdmin($request);

        $assignedModerator = $eventModeratorService->assign(
            $event,
            (int) $request->validated('user_id'),
        );

        return response()->json([
            'message' => 'Moderator assigned to event successfully.',
            'data' => [
                'event_id' => $event->id,
                'moderator' => [
                    'id' => $assignedModerator->id,
                    'name' => $assignedModerator->name,
                    'email' => $assignedModerator->email,
                ],
            ],
        ], 201);
    }

    // Remueve la asignación de moderador para un evento.
    public function destroy(
        Request $request,
        Event $event,
        User $user,
        EventModeratorService $eventModeratorService,
    ): JsonResponse {
        $this->ensureAdmin($request);
        $eventModeratorService->remove($event, $user);

        return response()->json([
            'message' => 'Moderator removed from event successfully.',
            'data' => null,
        ]);
    }

    // Verifica rol admin para mutaciones de responsables.
    private function ensureAdmin(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User && $user->hasRole('admin'), 403, 'Only admins can perform this action.');

        return $user;
    }

    // Define si un usuario puede consultar responsables del evento.
    private function canInspectModerators(User $user, Event $event): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if (! $user->hasRole('moderator')) {
            return false;
        }

        if ($event->status === 'published') {
            return true;
        }

        return $event->moderators()
            ->where('users.id', $user->id)
            ->exists();
    }
}
