<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Tests\TestCase;

class PermissionSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_and_permission_use_uuid_primary_keys(): void
    {
        $role = Role::create(['name' => 'admin']);
        $permission = Permission::create(['name' => 'manage users']);

        $this->assertSame('uuid', $role->getKeyName());
        $this->assertSame('uuid', $permission->getKeyName());
        $this->assertIsString($role->getKey());
        $this->assertIsString($permission->getKey());
    }

    public function test_user_can_be_assigned_a_role_and_permission(): void
    {
        $permission = Permission::create(['name' => 'manage users']);
        $role = Role::create(['name' => 'admin']);
        $role->givePermissionTo($permission);

        $user = User::factory()->create();
        $user->assignRole($role);

        $this->assertTrue($user->fresh()->hasRole('admin'));
        $this->assertTrue($user->fresh()->can('manage users'));
    }

    public function test_permission_middleware_aliases_are_registered(): void
    {
        $aliases = app(Kernel::class)->getMiddlewareAliases();

        $this->assertSame(RoleMiddleware::class, $aliases['role'] ?? null);
        $this->assertSame(PermissionMiddleware::class, $aliases['permission'] ?? null);
        $this->assertSame(RoleOrPermissionMiddleware::class, $aliases['role_or_permission'] ?? null);
    }
}
