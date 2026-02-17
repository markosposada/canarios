<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RolMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        $userRol = strtolower(trim(Auth::user()->rol));
        $rolesPermitidos = array_map(fn($r) => strtolower(trim($r)), $roles);

        // Administrador entra a todo
        if ($userRol === 'administrador') {
            return $next($request);
        }

        if (in_array($userRol, $rolesPermitidos)) {
            return $next($request);
        }

        // Redirección automática según rol
        return match ($userRol) {
            'operadora' => redirect('/dashboard'),
            'conductor' => redirect('/modulo-conductor'),
            'propietario taxi' => redirect('/modulo-propietario'),
            default => redirect('/login'),
        };
    }
}
