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
    $cedula = Auth::user()->cedula;

    $rows = DB::table('disponibles as d')
        ->join('movil as m', 'm.mo_id', '=', 'd.dis_conmo')
        ->select([
            'd.dis_id',
            'm.mo_taxi as movil', // ✅ CAMBIO AQUÍ
            'd.dis_dire as direccion',
            'd.dis_fecha as fecha',
            'd.dis_hora as hora',
            DB::raw('LEFT(COALESCE(d.dis_operadora, ""), 8) as operadora'),
            'd.dis_servicio as servicio',
        ])
        ->where('m.mo_conductor', $cedula)
        // ✅ últimos 3 días (incluye hoy)
        ->whereDate('d.dis_fecha', '>=', now()->subDays(3)->toDateString())
        ->orderByDesc('d.dis_fecha')
        ->orderByDesc('d.dis_hora')
        ->get();

    return response()->json([
        'data'  => $rows,
        'total' => $rows->count(),
    ]);
}


}
