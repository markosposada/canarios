<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RolMiddleware
{
    public function handle($request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        $user = Auth::user();

        // Admin entra a todo
        if ($user->rol === 'administrador') {
            return $next($request);
        }

        if (in_array($user->rol, $roles)) {
            return $next($request);
        }

        abort(403, 'No tienes permiso para acceder a esta página.');
    }
}
