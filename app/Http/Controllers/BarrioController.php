<?php

// app/Http/Controllers/BarrioController.php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BarrioController extends Controller
{
    public function sugerencias(Request $request)
    {
        $q = trim($request->get('q', ''));
        if ($q === '') return response()->json([]);

        // Búsqueda insensible a mayúsculas con límite
        $like = '%'.$q.'%';
        $barrios = DB::table('barrios')
            ->where('nombre', 'like', $like)
            ->orderByRaw('LENGTH(nombre) ASC') // los más cortos/simples primero
            ->limit(10)
            ->get(['id','nombre']);

        return response()->json($barrios);
    }
}

