<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Muestra el formulario de inicio de sesión.
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Maneja la petición de inicio de sesión.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {
        // 1. Validar los datos del formulario
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // 2. Intentar autenticar al usuario
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            
                session()->flash('login_success', '¡Bienvenido de nuevo, ' . Auth::user()->nombre . '!');

            // 3. Si es exitoso, redirigir a una ruta protegida (ej. 'dashboard')
            return redirect()->intended($this->redirectTo());
        }

        // 4. Si falla, redirigir de vuelta con un error de validación.
        return back()->with('login_error', 'Datos incorrectos. Verifique nuevamente sus credenciales.');

    }

    /**
     * Redirige según el rol del usuario.
     *
     * @return string
     */
    protected function redirectTo()
    {
        // Redirige según el rol del usuario
        switch (Auth::user()->rol) {
            case 'administrador':
                return '/dashboard';
            case 'operadora':
                return '/dashboard';
            case 'conductor':
                return '/modulo-conductor';
            case 'propietario taxi':
                return '/modulo-propietario';
            default:
                return '/';
        }
    }
}

