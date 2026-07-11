<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        abort_unless($request->user() && $request->user()->isActive(), 403);
        abort_unless(collect($roles)->contains(fn (string $role) => $request->user()->hasRole($role)), 403);
        return $next($request);
    }
}
