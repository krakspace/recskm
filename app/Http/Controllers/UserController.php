<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('administrasi.pengguna', [
            'users' => User::with('roles')->orderBy('name')->orderBy('id')->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('administrasi.form-pengguna', ['user' => new User(['is_active' => true])]);
    }

    public function store(SaveUserRequest $request): RedirectResponse
    {
        User::create($request->validated());

        return redirect()->route('admin.users.index')->with('status', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(User $user): View
    {
        return view('administrasi.form-pengguna', [
            'user' => $user->load('roles'),
            'roles' => Role::where('guard_name', 'web')->orderBy('name')->get(),
        ]);
    }

    public function update(SaveUserRequest $request, User $user): RedirectResponse
    {
        if ($user->is($request->user('web')) && ! $request->boolean('is_active')) {
            throw ValidationException::withMessages(['is_active' => 'Akun yang sedang digunakan harus tetap aktif.']);
        }

        $attributes = $request->validated();
        if (($attributes['password'] ?? null) === null) {
            unset($attributes['password']);
        }
        $roleIds = isset($attributes['role_ids']) ? array_filter($attributes['role_ids']) : null;
        unset($attributes['role_ids']);
        $editingOwnAccount = $user->is($request->user('web'));

        DB::transaction(function () use ($user, $attributes, $roleIds, $editingOwnAccount) {
            $user = User::query()->lockForUpdate()->findOrFail($user->getKey());
            $user->update($attributes);

            if ($roleIds === null) {
                return;
            }

            $roles = Role::where('guard_name', 'web')->whereKey(array_values($roleIds))->get();
            if ($roles->count() !== count($roleIds)) {
                throw ValidationException::withMessages(['role_ids' => 'Pilih peran yang tersedia untuk akun utama.']);
            }
            if ($editingOwnAccount && ! $roles->contains('name', User::SUPER_ADMIN_ROLE)) {
                throw ValidationException::withMessages(['role_ids' => 'Peran Super Admin harus tetap terpasang pada akun yang sedang digunakan.']);
            }

            $user->syncRoles($roles->merge($user->roles->where('guard_name', '!=', 'web')));
        });

        return redirect()->route('admin.users.index')->with('status', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($user->is($request->user('web')), 403);

        DB::transaction(fn () => $user->delete());

        return redirect()->route('admin.users.index')->with('status', 'Pengguna berhasil dihapus.');
    }
}
