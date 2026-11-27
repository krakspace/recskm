<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        return view('administrasi.daftar-role', [
            'roles' => Role::where('guard_name', 'web')->orderBy('name')->paginate(25)->withQueryString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::notIn(User::ADMIN_ROLES)],
        ], [
            'name.not_in' => 'Nama ini digunakan untuk peran Admin.',
        ]);

        $role = Role::query()->firstOrCreate(['name' => $validated['name'], 'guard_name' => 'web']);
        if (! $role->wasRecentlyCreated) {
            throw ValidationException::withMessages(['name' => 'Peran dengan nama ini sudah ada.']);
        }

        return redirect()->route('admin.roles.index', $request->query('from') === 'matrix' ? ['from' => 'matrix'] : [])
            ->with('status', 'Peran berhasil didaftarkan.');
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        DB::transaction(function () use ($role) {
            $role = Role::query()->lockForUpdate()->findOrFail($role->getKey());
            abort_unless($role->guard_name === 'web' && ! in_array($role->name, User::ADMIN_ROLES, true), 403);

            $role->delete();
        });

        return redirect()->route('admin.roles.index', $request->query('from') === 'matrix' ? ['from' => 'matrix'] : [])
            ->with('status', 'Peran berhasil dihapus.');
    }
}
