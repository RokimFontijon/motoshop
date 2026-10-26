<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Allows a route only for users whose role is in the list.
 * Use it in routes/web.php like this:  ->middleware('role:admin')
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role->name, $roles, true)) {
            abort(403, 'You do not have permission to do that.');
        }

        return $next($request);
    }
}
