<?php

namespace App\Http\Controllers\Operadora;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EstadoConductorControllerOperadora extends Controller
{
    public function index()
    {
        return view('operadora.estado_conductor');
    }

    public function buscar(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json(['ok' => true, 'data' => []]);
        }

        $like = "%{$q}%";

        $rows = DB::table('conductores as c')
            ->where(function ($w) use ($like) {
                $w->where('c.conduc_nombres', 'like', $like)
                  ->orWhereRaw('CAST(c.conduc_cc AS CHAR) LIKE ?', [$like]);
            })
            ->orderBy('c.conduc_nombres')
            ->limit(30)
            ->select([
                'c.conduc_cc as cedula',
                'c.conduc_nombres as nombre',
                'c.conduc_estado as estado',
            ])
            ->get();

        return response()->json(['ok' => true, 'data' => $rows]);
    }

    public function actualizar(Request $request)
    {
        $request->validate([
            'cedula' => 'required',
            'estado' => 'required|integer|in:1,2,4',
        ]);

        $cedula = $request->input('cedula');
        $estado = (int) $request->input('estado');

        $existe = DB::table('conductores')->where('conduc_cc', $cedula)->exists();
        if (!$existe) {
            return response()->json(['success' => false, 'message' => 'Conductor no encontrado.'], 404);
        }

        DB::table('conductores')
            ->where('conduc_cc', $cedula)
            ->update(['conduc_estado' => $estado]);

        return response()->json([
            'success' => true,
            'cedula'  => $cedula,
            'estado'  => $estado,
        ]);
    }
}
