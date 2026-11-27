<?php

namespace App\Http\Controllers;

use App\Services\SimulationDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AccountSessionController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]) + ['is_active' => true];

        $web = Auth::guard('web');
        $simulation = Auth::guard('simulation');
        $session = $request->session();

        if ($web->attempt($credentials)) {
            $session->forget([$simulation->getName(), UserImpersonationController::SESSION_KEY]);
            $simulation->forgetUser();

            return redirect()->intended(route('dashboard'));
        }

        if (app()->environment('local', 'testing')
            && is_file(database_path('sop-simulation.sqlite'))
            && $web->getProvider()->retrieveByCredentials(['email' => $credentials['email']]) === null
            && $this->attemptSimulation($credentials)) {
            $web->logoutCurrentDevice();
            $session->forget(['url.intended', UserImpersonationController::SESSION_KEY]);

            return redirect()->route('sim.index');
        }

        throw ValidationException::withMessages(['email' => 'Email atau kata sandi tidak cocok dengan akun aktif.']);
    }

    /** @param array{email: string, password: string, is_active: bool} $credentials */
    private function attemptSimulation(array $credentials): bool
    {
        return SimulationDatabase::run(function () use ($credentials): bool {
            $guard = Auth::guard('simulation');

            try {
                return $guard->attempt($credentials);
            } finally {
                $guard->forgetUser();
            }
        });
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logoutCurrentDevice();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('account.login');
    }
}
