<?php

namespace App\Http\Controllers\Operadora;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RecaudoController extends Controller
{
    public function vista()
    {
        return view('operadora.recaudado');
    }

    /**
     * Pendientes por móvil (mo_taxi)
     * ✅ NO requiere mo_estado=1
     * ✅ Evita mezclar histórico: conductor se resuelve desde facturas pendientes si existen
     */
    public function pendientes(Request $request)
    {
        $movilTaxi = (int) trim($request->query('movil', ''));
        if ($movilTaxi <= 0) {
            return response()->json(['ok' => false, 'message' => 'Debes ingresar el número de móvil.'], 422);
        }

        // 1) Si hay facturas pendientes, el conductor correcto está en facturacion_operadora
        $cc = DB::table('facturacion_operadora')
            ->where('fo_movil', $movilTaxi)
            ->where('fo_pagado', 0)
            ->orderByDesc('fo_id')
            ->value('fo_conductor');

        // 2) Si NO hay facturas pendientes, caemos al último conductor registrado en movil
        if (!$cc) {
            $cc = DB::table('movil')
                ->where('mo_taxi', $movilTaxi)
                ->orderByDesc('mo_id')
                ->value('mo_conductor');
        }

        if (!$cc) {
            return response()->json(['ok' => false, 'message' => 'No se pudo determinar el conductor de ese móvil.'], 404);
        }

        // Nombre / estado conductor
        $conductor = DB::table('conductores')
            ->where('conduc_cc', $cc)
            ->select('conduc_cc', 'conduc_nombres', 'conduc_estado')
            ->first();

        if (!$conductor) {
            return response()->json(['ok' => false, 'message' => 'Conductor no encontrado.'], 404);
        }

        // Facturas pendientes SOLO de ese conductor + ese móvil
        $facturas = DB::table('facturacion_operadora as fo')
            ->where('fo.fo_movil', $movilTaxi)
            ->where('fo.fo_conductor', $cc)
            ->where('fo.fo_pagado', 0)
            ->orderByDesc('fo.fo_id')
            ->select([
                'fo.fo_id',
                'fo.fo_fecha',
                'fo.fo_hora',
                'fo.fo_operadora',
                'fo.fo_total_servicios',
                'fo.fo_total_sanciones',
                'fo.fo_total',
            ])
            ->get();

        $totalPendiente = (int) $facturas->sum('fo_total');

        return response()->json([
            'ok' => true,
            'movil' => [
                'mo_taxi' => $movilTaxi,
                'conductor_cc' => $conductor->conduc_cc,
                'conductor_nombre' => $conductor->conduc_nombres,
                'conductor_estado' => (int)$conductor->conduc_estado,
            ],
            'facturas' => $facturas,
            'total_pendiente' => $totalPendiente,
        ]);
    }

    /**
     * Pendientes por cédula (modal)
     * ✅ NO requiere mo_estado=1
     * ✅ Facturas se consultan por cédula (y si quieres, mostramos un mo_taxi "referencia")
     */
    public function pendientesPorCedula(Request $request)
    {
        $cc = trim($request->query('cedula', ''));
        if ($cc === '') {
            return response()->json(['ok' => false, 'message' => 'Debes ingresar la cédula.'], 422);
        }

        $conductor = DB::table('conductores')
            ->where('conduc_cc', $cc)
            ->select('conduc_cc', 'conduc_nombres', 'conduc_estado')
            ->first();

        if (!$conductor) {
            return response()->json(['ok' => false, 'message' => 'Conductor no encontrado.'], 404);
        }

        // Móvil de referencia: el último mo_taxi que tuvo (solo para mostrar)
        $movilTaxi = DB::table('movil')
            ->where('mo_conductor', $cc)
            ->orderByDesc('mo_id')
            ->value('mo_taxi');

        // Facturas pendientes por cédula (pueden existir incluso si no hay móvil activo)
        $facturas = DB::table('facturacion_operadora as fo')
            ->where('fo.fo_conductor', $cc)
            ->where('fo.fo_pagado', 0)
            ->orderByDesc('fo.fo_id')
            ->select([
                'fo.fo_id',
                'fo.fo_fecha',
                'fo.fo_hora',
                'fo.fo_operadora',
                'fo.fo_total_servicios',
                'fo.fo_total_sanciones',
                'fo.fo_total',
                'fo.fo_movil',
            ])
            ->get();

        $totalPendiente = (int) $facturas->sum('fo_total');

        return response()->json([
            'ok' => true,
            'movil' => [
                'mo_taxi' => $movilTaxi ?? '—',
                'conductor_cc' => $conductor->conduc_cc,
                'conductor_nombre' => $conductor->conduc_nombres,
                'conductor_estado' => (int)$conductor->conduc_estado,
            ],
            'facturas' => $facturas,
            'total_pendiente' => $totalPendiente,
        ]);
    }

    /**
     * Buscar conductores por nombre o cédula (para modal)
     * ✅ NO requiere mo_estado=1
     */
    public function buscarConductores(Request $request)
    {
        $q = trim($request->query('q', ''));
        if ($q === '') {
            return response()->json(['ok' => true, 'data' => []]);
        }

        $like = "%{$q}%";

        // Buscar conductor (y mostramos un mo_taxi de referencia si existe)
        $rows = DB::table('conductores as c')
            ->leftJoin('movil as m', 'm.mo_conductor', '=', 'c.conduc_cc')
            ->where(function ($w) use ($like) {
                $w->where('c.conduc_nombres', 'like', $like)
                  ->orWhereRaw('CAST(c.conduc_cc AS CHAR) LIKE ?', [$like]);
            })
            ->groupBy('c.conduc_cc', 'c.conduc_nombres')
            ->orderBy('c.conduc_nombres')
            ->limit(20)
            ->selectRaw("
                c.conduc_cc as cedula,
                c.conduc_nombres as nombre,
                MAX(m.mo_id) as last_mo_id
            ")
            ->get();

        // Obtener mo_taxi de referencia por cada conductor (usando last_mo_id)
        $data = [];
        foreach ($rows as $r) {
            $movil = null;
            if (!empty($r->last_mo_id)) {
                $movil = DB::table('movil')->where('mo_id', $r->last_mo_id)->value('mo_taxi');
            }
            $data[] = [
                'cedula' => $r->cedula,
                'nombre' => $r->nombre,
                'movil'  => $movil ?? '—',
            ];
        }

        return response()->json([
            'ok' => true,
            'data' => $data
        ]);
    }

    /**
     * Pagar una o varias facturas
     */
    public function pagar(Request $request)
    {
        $request->validate([
            'facturas' => 'required|array|min:1',
            'facturas.*' => 'integer|min:1',
            'metodo' => 'nullable|string|max:50',
            'observacion' => 'nullable|string|max:255',
        ]);

        $ids = array_values(array_unique($request->facturas));
        $metodo = $request->input('metodo', 'EFECTIVO');
        $observacion = $request->input('observacion', null);

        $now = Carbon::now('America/Bogota');
        $operadora = Auth::user()->name ?? Auth::user()->email ?? 'OPERADORA';

        return DB::transaction(function () use ($ids, $metodo, $observacion, $now, $operadora) {

            $rows = DB::table('facturacion_operadora')
                ->whereIn('fo_id', $ids)
                ->lockForUpdate()
                ->get();

            if ($rows->count() === 0) {
                return response()->json(['ok' => false, 'message' => 'No se encontraron facturas.'], 404);
            }

            $pendientes = $rows->where('fo_pagado', 0)->pluck('fo_id')->values();

            if ($pendientes->count() === 0) {
                return response()->json(['ok' => false, 'message' => 'Esas facturas ya estaban pagadas.'], 422);
            }

            $total = (int) $rows->whereIn('fo_id', $pendientes)->sum('fo_total');

            DB::table('facturacion_operadora')
                ->whereIn('fo_id', $pendientes->toArray())
                ->update([
                    'fo_pagado' => 1,
                    'fo_pagado_at' => $now->format('Y-m-d H:i:s'),
                    'fo_pagado_operadora' => $operadora,
                    'fo_metodo' => $metodo,
                    'fo_observacion' => $observacion,
                ]);

            return response()->json([
                'ok' => true,
                'pagadas' => $pendientes,
                'total_pagado' => $total,
            ]);
        });
    }
    public function pendientesGlobal(Request $request)
{
    $q = trim($request->query('q', ''));

    $sql = DB::table('facturacion_operadora as fo')
        ->join('conductores as c', 'c.conduc_cc', '=', 'fo.fo_conductor')
        ->where('fo.fo_pagado', 0)
        ->select([
            'fo.fo_id',
            'fo.fo_movil',
            'fo.fo_conductor',
            'c.conduc_nombres as conductor_nombre',
            'fo.fo_fecha',
            'fo.fo_hora',
            'fo.fo_operadora',
            'fo.fo_total_servicios',
            'fo.fo_total_sanciones',
            'fo.fo_total',
        ])
        ->orderByDesc('fo.fo_id');

    if ($q !== '') {
        $like = "%{$q}%";
        $sql->where(function($w) use ($like) {
            $w->whereRaw('CAST(fo.fo_movil AS CHAR) LIKE ?', [$like])
              ->orWhere('fo.fo_conductor', 'like', $like)
              ->orWhere('c.conduc_nombres', 'like', $like)
              ->orWhereRaw('CAST(fo.fo_id AS CHAR) LIKE ?', [$like]);
        });
    }

    $rows = $sql->limit(500)->get();

    return response()->json([
        'ok' => true,
        'data' => $rows,
        'totales' => [
            'cantidad' => $rows->count(),
            'total' => (int) $rows->sum('fo_total'),
            'total_servicios' => (int) $rows->sum('fo_total_servicios'),
            'total_sanciones' => (int) $rows->sum('fo_total_sanciones'),
        ],
    ]);
}
public function vistaPendientesGlobal()
{
    return view('operadora.facturas_pendientes');
}


}
