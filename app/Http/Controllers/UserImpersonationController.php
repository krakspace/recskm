<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserImpersonationController extends Controller
{
    public const SESSION_KEY = 'impersonating_admin_id';

    public function store(Request $request, string $user): RedirectResponse
    {
        $admin = $request->user('web');
        $target = User::findOrFail($user);
        abort_unless($target->is_active && ! $target->is($admin), 403);

        $guard = Auth::guard('web');
        $guard->logoutCurrentDevice();
        $guard->login($target);
        $request->session()->put(self::SESSION_KEY, $admin->id);

        Log::notice('User impersonation started.', ['admin_id' => $admin->id, 'user_id' => $target->id]);

        return redirect()->route('dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $adminId = $request->session()->pull(self::SESSION_KEY);
        abort_unless(is_string($adminId), 403);

        $userId = $request->user('web')->id;
        $admin = User::find($adminId);
        $guard = Auth::guard('web');
        $guard->logoutCurrentDevice();

        Log::notice('User impersonation ended.', ['admin_id' => $adminId, 'user_id' => $userId]);

        if (! $admin?->is_active || ! $admin->isAdministrator()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('account.login');
        }

        $guard->login($admin);

        return redirect()->route('admin.users.index');
    }
}
