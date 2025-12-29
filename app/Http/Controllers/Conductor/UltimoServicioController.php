<?php

namespace App\Http\Controllers\Conductor;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UltimoServicioController extends Controller
{
    public function show()
    {
        $cedula = Auth::user()->cedula;

        // ✅ dis_conmo guarda mo_id, NO mo_taxi
$moIds = DB::table('movil')
    ->where('mo_conductor', $cedula)
    ->where('mo_estado', 1)
    ->pluck('mo_id');

        if ($moIds->isEmpty()) {
            return view('conductor.ultimo_servicio', [
                'servicio' => null,
                'hace' => null,
            ]);
        }

        // ✅ Buscar el último servicio por esos mo_id
        $servicio = DB::table('disponibles')
            ->whereIn('dis_conmo', $moIds)
            ->orderByDesc('dis_fecha')
            ->orderByDesc('dis_hora')
            ->first();

        $hace = null;

        if ($servicio && !empty($servicio->dis_fecha) && !empty($servicio->dis_hora)) {
            // ✅ dis_hora puede venir H:i o H:i:s
            $format = strlen($servicio->dis_hora) === 5 ? 'Y-m-d H:i' : 'Y-m-d H:i:s';

            $fechaHora = Carbon::createFromFormat(
                $format,
                $servicio->dis_fecha . ' ' . $servicio->dis_hora,
                'America/Bogota'
            );

            // ✅ "hace X..." (sin decimales raros)
            $hace = $fechaHora->diffForHumans(now('America/Bogota'), [
                'parts' => 2,
                'short' => false,
            ]);
        }

        return view('conductor.ultimo_servicio', compact('servicio', 'hace'));
    }
}
