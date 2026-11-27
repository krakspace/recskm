<?php

namespace App\Providers;

use App\Http\Middleware\EnsureSuperAdmin;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function (User $user): ?bool {
            $request = request();

            if ($request->hasSession() && ! $request->routeIs('sim.*')
                && $user->is($request->user('web')) && EnsureSuperAdmin::allows($request)) {
                return true;
            }

            return null;
        });
    }
}
