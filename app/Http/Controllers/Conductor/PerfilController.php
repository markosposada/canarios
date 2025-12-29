<?php

namespace App\Http\Controllers\Conductor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PerfilController extends Controller
{
    public function edit()
    {
        $cedula = auth()->user()->cedula;

        $conductor = DB::table('conductores')
            ->where('conduc_cc', $cedula)
            ->select('conduc_cc','conduc_nombres','conduc_estado','conduc_licencia','conduc_fecha','conduc_cel')
            ->first();

        if (!$conductor) {
            abort(404, 'No se encontró el conductor asociado a tu usuario.');
        }

        return view('conductor.perfil', compact('conductor'));
    }

   public function update(Request $request)
{
    $cedula = auth()->user()->cedula;

    $request->validate([
        'conduc_nombres'  => 'required|string|max:255',
        'conduc_licencia' => 'nullable|integer|min:0',
        'conduc_fecha'    => 'nullable|date',

        // 👇 SOLO NÚMEROS
        'conduc_cel'      => ['nullable','regex:/^[0-9]+$/','max:10'],
    ], [
        'conduc_cel.regex' => 'El número de celular solo debe contener números.',
    ]);

    // Sanitizar por si viene con espacios (defensa extra)
    $celular = $request->conduc_cel
        ? preg_replace('/[^0-9]/', '', $request->conduc_cel)
        : null;

    DB::table('conductores')
        ->where('conduc_cc', $cedula)
        ->update([
            'conduc_nombres'  => trim($request->conduc_nombres),
            'conduc_licencia' => $request->conduc_licencia ?: 0,
            'conduc_fecha'    => $request->conduc_fecha,
            'conduc_cel'      => $celular,
        ]);

    return redirect()
        ->route('conductor.perfil.edit')
        ->with('ok', '✅ Datos actualizados correctamente.');
}

}
