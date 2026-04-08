<?php

namespace Tests\Unit;

use App\Services\UserManagementService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UserManagementServiceTest extends TestCase
{
    public function test_update_managed_user_falla_sobre_admin(): void
    {
        $this->expectException(ValidationException::class);

        $admin = $this->createAdmin();

        app(UserManagementService::class)->updateManagedUser($admin, ['name' => 'No']);
    }

    public function test_soft_delete_y_restore_funcionan_en_usuario_gestionable(): void
    {
        $user = $this->createModerator();
        $service = app(UserManagementService::class);

        $service->softDelete($user);
        $this->assertSoftDeleted($user);

        $restored = $service->restore($user->id);
        $this->assertNull($restored->deleted_at);
    }

    public function test_activate_y_deactivate_cambian_el_estado(): void
    {
        $user = $this->createModerator(['is_active' => true]);
        $service = app(UserManagementService::class);

        $service->deactivate($user);
        $this->assertFalse($user->fresh()->is_active);

        $service->activate($user);
        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_sync_roles_reemplaza_roles(): void
    {
        $user = $this->createModerator();

        $updated = app(UserManagementService::class)->syncRoles($user, ['moderator']);

        $this->assertSame(['moderator'], $updated->roles->pluck('name')->all());
    }

    public function test_sync_roles_falla_con_arreglo_vacio(): void
    {
        $this->expectException(ValidationException::class);

        $user = $this->createModerator();

        app(UserManagementService::class)->syncRoles($user, []);
    }
}
