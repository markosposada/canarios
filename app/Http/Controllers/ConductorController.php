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

// Buscar conductor por cédula o nombres (AJAX)
public function buscarPorCedulaParaEditar(Request $request)
{
    // Soporta {q} (nuevo) o {cedula} (viejo) por compatibilidad
    $q = trim((string) ($request->input('q', $request->input('cedula', ''))));

    if ($q === '') {
        return response()->json(['encontrado' => false, 'error' => 'Consulta vacía']);
    }

    $conductorQuery = Conductor::query();

    // Si es numérico => buscar por cédula exacta
    if (preg_match('/^\d+$/', $q)) {
        $conductorQuery->where('conduc_cc', (int)$q);
    } else {
        // Si es texto => buscar por nombres (LIKE)
        // Recomendación: mínimo 2 letras
        if (mb_strlen($q) < 2) {
            return response()->json(['encontrado' => false, 'error' => 'Escribe al menos 2 letras']);
        }

        $like = '%' . $q . '%';
        $conductorQuery->where('conduc_nombres', 'like', $like)
                      ->orderBy('conduc_nombres');
    }

    // Si buscas por nombre puede haber varios; aquí tomamos el primero
    $conductor = $conductorQuery->first();

    if (!$conductor) {
        return response()->json(['encontrado' => false, 'error' => 'Conductor no encontrado']);
    }

    return response()->json([
        'encontrado' => true,
        'cedula' => $conductor->conduc_cc,
        'nombres' => $conductor->conduc_nombres,
        'licencia' => $conductor->conduc_licencia,
        'fecha' => $conductor->conduc_fecha,
    ]);
}

// LISTA para el modal (GET /conductores/licencia/buscar?q=)
public function buscarConductoresParaLicencia(Request $request)
{
    $q = trim($request->query('q', ''));

    if ($q === '' || mb_strlen($q) < 2) {
        return response()->json(['ok' => true, 'data' => []]);
    }

    $like = "%{$q}%";

    $rows = DB::table('conductores as c')
        ->where(function($w) use ($like){
            $w->where('c.conduc_nombres', 'like', $like)
              ->orWhereRaw('CAST(c.conduc_cc AS CHAR) LIKE ?', [$like]);
        })
        ->orderBy('c.conduc_nombres')
        ->limit(30)
        ->select([
            'c.conduc_cc as cedula',
            'c.conduc_nombres as nombre',
            'c.conduc_estado as estado',
        ])
        ->get();

    return response()->json(['ok' => true, 'data' => $rows]);
}

// DETALLE para llenar formulario (POST /conductores/licencia/detalle)
public function detalleConductorParaLicencia(Request $request)
{
    $cedula = trim((string)$request->input('cedula', ''));

    if ($cedula === '') {
        return response()->json(['encontrado' => false]);
    }

    $c = DB::table('conductores')
        ->where('conduc_cc', $cedula)
        ->select('conduc_cc','conduc_nombres','conduc_licencia','conduc_fecha')
        ->first();

    if (!$c) {
        return response()->json(['encontrado' => false]);
    }

    return response()->json([
        'encontrado' => true,
        'cedula' => $c->conduc_cc,
        'nombres' => $c->conduc_nombres,
        'licencia' => $c->conduc_licencia,
        'fecha' => $c->conduc_fecha,
    ]);
}


    // Guardar cambios
    public function actualizarLicencia(Request $request)
    {
        $request->validate([
            'cedula' => 'required',
            'nombres' => 'required|string|max:255',
            'licencia' => 'required|string|max:50',
            'fecha' => 'required|date'
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
    $cedula = trim($request->input('cedula', ''));

    if ($cedula === '') {
        return response()->json(['encontrado' => false]);
    }

    $conductor = DB::table('conductores')
        ->where('conduc_cc', $cedula)
        ->select('conduc_cc', 'conduc_nombres', 'conduc_estado')
        ->first();

    if (!$conductor) {
        return response()->json(['encontrado' => false]);
    }

    return response()->json([
        'encontrado' => true,
        'cedula' => $conductor->conduc_cc,
        'nombre' => $conductor->conduc_nombres,
        'estado' => (int)$conductor->conduc_estado,
    ]);
}

// =======================
// BUSCAR CONDUCTORES (MODAL)
// =======================
public function buscarConductoresAsignar(Request $request)
{
    $q = trim($request->query('q', ''));

    if ($q === '') {
        return response()->json(['ok' => true, 'data' => []]);
    }

    $like = "%{$q}%";

    $rows = DB::table('conductores as c')
        ->where(function($w) use ($like){
            $w->where('c.conduc_nombres', 'like', $like)
              ->orWhereRaw('CAST(c.conduc_cc AS CHAR) LIKE ?', [$like]);
        })
        ->orderBy('c.conduc_nombres')
        ->limit(30)
        ->select([
            'c.conduc_cc as cedula',
            'c.conduc_nombres as nombre',
            'c.conduc_estado as estado',
        ])
        ->get();

    return response()->json([
        'ok' => true,
        'data' => $rows
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
    \Log::info("== INICIO actualizarEstado por MÓVIL con mo_id: $id ==");

    $accion = $request->input('accion', 'activar') === 'desactivar' ? 'desactivar' : 'activar';

    return DB::transaction(function () use ($id, $accion) {
        // Bloqueamos el registro seleccionado
        $registro = DB::table('movil')->where('mo_id', $id)->lockForUpdate()->first();

        if (!$registro) {
            \Log::warning("No existe registro movil con mo_id=$id");
            return response()->json(['success' => false, 'message' => 'Registro no encontrado.'], 404);
        }

        $movil = $registro->mo_taxi;

        // *** Regla por MÓVIL: solo un ACTIVO por mo_taxi ***
        if ($accion === 'activar') {
            // (1) Activar el seleccionado
            DB::table('movil')
                ->where('mo_id', $id)
                ->update(['mo_estado' => 1]);

            // (2) Desactivar todos los demás del MISMO MÓVIL
            DB::table('movil')
                ->where('mo_taxi', $movil)
                ->where('mo_id', '!=', $id)
                ->where('mo_estado', 1)
                ->update(['mo_estado' => 2]);

            \Log::info("Activado mo_id=$id en mo_taxi=$movil; otros desactivados");
            return response()->json(['success' => true, 'accion' => 'activar']);
        } else {
            // Desactivar solo este registro
            DB::table('movil')
                ->where('mo_id', $id)
                ->update(['mo_estado' => 2]);

            \Log::info("Desactivado mo_id=$id en mo_taxi=$movil");
            return response()->json(['success' => true, 'accion' => 'desactivar']);
        }
    });
}





}
