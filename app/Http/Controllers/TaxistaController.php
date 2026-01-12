<?php

namespace App\Http\Controllers;

use App\Models\Taxista;
use Illuminate\Http\Request;

class TaxistaController extends Controller
{
    public function create()
    {
        return view('taxistas.create');
    }

public function store(Request $request)
{
    $data = $request->validate([
        'nombre' => ['required','string','max:100'],
        'apellidos' => ['required','string','max:150'],

        // Solo números (sin longitud fija indicada)
        'cedula' => ['required','regex:/^\d+$/','max:30','unique:taxistas,cedula'],

        // Exactamente 10 dígitos
        'celular' => ['required','digits:10'],

        // Exactamente 2 dígitos
       'movil' => ['required', 'digits_between:1,3'],

        // 3 letras y 3 números (ej: ABC123)
        'placa_taxi' => ['required','regex:/^[A-Za-z]{3}\d{3}$/',],

        // Opcional
        'correo_electronico' => ['nullable','email','max:150','unique:taxistas,correo_electronico'],
    ]);

    // Normalizar placa a mayúsculas antes de guardar
    $data['placa_taxi'] = strtoupper($data['placa_taxi']);

    Taxista::create($data);

    return redirect()->route('taxistas.success');
}

}

