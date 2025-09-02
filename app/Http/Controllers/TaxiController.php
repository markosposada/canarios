<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaxiController extends Controller
{
    /**
     * Muestra el formulario para actualizar un taxi existente.
     */
    public function create()
    {
        // Cargar móviles con estado 'P'
        $moviles = DB::table('taxi')
            ->where('ta_estado', 'P')
            ->select('ta_movil')
            ->get();

        return view('modulos.taxis', compact('moviles'));
    }

    /**
     * Actualiza los datos del taxi con base en el móvil seleccionado.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'placa' => 'required|string',
            'movil' => 'required|numeric|exists:taxi,ta_movil',
            'soat' => 'required|date|after_or_equal:today',
            'tecno' => 'required|date|after_or_equal:today',
        ], [
            'placa.required' => 'La placa es obligatoria.',
            'movil.required' => 'El número de móvil es obligatorio.',
            'movil.exists' => 'El número de móvil no es válido.',
            'soat.required' => 'La fecha de SOAT es obligatoria.',
            'soat.after_or_equal' => 'La fecha de SOAT debe ser desde hoy en adelante.',
            'tecno.required' => 'La fecha de tecnomecánica es obligatoria.',
            'tecno.after_or_equal' => 'La fecha de tecnomecánica debe ser desde hoy en adelante.',
        ]);

        // Actualizar taxi con base en el móvil seleccionado
        DB::table('taxi')
            ->where('ta_movil', $validated['movil'])
            ->update([
                'ta_placa' => strtoupper($validated['placa']),
                'ta_estado' => 'A',
                'ta_soat' => $validated['soat'],
                'ta_tecno' => $validated['tecno'],
            ]);

        return redirect()->route('taxis.create')->with('success', '¡Datos del taxi actualizados correctamente!');
    }

    public function editDates()
{
    return view('modulos.editar-fechas');
}

public function buscarPorPlaca(Request $request)
{
    $taxi = DB::table('taxi')
        ->where('ta_placa', strtoupper($request->query('placa')))
        ->first();

    return response()->json(['taxi' => $taxi]);
}

public function updateDates(Request $request)
{
    $validated = $request->validate([
        'placa' => 'required|string|exists:taxi,ta_placa',
        'soat' => 'required|date|after_or_equal:today',
        'tecno' => 'required|date|after_or_equal:today',
    ], [
        'placa.exists' => 'La placa no está registrada.',
        'soat.after_or_equal' => 'La fecha de SOAT debe ser desde hoy en adelante.',
        'tecno.after_or_equal' => 'La fecha de tecnomecánica debe ser desde hoy en adelante.',
    ]);

    DB::table('taxi')
        ->where('ta_placa', strtoupper($validated['placa']))
        ->update([
            'ta_soat' => $validated['soat'],
            'ta_tecno' => $validated['tecno'],
        ]);

    return redirect()->route('taxis.editDates')->with('success', 'Fechas actualizadas correctamente.');
}


public function panel()
{
    $taxis = DB::table('taxi')
        ->orderBy('ta_movil')
        ->get();

    return view('modulos.panel-taxis', compact('taxis'));
}


public function cambiarEstado(Request $request)
{
    $request->validate([
        'movil' => 'required|integer|exists:taxi,ta_movil',
    ]);

    $taxi = DB::table('taxi')->where('ta_movil', $request->movil)->first();
    $nuevoEstado = $taxi->ta_estado === 'A' ? 'I' : 'A';

    DB::table('taxi')->where('ta_movil', $request->movil)->update(['ta_estado' => $nuevoEstado]);

    return response()->json(['estado' => $nuevoEstado]);
}


}
