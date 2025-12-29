<?php

namespace App\Http\Controllers\Operadora;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RecaudoHistorialController extends Controller
{
    public function vista()
    {
        return view('operadora.recaudado_historial');
    }

    public function listar(Request $request)
    {
        $operadora = Auth::user()->name ?? Auth::user()->email ?? 'OPERADORA';

        $desde = $request->query('desde'); // YYYY-MM-DD
        $hasta = $request->query('hasta'); // YYYY-MM-DD
        $movil = trim($request->query('movil', ''));
        $q     = trim($request->query('q', ''));

        $sql = DB::table('facturacion_operadora as fo')
            ->leftJoin('conductores as c', 'c.conduc_cc', '=', 'fo.fo_conductor')
            ->where('fo.fo_pagado', 1)
            ->where('fo.fo_pagado_operadora', $operadora)
            ->select([
                'fo.fo_id',
                'fo.fo_movil',
                'fo.fo_conductor',
                'c.conduc_nombres',
                'fo.fo_fecha',
                'fo.fo_hora',
                'fo.fo_total_servicios',
                'fo.fo_total_sanciones',
                'fo.fo_total',
                'fo.fo_pagado_at',
                'fo.fo_metodo',
                'fo.fo_observacion',
            ]);

        if ($desde) $sql->whereDate('fo.fo_pagado_at', '>=', $desde);
        if ($hasta) $sql->whereDate('fo.fo_pagado_at', '<=', $hasta);

        if ($movil !== '') {
            $sql->where('fo.fo_movil', (int)$movil);
        }

        if ($q !== '') {
            $like = "%{$q}%";
            $sql->where(function($w) use ($like){
                $w->where('c.conduc_nombres', 'like', $like)
                  ->orWhere('fo.fo_conductor', 'like', $like)
                  ->orWhereRaw('CAST(fo.fo_movil AS CHAR) LIKE ?', [$like])
                  ->orWhereRaw('CAST(fo.fo_id AS CHAR) LIKE ?', [$like])
                  ->orWhere('fo.fo_metodo', 'like', $like);
            });
        }

        $rows = $sql->orderByDesc('fo.fo_pagado_at')
                    ->limit(1000)
                    ->get();

        // ✅ Totales estilo KPI
        $totales = [
            'cantidad' => $rows->count(),
            'total_servicios' => (int) $rows->sum('fo_total_servicios'),
            'total_sanciones' => (int) $rows->sum('fo_total_sanciones'),
            'total' => (int) $rows->sum('fo_total'),
        ];

        return response()->json([
            'ok' => true,
            'data' => $rows,
            'total' => $rows->count(),
            'totales' => $totales,
        ]);
    }
}
