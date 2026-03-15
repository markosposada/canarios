<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        if (auth()->check()) {
            return redirect($this->redirectTo());
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'cedula'   => ['required', 'digits_between:6,15'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::guard('web')->attempt([
            'cedula'   => $credentials['cedula'],
            'password' => $credentials['password'],
        ], $remember)) {

            $request->session()->regenerate();

            $user = Auth::user();

            if ($this->debeElegirModo($user)) {
                return redirect()->route('seleccionar.acceso');
            }

            return redirect($this->redirectTo());
        }

        return back()
            ->with('login_error', 'Cédula o contraseña incorrecta.')
            ->withInput($request->only('cedula'));
    }

    public function logout(Request $request)
    {
        session()->forget('modo_ingreso');

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    public function seleccionarAcceso()
    {
        $user = Auth::user();

        if (!$this->debeElegirModo($user)) {
            return redirect($this->redirectTo());
        }

        return view('auth.seleccionar-acceso');
    }

    public function guardarAcceso(Request $request)
    {
        $request->validate([
            'modo_ingreso' => ['required', 'in:administrador,conductor'],
        ]);

        $user = Auth::user();

        if (!$this->debeElegirModo($user)) {
            return redirect($this->redirectTo());
        }

        session(['modo_ingreso' => $request->modo_ingreso]);

        return redirect($this->redirectTo());
    }

    protected function redirectTo()
    {
        $user = Auth::user();
        $rol = strtolower(trim($user->rol ?? ''));
        $modoIngreso = session('modo_ingreso');

        if ($modoIngreso === 'conductor') {
            return '/modulo-conductor';
        }

        return match ($rol) {
            'administrador', 'operadora' => '/dashboard',
            'conductor' => '/modulo-conductor',
            'propietario taxi' => '/modulo-propietario',
            default => '/login',
        };
    }

    protected function debeElegirModo($user): bool
    {
        $rol = strtolower(trim($user->rol ?? ''));

        return $rol === 'administrador';
    }
}