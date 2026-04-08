<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EventController extends Controller
{
    // Lista eventos según visibilidad: público, moderador asignado o admin.
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Event::query()->latest();
        $user = $request->user();

        if ($user instanceof User && $user->hasRole('admin')) {
            // Admin can list every event.
        } elseif ($user instanceof User && $user->hasRole('moderator')) {
            $query->where(function ($query) use ($user): void {
                $query->where('status', 'published')
                    ->orWhereHas('moderators', fn ($moderatorQuery) => $moderatorQuery->where('users.id', $user->id));
            });
        } else {
            $query->where('status', 'published');
        }

        return EventResource::collection($query->paginate())
            ->additional(['message' => 'Events retrieved successfully.']);
    }

    // Crea eventos en estado draft y asigna creador.
    public function store(StoreEventRequest $request): JsonResponse
    {
        $user = $this->ensureAdmin($request);
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
        ], 201);
    }

    // Devuelve detalle del evento si el usuario tiene visibilidad.
    public function show(Request $request, Event $event): JsonResponse
    {
        abort_unless($this->canViewEvent($request->user(), $event), 404, 'Event not found.');

        return response()->json([
            'message' => 'Event retrieved successfully.',
            'data' => new EventResource($event),
        ]);
    }

    // Actualiza metadatos del evento (solo admin).
    public function update(UpdateEventRequest $request, Event $event): JsonResponse
    {
        $this->ensureAdmin($request);
        $event->update($request->validated());

        return response()->json([
            'message' => 'Event updated successfully.',
            'data' => new EventResource($event->refresh()),
        ]);
    }

    // Soft delete del evento (solo admin).
    public function destroy(Request $request, Event $event): JsonResponse
    {
        $this->ensureAdmin($request);
        $event->delete();

        return response()->json([
            'message' => 'Event deleted successfully.',
            'data' => null,
        ]);
    }

    // Centraliza validación de rol admin para acciones de escritura.
    private function ensureAdmin(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User && $user->hasRole('admin'), 403, 'Only admins can perform this action.');

        return $user;
    }

    // Evalúa reglas de lectura por estado y asignación de moderador.
    private function canViewEvent(?User $user, Event $event): bool
    {
        if ($event->status === 'published') {
            return true;
        }

        if (! $user instanceof User) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        if (! $user->hasRole('moderator')) {
            return false;
        }

        return $event->moderators()
            ->where('users.id', $user->id)
            ->exists();
    }
}
