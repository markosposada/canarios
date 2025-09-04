<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConductorServiciosController extends Controller
{
    /** Vista principal (tabla) */
    public function vista()
    {
        // Si usas roles y quieres restringir explícitamente:
        // abort_unless(auth()->user()?->rol === 'conductor', 403, 'Acceso solo para conductores.');
        return view('conductor.servicios');
    }

    /** Datos (AJAX) de servicios del conductor autenticado */
    public function listar(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json([], 401);
        }

        // El conductor se identifica por la cédula del usuario
        $cedulaConductor = $user->cedula;

        $desde = $request->query('desde'); // YYYY-MM-DD
        $hasta = $request->query('hasta'); // YYYY-MM-DD
        $q     = trim($request->query('q', ''));

        $sql = DB::table('disponibles')
            ->join('movil', 'disponibles.dis_conmo', '=', 'movil.mo_id')
            ->leftJoin('taxi', 'taxi.ta_movil', '=', 'movil.mo_taxi')
            // Solo servicios del conductor logueado
            ->where('movil.mo_conductor', '=', $cedulaConductor)
            // Opcional: solo activos (1). Si quieres ver cancelados (2) también, elimina esta línea.
            ->whereIn('disponibles.dis_servicio', [1,2])
            ->selectRaw("
    disponibles.dis_id       AS id,
    disponibles.dis_fecha    AS fecha,
    disponibles.dis_hora     AS hora,
    disponibles.dis_dire     AS direccion,
    movil.mo_taxi            AS movil,
    COALESCE(taxi.ta_placa,'') AS placa,
    disponibles.dis_servicio AS estado
")
;

        if ($desde) { $sql->where('disponibles.dis_fecha', '>=', $desde); }
        if ($hasta) { $sql->where('disponibles.dis_fecha', '<=', $hasta); }

        if ($q !== '') {
            $like = "%{$q}%";
            $sql->where(function ($w) use ($like) {
                $w->where('disponibles.dis_dire', 'like', $like)
                  ->orWhereRaw('CAST(movil.mo_taxi AS CHAR) LIKE ?', [$like])
                  ->orWhere('taxi.ta_placa', 'like', $like);
            });
        }

        $rows = $sql->orderByDesc('disponibles.dis_fecha')
                    ->orderByDesc('disponibles.dis_hora')
                    ->limit(300)
                    ->get();

        return response()->json($rows);
    }
}
