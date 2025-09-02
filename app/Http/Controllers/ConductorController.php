<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Conductor;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ConductorController extends Controller
{
    // Mostrar vista para editar licencia
    public function editLicencia()
    {
        return view('conductores.editar-licencia');
    }

    // Buscar conductor por cédula (AJAX)
    public function buscarPorCedulaParaEditar(Request $request)
    {
        $cedula = (int) $request->input('cedula');
        \Log::info('Buscando conductor con cédula: ' . $cedula);

        $conductor = Conductor::where('conduc_cc', $cedula)->first();

        if (!$conductor) {
            \Log::warning('Conductor no encontrado para cédula: ' . $cedula);
            return response()->json(['encontrado' => false]);
        }

        return response()->json([
            'encontrado' => true,
            'nombres' => $conductor->conduc_nombres,
            'licencia' => $conductor->conduc_licencia,
            'fecha' => $conductor->conduc_fecha
        ]);
    }

    // Guardar cambios
    public function actualizarLicencia(Request $request)
    {
        $request->validate([
            'cedula' => 'required',
            'nombres' => 'required|string|max:255',
            'licencia' => 'required|string|max:50',
            'fecha' => 'required|date|after_or_equal:' . now()->toDateString()
        ]);

        $conductor = Conductor::where('conduc_cc', $request->cedula)->first();

        if (!$conductor) {
            return redirect()->back()->with('error', 'Conductor no encontrado.');
        }

        $conductor->conduc_nombres = $request->nombres;
        $conductor->conduc_licencia = $request->licencia;
        $conductor->conduc_fecha = $request->fecha;
        $conductor->save();

        return redirect()->back()->with('success', 'Licencia actualizada correctamente.');
    }

    // Mostrar vista con móviles disponibles
    public function asignar()
    {
        $moviles = DB::table('taxi')
            ->whereIn('ta_estado', ['A', 'I'])
            ->select('ta_movil')
            ->get();

        return view('conductores.asignar', compact('moviles'));
    }

    // Buscar conductor por cédula
    public function buscarDatosConductor(Request $request)
    {
        $cedula = $request->input('cedula');
        $conductor = DB::table('conductores')->where('conduc_cc', $cedula)->first();

        if (!$conductor) {
            return response()->json(['encontrado' => false]);
        }

        return response()->json([
            'encontrado' => true,
            'nombre' => $conductor->conduc_nombres
        ]);
    }

    // Guardar asignación en tabla movil
    public function guardarAsignacion(Request $request)
    {
        $request->validate([
            'cedula' => 'required',
            'movil' => 'required'
        ]);

        DB::table('movil')->insert([
            'mo_taxi' => $request->movil,
            'mo_conductor' => $request->cedula,
            'mo_estado' => 2
        ]);

        return redirect()->back()->with('success', 'Conductor asignado correctamente.');
    }

    public function panelConductores()
{
        return view('conductores.panel');

}

public function buscarMovilAjax(Request $request)
{
    $movil = $request->input('movil');

    $resultados = DB::table('movil')
        ->join('conductores', 'movil.mo_conductor', '=', 'conductores.conduc_cc')
        ->select('movil.mo_id', 'movil.mo_taxi', 'conductores.conduc_nombres', 'movil.mo_estado')
        ->where('movil.mo_taxi', '=', $movil)
        
        ->get();

    return response()->json($resultados);
}

public function actualizarEstado(Request $request, $id)
{
    \Log::info("== INICIO actualizarEstado estilo JAVA con mo_id: $id ==");

    $registro = DB::table('movil')->where('mo_id', $id)->first();

    if (!$registro) {
        return response()->json(['success' => false]);
    }

    $carro = $registro->mo_taxi;

    // 1. Activar el registro seleccionado (si pertenece al móvil)
    DB::table('movil')
        ->where('mo_id', $id)
        ->where('mo_taxi', $carro)
        ->update(['mo_estado' => 1]);

    // 2. Desactivar todos los demás activos de ese móvil
    DB::table('movil')
        ->where('mo_taxi', $carro)
        ->where('mo_id', '!=', $id)
        ->where('mo_estado', 1)
        ->update(['mo_estado' => 2]);

    \Log::info("Activado mo_id=$id en mo_taxi=$carro y desactivados los demás");

    return response()->json(['success' => true]);
}



}
