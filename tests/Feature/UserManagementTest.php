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
        $this->get(route('admin.users.index', ['user' => $user->id]))->assertSeeText($details['name'])->assertSeeText('Masuk sebagai');
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

    public function test_page_matrix_grants_and_revokes_access_to_the_menu_and_endpoint(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $role = Role::findOrCreate('Manager', 'web');
        $user->assignRole($role);
        $colleague = User::factory()->create(['is_active' => true]);
        $colleague->assignRole($role);
        $draftPage = route('pengelolaan.draf-sop');
        $backupPage = route('draft-backups.index');
        $update = route('admin.users.permissions.update', $role);
        $matrix = route('admin.users.permissions.index', ['role' => $role->uuid]);

        $this->get($draftPage)->assertRedirect(route('account.login'));
        $this->actingAs($user, 'web')->get($draftPage)->assertForbidden();
        $this->getJson($backupPage)->assertForbidden();
        $this->get($matrix)->assertForbidden();
        $this->put($update, ['pages' => ['pengelolaan.draf-sop']])->assertForbidden();

        $admin = $this->signInAsSuperAdmin();
        $this->get(route('admin.users.index'))->assertOk()
            ->assertDontSeeText('Izin Akses')
            ->assertDontSee('name="pages[]"', false);
        $this->get($matrix)->assertOk()->assertSeeText('Pilih Peran')->assertSeeText($role->name)
            ->assertDontSeeText($user->email)->assertDontSeeText($colleague->email);
        $this->put($update, ['pages' => ['admin.users.index']])->assertSessionHasErrors('pages.0');
        $this->put($update, ['pages' => ['audit.log-aktivitas']])->assertSessionHasErrors('pages.0');
        $this->put($update, ['pages' => ['pengelolaan.draf-sop']])->assertRedirect($matrix);

        $this->actingAs($user->fresh(), 'web')->get($draftPage)->assertOk();
        $this->getJson($backupPage)->assertOk();
        $this->get(route('dashboard'))
            ->assertSee('class="dashboard-shortcut" href="'.$draftPage.'"', false)
            ->assertDontSee(route('pustaka.sop-berlaku'))
            ->assertDontSee(route('pustaka.arsip-sop'));
        $this->get(route('pustaka.arsip-sop'))->assertForbidden();
        $this->actingAs($colleague->fresh(), 'web')->get($draftPage)->assertOk();

        $this->actingAs($admin, 'web')->put($update)->assertRedirect($matrix);
        $this->actingAs($user->fresh(), 'web')->get($draftPage)->assertForbidden();
        $this->getJson($backupPage)->assertForbidden();
    }

    public function test_admin_can_add_a_web_role_and_assign_it_from_the_user_form(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $index = route('admin.roles.index');
        $store = route('admin.roles.store');
        $this->actingAs($user, 'web')->get($index)->assertForbidden();
        $this->post($store, ['name' => 'Koordinator'])->assertForbidden();

        $this->signInAsSuperAdmin();
        $this->get(route('admin.users.index'))->assertDontSeeText('Tambah Role')->assertDontSeeText('Daftar Role');
        $this->get(route('admin.users.permissions.index'))->assertSeeText('Daftar Role');
        $this->post($store, ['name' => 'Koordinator'])->assertRedirect($index);
        $this->assertDatabaseHas('roles', ['name' => 'Koordinator', 'guard_name' => 'web']);
        $this->get($index)->assertOk()->assertSeeText('Koordinator');
        $this->post($store, ['name' => 'Koordinator'])->assertSessionHasErrors('name');
        $this->post($store, ['name' => 'Administrator'])->assertSessionHasErrors('name');
        $this->get(route('admin.users.edit', $user))->assertOk()->assertSeeText('Koordinator')
            ->assertDontSeeText('Tambah Role')->assertDontSeeText('Daftar Role');
    }

    public function test_only_admin_can_delete_a_regular_role_and_its_assignments(): void
    {
        $role = Role::findOrCreate('Koordinator', 'web');
        $permission = Permission::findOrCreate('akses.pengelolaan.draf-sop', 'web');
        $role->givePermissionTo($permission);
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);
        $delete = route('admin.roles.destroy', ['role' => $role, 'from' => 'matrix']);

        $this->actingAs($user, 'web')->delete($delete)->assertForbidden();
        $this->get(route('pengelolaan.draf-sop'))->assertOk();

        $this->signInAsSuperAdmin();
        $adminRole = Role::findOrCreate('Administrator', 'web');
        $simulationRole = Role::findOrCreate('Koordinator', 'simulation');
        $this->get(route('admin.roles.index'))->assertSee('aria-label="Hapus peran Koordinator"', false)
            ->assertDontSee('aria-label="Hapus peran Administrator"', false);
        $this->delete(route('admin.roles.destroy', $adminRole))->assertForbidden();
        $this->delete(route('admin.roles.destroy', $simulationRole))->assertForbidden();
        $this->delete($delete)->assertRedirect(route('admin.roles.index', ['from' => 'matrix']));

        $this->assertModelMissing($role);
        $this->assertModelExists($user);
        $this->assertModelExists($permission);
        $this->assertFalse($user->fresh()->hasRole('Koordinator', 'web'));
        $this->actingAs($user->fresh(), 'web')->get(route('pengelolaan.draf-sop'))->assertForbidden();
    }

    public function test_only_admin_can_access_administration_and_legacy_direct_grants_do_not_apply(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $adminRole = Role::findOrCreate('Administrator', 'web');
        $admin->assignRole($adminRole);
        $user = User::factory()->create(['is_active' => true]);
        $manager = Role::findOrCreate('Manager', 'web');
        $manager->givePermissionTo(Permission::findOrCreate('akses.audit.log-aktivitas', 'web'));
        $user->assignRole($manager);
        $user->givePermissionTo(Permission::findOrCreate('akses.pengelolaan.draf-sop', 'web'));

        $this->actingAs($admin, 'web')->get(route('admin.users.index'))->assertOk();
        $audit = route('audit.log-aktivitas');
        $this->get($audit)->assertOk();
        $this->put(route('admin.users.permissions.update', $adminRole))->assertForbidden();

        $this->actingAs($user, 'web')->get($audit)->assertForbidden();
        $this->get(route('pengelolaan.draf-sop'))->assertForbidden();
        $this->get(route('admin.users.index'))->assertForbidden();
        $this->get(route('dashboard'))->assertDontSee($audit);
    }
}
