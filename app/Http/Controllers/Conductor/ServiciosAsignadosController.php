<?php

namespace App\Http\Controllers\Conductor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ServiciosAsignadosController extends Controller
{
    /**
     * Vista principal
     */
    public function index()
    {
        return view('conductor.servicios_asignados');

    }

    /**
     * Listado de servicios asignados al conductor (AJAX)
     */
    public function listar(Request $request)
    {
        // 🔑 CÉDULA del usuario logueado
        $cedula = Auth::user()->cedula;

        $rows = DB::table('facturacion as f')
            ->join('movil as m', 'm.mo_id', '=', 'f.fac_condu')
            ->join('estpago as ep', 'ep.ep_id', '=', 'f.fac_pago')
            ->select([
                'f.fac_id',
                'f.fac_movil',
                'f.fac_direc',
                'f.fac_fecha',
                'f.fac_hora',
                'f.fac_operadora',
                'ep.ep_name as pago',
            ])
            // Igual que tu sistema viejo
            ->where('f.fac_pago', 1)
            // 👇 FILTRO REAL POR CONDUCTOR
            ->where('m.mo_conductor', $cedula)
            ->orderByDesc('f.fac_fecha')
            ->orderByDesc('f.fac_hora')
            ->get();

        return response()->json([
            'data'  => $rows,
            'total'=> $rows->count(),
        ]);
    }
}
