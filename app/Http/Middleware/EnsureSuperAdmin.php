<?php

namespace App\Http\Middleware;

use App\Http\Controllers\UserImpersonationController;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(self::allows($request), 403);

        return $next($request);
    }

    public static function allows(Request $request): bool
    {
        $user = $request->user('web');

        return $user?->is_active
            && $user->hasRole(User::SUPER_ADMIN_ROLE, 'web')
            && ! $request->session()->has(UserImpersonationController::SESSION_KEY);
    }
}
