<?php

namespace App\Http\Controllers\Conductor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaxiConductorController extends Controller
{
    private function taxiAsignadoPorConductor($cedula)
    {
        // Relación: movil.mo_conductor = cedula, movil.mo_taxi = taxi.ta_movil
        // Si tu "activo" no es 1, cambia este filtro.
        return DB::table('movil as m')
            ->join('taxi as t', 't.ta_movil', '=', 'm.mo_taxi')
            ->where('m.mo_conductor', $cedula)
            ->where('m.mo_estado', 1) // SOLO MOVIL ACTIVO
            ->select(
                't.ta_movil',
                't.ta_placa',
                't.ta_estado',
                't.ta_soat',
                't.ta_tecno'
            )
            ->first();
    }

    public function edit()
    {
        $cedula = auth()->user()->cedula;

        $taxi = $this->taxiAsignadoPorConductor($cedula);

        if (!$taxi) {
            abort(403, 'Para editar los datos del taxi debes tener un móvil ACTIVO. Activa tu móvil desde "Mis Móviles" e inténtalo nuevamente.');
        }

        return view('conductor.taxi_edit', compact('taxi'));
    }

    public function update(Request $request)
    {
        $cedula = auth()->user()->cedula;

        $taxi = $this->taxiAsignadoPorConductor($cedula);

        if (!$taxi) {
            abort(403, 'No tienes un taxi asignado para actualizar.');
        }

        $request->validate([
            'ta_placa' => ['required', 'string', 'max:10'],
            'ta_soat'  => ['nullable', 'date'],
            'ta_tecno' => ['nullable', 'date'],
            // Si NO quieres que cambien estado, no lo pongas aquí.
        ]);

        DB::table('taxi')
            ->where('ta_movil', $taxi->ta_movil) // 👈 solo su taxi
            ->update([
                'ta_placa' => strtoupper(trim($request->ta_placa)),
                'ta_soat'  => $request->ta_soat ?: '0000-00-00',
                'ta_tecno' => $request->ta_tecno ?: '0000-00-00',
            ]);

        return redirect()
            ->route('conductor.taxi.edit')
            ->with('ok', '✅ Taxi actualizado correctamente.');
    }
}
