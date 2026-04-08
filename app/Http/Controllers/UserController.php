<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\SyncUserRolesRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    // Lista usuarios para administración interna.
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->ensureAdmin($request);

        $users = User::query()
            ->with('roles')
            ->latest()
            ->paginate();

        return UserResource::collection($users)
            ->additional(['message' => 'Users retrieved successfully.']);
    }

    // Crea usuario y asigna roles iniciales opcionales.
    public function store(
        StoreUserRequest $request,
        UserManagementService $userManagementService,
    ): JsonResponse {
        $this->ensureAdmin($request);

        $user = $userManagementService->create($request->validated());

        return response()->json([
            'message' => 'User created successfully.',
            'data' => new UserResource($user->load('roles')),
        ], 201);
    }

    // Muestra detalle de usuario con roles.
    public function show(Request $request, User $user): JsonResponse
    {
        $this->ensureAdmin($request);

        return response()->json([
            'message' => 'User retrieved successfully.',
            'data' => new UserResource($user->load('roles')),
        ]);
    }

    // Actualiza datos básicos del usuario.
    public function update(
        UpdateUserRequest $request,
        User $user,
        UserManagementService $userManagementService,
    ): JsonResponse {
        $this->ensureAdmin($request);

        $updatedUser = $userManagementService->update($user, $request->validated());

        return response()->json([
            'message' => 'User updated successfully.',
            'data' => new UserResource($updatedUser->load('roles')),
        ]);
    }

    // Elimina lógicamente al usuario.
    public function destroy(
        Request $request,
        User $user,
        UserManagementService $userManagementService,
    ): JsonResponse {
        $this->ensureAdmin($request);
        $userManagementService->softDelete($user);

        return response()->json([
            'message' => 'User deleted successfully.',
            'data' => null,
        ]);
    }

    // Restaura usuario previamente eliminado (soft delete).
    public function restore(
        Request $request,
        int $user,
        UserManagementService $userManagementService,
    ): JsonResponse {
        $this->ensureAdmin($request);

        $restoredUser = $userManagementService->restore($user);

        return response()->json([
            'message' => 'User restored successfully.',
            'data' => new UserResource($restoredUser->load('roles')),
        ]);
    }

    // Activa acceso del usuario en el sistema.
    public function activate(
        Request $request,
        User $user,
        UserManagementService $userManagementService,
    ): JsonResponse {
        $this->ensureAdmin($request);

        $activatedUser = $userManagementService->activate($user);

        return response()->json([
            'message' => 'User activated successfully.',
            'data' => new UserResource($activatedUser->load('roles')),
        ]);
    }

    // Desactiva acceso del usuario sin eliminarlo.
    public function deactivate(
        Request $request,
        User $user,
        UserManagementService $userManagementService,
    ): JsonResponse {
        $this->ensureAdmin($request);

        $deactivatedUser = $userManagementService->deactivate($user);

        return response()->json([
            'message' => 'User deactivated successfully.',
            'data' => new UserResource($deactivatedUser->load('roles')),
        ]);
    }

    // Obtiene solo el set de roles del usuario.
    public function roles(Request $request, User $user): JsonResponse
    {
        $this->ensureAdmin($request);

        $user->load('roles');

        return response()->json([
            'message' => 'User roles retrieved successfully.',
            'data' => [
                'user_id' => $user->id,
                'roles' => $user->roles
                    ->pluck('name')
                    ->values(),
            ],
        ]);
    }

    // Sincroniza roles (reemplazo total del set actual).
    public function syncRoles(
        SyncUserRolesRequest $request,
        User $user,
        UserManagementService $userManagementService,
    ): JsonResponse {
        $this->ensureAdmin($request);

        $updatedUser = $userManagementService->syncRoles(
            $user,
            $request->validated('roles'),
        );

        return response()->json([
            'message' => 'User roles synchronized successfully.',
            'data' => [
                'user_id' => $updatedUser->id,
                'roles' => $updatedUser->roles
                    ->pluck('name')
                    ->values(),
            ],
        ]);
    }

    // Reutiliza comprobación admin para endpoints de usuarios.
    private function ensureAdmin(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User && $user->hasRole('admin'), 403, 'Only admins can perform this action.');

        return $user;
    }
}
