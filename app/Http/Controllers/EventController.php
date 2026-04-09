<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class EventController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Event::class, 'event', ['except' => ['show', 'update', 'destroy']]);
    }

    // Lista eventos segun visibilidad publica o permisos del usuario autenticado.
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Event::query()
            ->with('creator:id,name');
        $user = $request->user();

        if ($user instanceof User && $user->hasPermissionTo(PermissionCatalog::ALL['view_any_event'])) {
            // Global visibility keeps the full list.
            $query->latest();
        } elseif ($user instanceof User && $user->hasPermissionTo(PermissionCatalog::ALL['view_own_event'])) {
            $query->whereHas('moderators', fn ($moderatorQuery) => $moderatorQuery->where('users.id', $user->id));
            $query->orderBy('start_date');
        } else {
            $query->where('status', 'published')
                ->orderBy('start_date');
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
        ])->load('creator:id,name');

        return response()->json([
            'message' => 'Event created successfully.',
            'data' => new EventResource($event),
            'status' => 201,
        ], 201);
    }

    // Devuelve detalle del evento si el usuario tiene visibilidad.
    public function show(Request $request, int $event): JsonResponse
    {
        $targetEvent = Event::with('creator:id,name')->find($event);

        if (! $targetEvent instanceof Event) {
            return $this->notFoundResponse('Event not found.');
        }

        if (! Gate::allows('view', $targetEvent)) {
            if (! $request->user() instanceof User) {
                return $this->notFoundResponse('Event not found.');
            }

            return $this->forbiddenResponse('This action is unauthorized.');
        }

        return response()->json([
            'message' => 'Event retrieved successfully.',
            'data' => new EventResource($targetEvent),
            'status' => 200,
        ], 200);
    }

    // Actualiza metadatos del evento.
    public function update(UpdateEventRequest $request, int $event): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $targetEvent = Event::find($event);

        if (! $targetEvent instanceof Event) {
            return $this->notFoundResponse('Event not found.');
        }

        if ($actor->cannot('update', $targetEvent)) {
            return $this->forbiddenResponse('This action is unauthorized.');
        }

        $targetEvent->update($request->validated());
        $targetEvent->refresh()->load('creator:id,name');

        return response()->json([
            'message' => 'Event updated successfully.',
            'data' => new EventResource($targetEvent),
            'status' => 200,
        ], 200);
    }

    // Soft delete del evento.
    public function destroy(Request $request, int $event): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $targetEvent = Event::withTrashed()
            ->with('creator:id,name')
            ->find($event);

        if (! $targetEvent instanceof Event) {
            return $this->notFoundResponse('Event not found.');
        }

        if ($actor->cannot('delete', $targetEvent)) {
            return $this->forbiddenResponse('This action is unauthorized.');
        }

        if ($targetEvent->trashed()) {
            return $this->conflictResponse('This event is already hidden.');
        }

        $targetEvent->delete();

        return response()->json([
            'message' => 'Event deleted successfully.',
            'data' => new EventResource($targetEvent),
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
            return $this->forbiddenResponse('This action is unauthorized.');
        }

        if (! $targetEvent->trashed()) {
            return $this->conflictResponse('This event is already visible.');
        }

        $targetEvent->restore();
        $targetEvent->refresh()->load('creator:id,name');

        return response()->json([
            'message' => 'Event restored successfully.',
            'data' => new EventResource($targetEvent),
            'status' => 200,
        ], 200);
    }
}
