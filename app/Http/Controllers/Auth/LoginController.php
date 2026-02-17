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

        // ✅ aquí se define
        $remember = $request->boolean('remember');

        // ✅ fuerza el guard web y usa remember
        if (Auth::guard('web')->attempt([
            'cedula'   => $credentials['cedula'],
            'password' => $credentials['password'],
            
        ], $remember)) {

            $request->session()->regenerate();
            //dd('LOGUEADO', Auth::check(), Auth::user()->rol, $this->redirectTo());


            return redirect($this->redirectTo());

        }

        return back()
            ->with('login_error', 'Cédula o contraseña incorrecta.')
            ->withInput($request->only('cedula'));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

  protected function redirectTo()
{
    $rol = strtolower(trim(Auth::user()->rol ?? ''));

    return match ($rol) {
        'administrador', 'operadora' => '/dashboard',
        'conductor' => '/modulo-conductor',
        'propietario taxi' => '/modulo-propietario',
        default => '/login',
    };
}

}
