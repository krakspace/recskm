<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsurePageAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $page = null): Response
    {
        abort_unless(self::allows($request, $page ?? $request->route()->getName()), 403);

        return $next($request);
    }

    public static function allows(Request $request, string $page): bool
    {
        $user = $request->user('web');
        if (! $user?->is_active) {
            return false;
        }

        return EnsureSuperAdmin::allows($request) || (self::isUserPage($page)
            && $user->getPermissionsViaRoles()->where('guard_name', 'web')->contains('name', self::permission($page)));
    }

    public static function isUserPage(string $page): bool
    {
        return ! Str::startsWith($page, ['admin.', 'administrasi.', 'audit.']);
    }

    /** @return array<string, array<string, string>> */
    public static function userPages(): array
    {
        return array_filter(array_map(
            fn (array $items): array => array_filter($items, self::isUserPage(...)),
            config('sidebar'),
        ));
    }

    public static function permission(string $page): string
    {
        return 'akses.'.$page;
    }
}
