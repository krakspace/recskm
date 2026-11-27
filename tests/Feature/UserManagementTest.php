<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_edit_and_delete_a_user(): void
    {
        $this->signInAsSuperAdmin();
        $this->get(route('admin.users.index'))->assertOk()->assertSeeText('Tambah Pengguna');
        $this->get(route('admin.users.create'))->assertOk()->assertSeeText('Tambah Pengguna');

        $details = ['name' => 'Pengguna Baru', 'username' => 'pengguna-baru', 'email' => 'baru@example.com', 'is_active' => '1'];
        $this->post(route('admin.users.store'), $details + [
            'password' => 'KataSandi-123',
            'password_confirmation' => 'KataSandi-123',
        ])->assertRedirect(route('admin.users.index'));

        $user = User::where('email', $details['email'])->firstOrFail();
        $this->assertTrue(Hash::check('KataSandi-123', $user->password));
        $this->get(route('admin.users.index'))->assertSeeText($details['name'])->assertSeeText('Masuk sebagai');
        $this->get(route('admin.users.edit', $user))->assertOk()->assertSee('value="baru@example.com"', false);

        $this->patch(route('admin.users.update', $user), array_replace($details, [
            'name' => 'Pengguna Diperbarui', 'is_active' => '0', 'password' => '',
        ]))->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Pengguna Diperbarui', 'is_active' => false]);
        $this->assertSame($user->password, $user->fresh()->password);

        $role = Role::findOrCreate('viewer', 'web');
        $user->assignRole($role);
        $this->delete(route('admin.users.destroy', $user))->assertRedirect(route('admin.users.index'));
        $this->assertModelMissing($user);
        $this->assertDatabaseMissing('model_has_roles', ['model_id' => $user->id]);
        $this->assertModelExists($role);
    }

    public function test_user_form_rejects_duplicate_identity_and_mismatched_password(): void
    {
        $admin = $this->signInAsSuperAdmin();
        $this->post(route('admin.users.store'), [
            'name' => 'Duplikat', 'username' => $admin->username, 'email' => $admin->email, 'is_active' => '1',
            'password' => 'KataSandi-123', 'password_confirmation' => 'KataSandi-Lain',
        ])->assertSessionHasErrors(['username', 'email', 'password']);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_super_admin_cannot_disable_or_delete_their_own_account(): void
    {
        $admin = $this->signInAsSuperAdmin();
        $this->patch(route('admin.users.update', $admin), [
            'name' => $admin->name, 'username' => $admin->username, 'email' => $admin->email, 'is_active' => '0',
        ])->assertSessionHasErrors('is_active');
        $this->delete(route('admin.users.destroy', $admin))->assertForbidden();
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_regular_users_and_impersonated_super_admins_cannot_manage_users(): void
    {
        $admin = $this->signInAsSuperAdmin();
        $regularUser = User::factory()->create();
        $regularUser->givePermissionTo(Permission::findOrCreate('manage-users', 'web'));
        $this->actingAs($regularUser, 'web');
        $this->post(route('admin.users.store'))->assertForbidden();
        $this->patch(route('admin.users.update', $admin))->assertForbidden();
        $this->delete(route('admin.users.destroy', $admin))->assertForbidden();

        $targetAdmin = User::factory()->create();
        $targetAdmin->assignRole(Role::findOrCreate(User::SUPER_ADMIN_ROLE, 'web'));
        $this->actingAs($admin, 'web')->post(route('admin.impersonation.store', $targetAdmin))->assertRedirect();
        $this->get(route('admin.users.create'))->assertForbidden();
        $this->delete(route('admin.users.destroy', $regularUser))->assertForbidden();
        $this->assertModelExists($regularUser);
    }

    public function test_super_admin_can_replace_and_clear_a_users_web_roles(): void
    {
        $this->signInAsSuperAdmin();
        $user = User::factory()->create();
        $viewer = Role::findOrCreate('viewer', 'web');
        $administrator = Role::findOrCreate('Administrator', 'web');
        $otherGuardRole = Role::findOrCreate('simulation-viewer', 'simulation');
        $user->assignRole($viewer, $otherGuardRole);

        $form = $this->get(route('admin.users.edit', $user))->assertOk()
            ->assertSeeText('Administrator')->assertSeeText('viewer')->assertDontSeeText('simulation-viewer');
        $this->assertMatchesRegularExpression('~</legend>\s*<div class="form-check">~', $form->getContent());

        $details = $user->only(['name', 'username', 'email', 'is_active']);
        $details['is_active'] = true;
        $this->patch(route('admin.users.update', $user), $details + ['role_ids' => ['', $administrator->uuid]])
            ->assertRedirect(route('admin.users.index'));
        $this->assertEqualsCanonicalizing(['Administrator', 'simulation-viewer'], $user->fresh()->getRoleNames()->all());

        $this->patch(route('admin.users.update', $user), $details + ['role_ids' => ['']])
            ->assertRedirect(route('admin.users.index'));
        $this->assertSame(['simulation-viewer'], $user->fresh()->getRoleNames()->all());
    }

    public function test_role_update_rejects_wrong_guard_and_removing_own_super_admin_role(): void
    {
        $admin = $this->signInAsSuperAdmin();
        $details = $admin->only(['username', 'email', 'is_active']) + ['name' => 'Nama yang tidak tersimpan'];
        $otherGuardRole = Role::findOrCreate('simulation-viewer', 'simulation');

        $this->patch(route('admin.users.update', $admin), $details + ['role_ids' => [$otherGuardRole->uuid]])
            ->assertSessionHasErrors('role_ids');
        $this->patch(route('admin.users.update', $admin), $details + ['role_ids' => ['']])
            ->assertSessionHasErrors('role_ids');
        $this->assertSame($admin->name, $admin->fresh()->name);
        $this->assertTrue($admin->fresh()->hasRole(User::SUPER_ADMIN_ROLE, 'web'));
    }

    private function signInAsSuperAdmin(): User
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole(Role::findOrCreate(User::SUPER_ADMIN_ROLE, 'web'));
        $this->actingAs($admin, 'web');

        return $admin;
    }
}
