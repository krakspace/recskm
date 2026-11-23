<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
