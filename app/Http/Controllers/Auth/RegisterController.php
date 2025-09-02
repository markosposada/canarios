<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RegisterController extends Controller
{
    /**
     * Muestra el formulario de registro.
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    /**
     * Procesa el registro de un nuevo usuario.
     */
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'nombres' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'cedula' => 'required|string|max:20|unique:users',
            'celular' => 'required|string|max:20',
            'email' => 'required|string|email|max:255|unique:users',
            'rol' => 'required|in:conductor,operadora,administrador',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'nombres.required' => 'El campo nombres es obligatorio.',
            'apellidos.required' => 'El campo apellidos es obligatorio.',
            'cedula.required' => 'El campo cédula es obligatorio.',
            'cedula.unique' => 'Esta cédula ya está registrada.',
            'celular.required' => 'El campo celular es obligatorio.',
            'email.required' => 'El campo correo electrónico es obligatorio.',
            'email.unique' => 'El correo electrónico ya está registrado, por favor ingresa otro.',
            'rol.required' => 'Debe seleccionar un rol.',
            'password.required' => 'El campo contraseña es obligatorio.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ]);

        try {
            $validatedData['name'] = $validatedData['nombres'] . ' ' . $validatedData['apellidos'];
            $validatedData['password'] = Hash::make($validatedData['password']);
            User::create($validatedData);

            // Redirige de nuevo al formulario con mensaje de éxito
            return redirect()->route('register')->with('success', '¡Usuario registrado exitosamente!');
            
        } catch (\Exception $e) {
            Log::error('Error al registrar usuario: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Error al registrar usuario: ' . $e->getMessage());
        }
    }
}
