<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsurePageAccess;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RolePagePermissionController extends Controller
{
    public function index(Request $request): View
    {
        $roles = Role::where('guard_name', 'web')->whereNotIn('name', User::ADMIN_ROLES)->orderBy('name')->get();
        $roleId = $request->query('role');
        $selectedRole = is_string($roleId) && Str::isUuid($roleId)
            ? $roles->firstWhere('uuid', $roleId)
            : null;
        $selectedRole ??= $roles->first();

        return view('administrasi.matriks-izin', [
            'roles' => $roles,
            'selectedRole' => $selectedRole,
            'groups' => EnsurePageAccess::userPages(),
            'selectedPermissions' => $selectedRole?->permissions->where('guard_name', 'web')->pluck('name')->all() ?? [],
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        abort_unless($role->guard_name === 'web' && ! in_array($role->name, User::ADMIN_ROLES, true), 403);
        $pages = Arr::flatten(EnsurePageAccess::userPages());
        $validated = $request->validate([
            'pages' => ['sometimes', 'array', 'list'],
            'pages.*' => ['required', 'string', 'distinct', Rule::in($pages)],
        ]);

        DB::transaction(function () use ($role, $pages, $validated) {
            $role = Role::query()->lockForUpdate()->findOrFail($role->getKey());
            $managedPermissions = array_map(EnsurePageAccess::permission(...), $pages);
            $permissions = $role->permissions->reject(fn (Permission $permission): bool => $permission->guard_name === 'web'
                && in_array($permission->name, $managedPermissions, true));

            foreach ($validated['pages'] ?? [] as $page) {
                $permissions->push(Permission::query()->firstOrCreate([
                    'name' => EnsurePageAccess::permission($page),
                    'guard_name' => 'web',
                ]));
            }

            $role->syncPermissions($permissions);
        });

        return redirect()->route('admin.users.permissions.index', ['role' => $role->uuid])->with('status', 'Izin akses peran berhasil diperbarui.');
    }
}
