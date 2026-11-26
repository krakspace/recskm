<?php

namespace App\Http\Middleware;

use App\Services\SimulationDatabase;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UseSimulationDatabase
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(app()->environment('local', 'testing'), 404);
        abort_unless(is_file(database_path('sop-simulation.sqlite')), 503, 'Jalankan php artisan sop:simulate terlebih dahulu.');

        return SimulationDatabase::run(function () use ($request, $next) {
            $guard = Auth::guard('simulation');
            try {
                if (! $request->routeIs('sim.login') && ! $guard->user()?->is_active) {
                    $guard->logout();

                    return redirect()->route('login');
                }

                return $next($request);
            } finally {
                $guard->forgetUser();
            }
        });
    }
}
