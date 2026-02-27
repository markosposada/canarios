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
        $page = max(1, (int) $request->query('page', 1));
        $perPage = (int) $request->query('per_page', 30);
        $perPage = min(max($perPage, 10), 100); // 10..100

        $base = DB::table('conductores as c');

        if ($q !== '') {
            $like = "%{$q}%";
            $base->where(function ($w) use ($like) {
                $w->where('c.conduc_nombres', 'like', $like)
                  ->orWhere('c.conduc_cel', 'like', $like)
                  ->orWhereRaw('CAST(c.conduc_cc AS CHAR) LIKE ?', [$like]);
            });
        }

        // Total (sin limit/offset)
        $total = (clone $base)->count();

        $rows = $base
            ->orderBy('c.conduc_nombres')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->select([
                'c.conduc_cc as cedula',
                'c.conduc_nombres as nombre',
                'c.conduc_cel as movil',
                'c.conduc_estado as estado',
            ])
            ->get();

        $lastPage = (int) max(1, ceil($total / $perPage));

        return response()->json([
            'ok' => true,
            'data' => $rows,
            'meta' => [
                'q' => $q,
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
                'from' => $total ? (($page - 1) * $perPage + 1) : 0,
                'to' => $total ? min($page * $perPage, $total) : 0,
            ],
        ]);
    }

    public function actualizar(Request $request)
    {
        $request->validate([
            'cedula' => 'required',
            'estado' => 'required|integer|in:1,2,3,4,5',
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
