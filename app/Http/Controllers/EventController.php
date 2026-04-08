<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\User;
use Database\Seeders\PermissionCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class EventController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Event::class, 'event', ['except' => ['show']]);
    }

    // Lista eventos segun visibilidad publica o permisos del usuario autenticado.
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Event::query()->latest();
        $user = $request->user();

        if ($user instanceof User && $user->hasPermissionTo(PermissionCatalog::ALL['view_any_event'])) {
            // Global visibility keeps the full list.
        } elseif ($user instanceof User && $user->hasPermissionTo(PermissionCatalog::ALL['view_own_event'])) {
            $query->where(function ($query) use ($user): void {
                $query->where('status', 'published')
                    ->orWhereHas('moderators', fn ($moderatorQuery) => $moderatorQuery->where('users.id', $user->id));
            });
        } else {
            $query->where('status', 'published');
        }

        return EventResource::collection($query->paginate())
            ->additional([
                'message' => 'Events retrieved successfully.',
                'status' => 200,
            ]);
    }

    // Crea eventos en estado draft y asigna creador.
    public function store(StoreEventRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $payload = $request->validated();

        $event = Event::create([
            ...$payload,
            'status' => 'draft',
            'form_is_active' => false,
            'created_by' => $user->id,
        ]);

        return response()->json([
            'message' => 'Event created successfully.',
            'data' => new EventResource($event),
            'status' => 201,
        ], 201);
    }

    // Devuelve detalle del evento si el usuario tiene visibilidad.
    public function show(Request $request, Event $event): JsonResponse
    {
        if (! Gate::allows('view', $event)) {
            return $this->notFoundResponse('Event not found.');
        }

        return response()->json([
            'message' => 'Event retrieved successfully.',
            'data' => new EventResource($event),
            'status' => 200,
        ], 200);
    }

    // Actualiza metadatos del evento.
    public function update(UpdateEventRequest $request, Event $event): JsonResponse
    {
        $event->update($request->validated());

        return response()->json([
            'message' => 'Event updated successfully.',
            'data' => new EventResource($event->refresh()),
            'status' => 200,
        ], 200);
    }

    // Soft delete del evento.
    public function destroy(Request $request, Event $event): JsonResponse
    {
        $event->delete();

        return response()->json([
            'message' => 'Event deleted successfully.',
            'data' => new EventResource($event),
            'status' => 200,
        ], 200);
    }

    // Restaura un evento previamente eliminado.
    public function restore(Request $request, int $event): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $targetEvent = Event::withTrashed()->find($event);

        if (! $targetEvent instanceof Event) {
            return $this->notFoundResponse('Event not found.');
        }

        if ($actor->cannot('restore', $targetEvent)) {
            return $this->forbiddenResponse('You are not allowed to restore this event.');
        }

        $targetEvent->restore();

        return response()->json([
            'message' => 'Event restored successfully.',
            'data' => new EventResource($targetEvent->refresh()),
            'status' => 200,
        ], 200);
    }
}
