<?php
/**
 * Senior note:
 * - Variadic roles allow flexible route protection (role:vendor,admin).
 * - Keep failures explicit and consistent (403 Forbidden).
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();
        if (!$user || !in_array($user->role, $roles, true)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        return $next($request);
    }
}
