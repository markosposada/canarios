<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AsignaServicioController extends Controller
{
    public function vista()
    {
        return view('servicios.asignar');
    }

    /**
     * Lista de MÓVILES ACTIVOS con cantidad de servicios del día
     */
public function movilesActivos(Request $request)
{
    $hoy = \Carbon\Carbon::today()->toDateString();
    $q   = trim($request->get('q', ''));

    $moviles = \DB::table('movil')
        ->join('conductores', 'movil.mo_conductor', '=', 'conductores.conduc_cc')
        ->leftJoin('taxi', 'taxi.ta_movil', '=', 'movil.mo_taxi')
        ->leftJoin('disponibles', function($join) use ($hoy) {
            $join->on('disponibles.dis_conmo', '=', 'movil.mo_id')  // ⚠️ cambia a mo_conductor si tu diseño es así
                 ->whereDate('disponibles.dis_fecha', $hoy);
        })
        ->where('movil.mo_estado', 1)
        ->when($q !== '', function($sql) use ($q) {
            $like = "%{$q}%";
            $sql->where(function($w) use ($like) {
                // mo_taxi suele ser INT; casteamos a CHAR para LIKE fiable
                $w->whereRaw('CAST(movil.mo_taxi AS CHAR) LIKE ?', [$like])
                  ->orWhere('conductores.conduc_nombres', 'like', $like)
                  ->orWhere('taxi.ta_placa', 'like', $like);
            });
        })
        ->groupBy('movil.mo_id', 'movil.mo_taxi', 'taxi.ta_placa', 'conductores.conduc_nombres', 'movil.mo_conductor')
        ->selectRaw('
            movil.mo_id,
            movil.mo_taxi,
            COALESCE(taxi.ta_placa, "") AS placa,
            conductores.conduc_nombres AS nombre_conductor,
            COUNT(disponibles.dis_conmo) AS cantidad
        ')
        ->orderBy('cantidad')
        ->orderBy('movil.mo_taxi')
        ->get();

    return response()->json($moviles);
}

    /**
     * Registrar servicio "disponible"
     */
public function registrar(Request $request)
{
    $request->validate([
        'conmo'      => 'required',
        'usuario'    => 'required|string|max:200',
        'direccion'  => 'required|string|max:255',
        'barrio'     => 'required|string|max:120', // <--- ahora es obligatorio
        'operadora'  => 'required|string|max:120',
    ]);

    $now = \Carbon\Carbon::now();

    DB::table('disponibles')->insert([
        'dis_conmo'     => $request->conmo,
        'dis_dire'      => trim($request->direccion).' - '.$request->barrio,
        'dis_usuario'   => $request->usuario,
        'dis_fecha'     => $now->toDateString(),
        'dis_hora'      => $now->format('H:i:s'),
        'dis_operadora' => $request->operadora
    ]);

    return response()->json(['success' => true]);
}
}