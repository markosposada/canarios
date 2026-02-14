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

        // ✅ Traer el último servicio y además el mo_taxi (placa) desde movil
        $servicio = DB::table('disponibles as d')
            ->join('movil as m', 'm.mo_id', '=', 'd.dis_conmo')
            ->select('d.*', 'm.mo_taxi') // ✅ ahora la vista podrá usar $servicio->mo_taxi
            ->where('m.mo_conductor', $cedula)
            ->where('m.mo_estado', 1)
            ->orderByDesc('d.dis_fecha')
            ->orderByDesc('d.dis_hora')
            ->first();

        if (!$servicio) {
            return view('conductor.ultimo_servicio', [
                'servicio' => null,
                'hace' => null,
            ]);
        }

        $hace = null;

        if (!empty($servicio->dis_fecha) && !empty($servicio->dis_hora)) {
            $format = strlen($servicio->dis_hora) === 5 ? 'Y-m-d H:i' : 'Y-m-d H:i:s';

            $fechaHora = Carbon::createFromFormat(
                $format,
                $servicio->dis_fecha . ' ' . $servicio->dis_hora,
                'America/Bogota'
            );

            $hace = $fechaHora->diffForHumans(now('America/Bogota'), [
                'parts' => 2,
                'short' => false,
            ]);
        }

        return view('conductor.ultimo_servicio', compact('servicio', 'hace'));
    }
}

