<?php

namespace App\Http\Controllers\Conductor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MovilesController extends Controller
{
    public function index()
    {
        $cedula = auth()->user()->cedula;

        $moviles = DB::table('movil')
            ->where('mo_conductor', $cedula)
            ->select('mo_id', 'mo_taxi', 'mo_estado')
            ->orderBy('mo_taxi')
            ->get();

        return view('conductor.moviles', compact('moviles'));
    }

    public function toggle(Request $request, $movilId)
    {
        \Log::info("== INICIO toggle por MÓVIL con mo_id: $movilId ==");

        $cedula = auth()->user()->cedula;

        $accion = $request->input('accion', 'activar') === 'desactivar'
            ? 'desactivar'
            : 'activar';

        return DB::transaction(function () use ($movilId, $accion, $cedula) {

            // Bloquea el registro seleccionado (y asegura que sea del conductor logueado)
            $registro = DB::table('movil')
                ->where('mo_id', $movilId)
                ->where('mo_conductor', $cedula)
                ->lockForUpdate()
                ->first();

            if (!$registro) {
                \Log::warning("No existe registro movil con mo_id=$movilId para cedula=$cedula");
                return response()->json(['success' => false, 'message' => 'Registro no encontrado.'], 404);
            }

            $movil = $registro->mo_taxi;

            // ✅ REGLAS SOLO CUANDO VA A ACTIVAR
            if ($accion === 'activar') {

                // 1) Consultar estado del conductor
                $estadoConductor = (int) DB::table('conductores')
                    ->where('conduc_cc', $cedula)
                    ->value('conduc_estado'); // 1=activo, 2=debe activar estado, 3/4=sancionado (según tu regla)

                \Log::info("Estado conductor cedula=$cedula => estado=$estadoConductor");

                // 2) Aplicar reglas:
                // estado 2 -> bloquear activación y pedir activar estado conductor
                if ($estadoConductor === 2) {
                    return response()->json([
                        'success' => false,
                        'code'    => 'CONDUCTOR_INACTIVO',
                        'message' => 'Debes activar tu estado de conductor antes de activar el móvil.',
                        'redirect'=> url('/conductor/estado')
                    ], 403);
                }

                // estado 3 o 4 -> sancionado
                if ($estadoConductor === 3 || $estadoConductor === 4) {
                    return response()->json([
                        'success' => false,
                        'code'    => 'CONDUCTOR_SANCIONADO',
                        'message' => 'Tu cuenta se encuentra sancionada. Debes acercarte a la oficina principal.',
                    ], 403);
                }

                // estado 1 (o cualquier otro permitido) -> continúa
                // (1) Activar el seleccionado
                DB::table('movil')
                    ->where('mo_id', $movilId)
                    ->where('mo_conductor', $cedula)
                    ->update(['mo_estado' => 1]);

                // (2) Desactivar todos los demás del MISMO MÓVIL (mismo mo_taxi)
                DB::table('movil')
                    ->where('mo_taxi', $movil)
                    ->where('mo_id', '!=', $movilId)
                    ->where('mo_estado', 1)
                    ->update(['mo_estado' => 2]);

                \Log::info("Activado mo_id=$movilId en mo_taxi=$movil; otros desactivados");

                return response()->json([
                    'success' => true,
                    'accion'  => 'activar',
                    'mo_taxi' => $movil
                ]);
            }

            // Desactivar solo este registro
            DB::table('movil')
                ->where('mo_id', $movilId)
                ->where('mo_conductor', $cedula)
                ->update(['mo_estado' => 2]);

            \Log::info("Desactivado mo_id=$movilId en mo_taxi=$movil");

            return response()->json([
                'success' => true,
                'accion'  => 'desactivar',
                'mo_taxi' => $movil
            ]);
        });
    }
}
