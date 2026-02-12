<?php

namespace App\Http\Controllers\Conductor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FacturacionControllerConductor extends Controller
{
    /**
     * Vista principal
     */
    public function index()
    {
        return view('conductor.facturacion');
    }

    /**
     * Listado facturación últimos 5 días
     */
    public function listar(Request $request)
    {
        $cedula = Auth::user()->cedula;

        // últimos 5 días (incluye hoy)
        $desde = now()->subDays(5)->toDateString();

        $rows = DB::table('facturacion_operadora as fo')
            ->select([
                'fo.fo_id',
                'fo.fo_movil',
                'fo.fo_fecha',
                'fo.fo_hora',
                DB::raw('LEFT(COALESCE(fo.fo_operadora, ""), 12) as operadora'),
                'fo.fo_total',
                'fo.fo_pagado',
                'fo.fo_pagado_at',
                DB::raw('LEFT(COALESCE(fo.fo_pagado_operadora, ""), 12) as pagado_por'),
                'fo.fo_metodo',
                'fo.fo_observacion',
            ])
            ->where('fo.fo_conductor', $cedula)
            ->whereDate('fo.fo_fecha', '>=', $desde)
            ->orderByDesc('fo.fo_fecha')
            ->orderByDesc('fo.fo_hora')
            ->get();

        // Totales resumen
        $totalRegistros = $rows->count();
        $totalFacturado = (int) $rows->sum('fo_total');
        $totalPagado    = (int) $rows->where('fo_pagado', 1)->sum('fo_total');
        $totalDebe      = (int) $rows->where('fo_pagado', 0)->sum('fo_total');

        return response()->json([
            'data' => $rows,
            'totales' => [
                'registros' => $totalRegistros,
                'facturado' => $totalFacturado,
                'pagado'    => $totalPagado,
                'debe'      => $totalDebe,
            ]
        ]);
    }
}
