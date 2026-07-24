<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserType
{
    public function handle(Request $request, Closure $next, string ...$types): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        foreach ($types as $type) {
            if ($type === 'admin' && $user->isAdmin()) {
                return $next($request);
            }
            if ($type === 'agency' && $user->isAgency()) {
                return $next($request);
            }
            if ($type === 'super_admin' && $user->isSuperAdmin()) {
                return $next($request);
            }
        }

        abort(403, 'ليس لديك صلاحية للوصول لهذا المورد');
    }
}
