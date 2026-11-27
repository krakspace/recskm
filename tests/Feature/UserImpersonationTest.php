<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class UserImpersonationTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_switch_to_a_user_and_return(): void
    {
        Route::middleware('web')->get('/akses-fitur-uji', function () {
            Gate::authorize('sop.configure');

            return response()->noContent();
        });

        $admin = User::factory()->create();
        $role = Role::findOrCreate(User::SUPER_ADMIN_ROLE, 'web');
        $admin->assignRole($role);
        $target = User::factory()->create(['name' => 'Target Account']);
        $target->assignRole($role);

        $this->get(route('admin.users.index'))->assertRedirect(route('account.login'));
        $this->post(route('account.login.store'), ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('admin.users.index'));
        Auth::guard('web')->forgetUser();
        $this->get(route('admin.users.index'))->assertOk()->assertSeeText('Target Account');
        $this->get('/akses-fitur-uji')->assertNoContent();
        $this->post(route('admin.impersonation.store', $target))->assertRedirect(route('dashboard'));
        Auth::guard('web')->forgetUser();
        $this->get(route('dashboard'))->assertSeeText('Target Account')->assertSeeText('Kembali ke Super Admin');
        $this->assertAuthenticatedAs($target, 'web');
        $this->get(route('admin.users.index'))->assertForbidden();
        $this->get('/akses-fitur-uji')->assertForbidden();
        $this->post(route('admin.impersonation.store', $admin))->assertForbidden();
        $this->delete(route('admin.impersonation.destroy'))->assertRedirect(route('admin.users.index'));
        Auth::guard('web')->forgetUser();
        $this->get(route('admin.users.index'))->assertOk()->assertDontSeeText('Kembali ke Super Admin');
        $this->assertAuthenticatedAs($admin, 'web');
        $this->get('/akses-fitur-uji')->assertNoContent();
    }

    public function test_guest_and_regular_user_cannot_impersonate(): void
    {
        $target = User::factory()->create();
        $this->get(route('admin.users.index'))->assertRedirect(route('account.login'));
        $this->post(route('admin.impersonation.store', $target))->assertRedirect(route('account.login'));
        $this->actingAs(User::factory()->create(), 'web');
        $this->get(route('admin.users.index'))->assertForbidden();
        $this->post(route('admin.impersonation.store', $target))->assertForbidden();
        $this->delete(route('admin.impersonation.destroy'))->assertForbidden();
    }

    public function test_super_admin_cannot_switch_to_an_inactive_account(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate(User::SUPER_ADMIN_ROLE, 'web'));
        $target = User::factory()->create(['is_active' => false]);

        $this->actingAs($admin->fresh(), 'web')
            ->post(route('admin.impersonation.store', $target))->assertForbidden();
        $this->assertAuthenticatedAs($admin, 'web');
    }

    public function test_return_requires_the_original_admin_to_still_have_the_role(): void
    {
        $admin = User::factory()->create();
        $role = Role::findOrCreate(User::SUPER_ADMIN_ROLE, 'web');
        $admin->assignRole($role);
        $target = User::factory()->create();

        $this->actingAs($admin->fresh(), 'web')
            ->post(route('admin.impersonation.store', $target))->assertRedirect(route('dashboard'));
        $admin->removeRole($role);

        $this->delete(route('admin.impersonation.destroy'))->assertRedirect(route('account.login'));
        $this->assertGuest('web');
    }

    public function test_create_super_admin_command_creates_a_login_with_the_role(): void
    {
        $this->artisan('app:create-super-admin')
            ->expectsQuestion('Nama', 'Super Admin')
            ->expectsQuestion('Username', 'super-admin')
            ->expectsQuestion('Email', 'super-admin@example.com')
            ->expectsQuestion('Kata sandi (minimal 12 karakter)', 'Test-Password-1234')
            ->expectsQuestion('Ulangi kata sandi', 'Test-Password-1234')
            ->assertSuccessful();

        $this->post(route('account.login.store'), ['email' => 'super-admin@example.com', 'password' => 'Test-Password-1234'])
            ->assertRedirect(route('dashboard'));
        $this->get(route('admin.users.index'))->assertOk();
    }
}
