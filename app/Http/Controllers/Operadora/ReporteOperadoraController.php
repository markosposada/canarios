<?php

namespace App\Http\Controllers\operadora;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class ReporteOperadoraController extends Controller
{
    public function vistaResumenCarreras()
    {
        $user = Auth::user();
        $rol = strtolower(trim($user->rol ?? ''));
        $nombre = $user->name ?? $user->email ?? 'OPERADORA';
        $esAdmin = ($rol === 'administrador');

        return view('operadora.resumen_operadoras', [
            'esAdmin' => $esAdmin,
            'operadoraLogueada' => $nombre,
        ]);
    }

    public function listarResumenCarreras(Request $request)
    {
        try {
            $user = Auth::user();
            $rol = strtolower(trim($user->rol ?? ''));
            $nombre = $user->name ?? $user->email ?? 'OPERADORA';
            $esAdmin = ($rol === 'administrador');

            $desde = $request->query('desde');
            $hasta = $request->query('hasta');
            $operadora = trim($request->query('operadora', ''));

            $sql = DB::table('disponibles as d')
                ->selectRaw("
                    CASE
                        WHEN d.dis_operadora IS NULL OR TRIM(d.dis_operadora) = '' THEN 'SIN OPERADORA'
                        ELSE d.dis_operadora
                    END as operadora,
                    COUNT(*) as total_carreras,
                    SUM(CASE WHEN COALESCE(d.dis_servicio, 1) = 1 THEN 1 ELSE 0 END) as activas,
                    SUM(CASE WHEN COALESCE(d.dis_servicio, 1) = 2 THEN 1 ELSE 0 END) as canceladas
                ");

            if ($desde) {
                $sql->whereDate('d.dis_fecha', '>=', $desde);
            }

            if ($hasta) {
                $sql->whereDate('d.dis_fecha', '<=', $hasta);
            }

            if ($esAdmin) {
                if ($operadora !== '') {
                    $sql->where('d.dis_operadora', $operadora);
                }
            } else {
                $sql->where('d.dis_operadora', $nombre);
            }

            $rows = $sql
                ->groupBy('d.dis_operadora')
                ->orderByDesc('total_carreras')
                ->get();

            $totales = [
                'total_carreras' => (int) $rows->sum('total_carreras'),
                'activas' => (int) $rows->sum('activas'),
                'canceladas' => (int) $rows->sum('canceladas'),
            ];

            return response()->json([
                'ok' => true,
                'data' => $rows,
                'totales' => $totales,
                'scope' => $esAdmin ? 'global' : 'propio',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function listarOperadoras()
    {
        $user = Auth::user();
        $rol = strtolower(trim($user->rol ?? ''));
        $nombre = $user->name ?? $user->email ?? 'OPERADORA';
        $esAdmin = ($rol === 'administrador');

        if ($esAdmin) {
            $rows = DB::table('users')
                ->whereIn('rol', ['operadora', 'administrador'])
                ->whereNotNull('name')
                ->whereRaw("TRIM(name) <> ''")
                ->orderBy('name')
                ->pluck('name');
        } else {
            $rows = collect([$nombre]);
        }

        return response()->json([
            'ok' => true,
            'data' => $rows,
        ]);
    }
}