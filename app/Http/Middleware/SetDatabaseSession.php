<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tells PostgreSQL who is logged in, on every request.
 *
 * It sets two session variables (app.user_id and app.role). The row level
 * security policies on job_orders (database/sql/01_db_objects.sql) read them,
 * so the database itself refuses a mechanic who tries to change a job order
 * that belongs to someone else, even if the PHP code had a bug.
 */
class SetDatabaseSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && DB::getDriverName() === 'pgsql') {
            DB::select(
                "select set_config('app.user_id', ?, false), set_config('app.role', ?, false)",
                [(string) $user->id, $user->role->name]
            );
        }

        return $next($request);
    }
}
