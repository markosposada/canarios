<?php

namespace App\Http\Controllers\Conductor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EstadoConductorController extends Controller
{
    public function index()
    {
        $cedula = auth()->user()->cedula;

        $conductor = DB::table('conductores')
            ->where('conduc_cc', $cedula)
            ->select('conduc_cc', 'conduc_nombres', 'conduc_estado')
            ->first();

        if (!$conductor) {
            abort(404, 'No se encontró el conductor asociado a tu usuario.');
        }

        return view('conductor.estado', compact('conductor'));
    }

    public function update(Request $request)
    {
        $cedula = auth()->user()->cedula;

        // Estado solicitado (solo 1 o 2)
        $request->validate([
            'estado' => ['required', 'integer', 'in:1,2'],
        ]);

        return DB::transaction(function () use ($cedula, $request) {

            // Bloquear fila del conductor
            $conductor = DB::table('conductores')
                ->where('conduc_cc', $cedula)
                ->lockForUpdate()
                ->select('conduc_cc', 'conduc_estado')
                ->first();

            if (!$conductor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conductor no encontrado.'
                ], 404);
            }

            $estadoActual = (int) $conductor->conduc_estado;

            // ✅ REGLA: solo puede cambiar si está en 1 o 2
            if ($estadoActual === 3) {
                return response()->json([
                    'success' => false,
                    'code'    => 'CONDUCTOR_SANCIONADO',
                    'message' => 'No puedes cambiar tu estado. Estás sancionado. Acércate a la oficina principal.',
                    'estado'  => $estadoActual
                ], 403);
            }

            if ($estadoActual === 4) {
                return response()->json([
                    'success' => false,
                    'code'    => 'CONDUCTOR_RETIRADO',
                    'message' => 'No puedes cambiar tu estado. Estás retirado. Acércate a la oficina principal.',
                    'estado'  => $estadoActual
                ], 403);
            }

            if (!in_array($estadoActual, [1,2], true)) {
                // Por si aparece un estado no previsto
                return response()->json([
                    'success' => false,
                    'code'    => 'ESTADO_NO_PERMITIDO',
                    'message' => 'Tu estado actual no permite cambios. Acércate a la oficina.',
                    'estado'  => $estadoActual
                ], 403);
            }

            $nuevoEstado = (int) $request->estado;

            // Aseguramos que solo sea 1 o 2
            if (!in_array($nuevoEstado, [1,2], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Estado inválido.'
                ], 422);
            }

            DB::table('conductores')
                ->where('conduc_cc', $cedula)
                ->update(['conduc_estado' => $nuevoEstado]);

            return response()->json([
                'success' => true,
                'estado'  => $nuevoEstado
            ]);
        });
    }
}
