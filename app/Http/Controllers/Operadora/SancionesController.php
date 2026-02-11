<?php

namespace App\Http\Controllers\Operadora;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SancionesController extends Controller
{
    public function index()
    {
        $tipos = DB::table('tiposancion')
            ->orderBy('tpsa_id')
            ->get();

        return view('operadora.sanciones', compact('tipos'));
    }

    /**
     * Lista móviles activos para sancionar
     * - mo_estado = 1
     * - trae conductor y placa si existe
     */
  public function movilesActivos(Request $request)
{
    $q = trim($request->get('q', ''));

    $sql = DB::table('movil as m')
        ->join('conductores as c', 'c.conduc_cc', '=', 'm.mo_conductor')
        ->leftJoin('taxi as t', 't.ta_movil', '=', 'm.mo_taxi')
        ->where('m.mo_estado', 1)
        ->select([
            'm.mo_id',
            'm.mo_taxi',
            'm.mo_conductor',
            'c.conduc_nombres',
            DB::raw("COALESCE(t.ta_placa,'') as placa"),
            DB::raw("c.conduc_estado as estado_conductor"),
        ]);

    // ✅ SOLO por número de móvil
    if ($q !== '') {
        $sql->whereRaw('CAST(m.mo_taxi AS CHAR) LIKE ?', ["%{$q}%"]);
    }

    return response()->json(
        $sql->orderBy('m.mo_taxi')->limit(200)->get()
    );
}


    /**
     * Registrar sanción
     * Guarda:
     * - sancion_condu = cédula
     * - sancion_movil = número del móvil (mo_taxi)
     * - sancion_tipo  = tpsa_id
     * - sancion_fecha/hora = ahora
     * - sancion_operadora = nombre del usuario logueado
     */
 public function registrar(Request $request)
{
    $request->validate([
        'mo_id'    => 'required|integer',
        'tpsa_id'  => 'required|integer|exists:tiposancion,tpsa_id',
    ]);

    $operadora = Auth::user()->name ?? Auth::user()->email ?? 'OPERADORA';
    $now = Carbon::now('America/Bogota');

    return DB::transaction(function () use ($request, $operadora, $now) {

        $movil = DB::table('movil as m')
            ->join('conductores as c', 'c.conduc_cc', '=', 'm.mo_conductor')
            ->where('m.mo_id', $request->mo_id)
            ->select('m.mo_taxi', 'm.mo_conductor', 'm.mo_estado', 'c.conduc_estado')
            ->lockForUpdate()
            ->first();

        if (!$movil) {
            return response()->json(['success' => false, 'message' => 'Móvil no encontrado.'], 404);
        }

        if ((int)$movil->mo_estado !== 1) {
            return response()->json(['success' => false, 'message' => 'Solo se puede sancionar móviles activos.'], 422);
        }

        // 1) Insertar sanción
        $id = DB::table('sancion')->insertGetId([
            'sancion_condu'     => $movil->mo_conductor,
            'sancion_movil'     => $movil->mo_taxi,
            'sancion_tipo'      => $request->tpsa_id,
            'sancion_fecha'     => $now->toDateString(),
            'sancion_hora'      => $now->format('H:i:s'),
            'sancion_operadora' => $operadora,
        ]);

        // 2) Cambiar estado del conductor a 3 (SANCIONADO)
        DB::table('conductores')
            ->where('conduc_cc', $movil->mo_conductor)
            ->update(['conduc_estado' => 3]);

        return response()->json([
            'success' => true,
            'id'      => $id,
        ]);
    });
}


    /**
     * Listado de sanciones recientes con cálculo de vigencia:
     * fin = fecha+hora + tpsa_horas
     * vigente = now < fin
     * minutos_restantes (si vigente)
     */
    /**
     * Listado de sanciones recientes (ÚLTIMOS 3 DÍAS)
     */
    public function listar(Request $request)
    {
        $limit = (int)($request->get('limit', 100));
        if ($limit <= 0 || $limit > 500) $limit = 100;

        // "Ahora" en Bogotá (Objeto Carbon)
        $nowCarbon = Carbon::now('America/Bogota');
        
        // Formato string para las comparaciones SQL RAW
        $nowStr = $nowCarbon->format('Y-m-d H:i:s');

        // Calcular fecha de hace 3 días
        $fechaLimite = $nowCarbon->copy()->subDays(3)->toDateString(); 

        $rows = DB::table('sancion as s')
            ->join('tiposancion as t', 't.tpsa_id', '=', 's.sancion_tipo')
            ->leftJoin('conductores as c', 'c.conduc_cc', '=', 's.sancion_condu')
            
            // --- NUEVO FILTRO: Solo fecha mayor o igual a hace 3 días ---
            ->where('s.sancion_fecha', '>=', $fechaLimite)
            // -----------------------------------------------------------

            ->selectRaw("
                s.sancion_id,
                s.sancion_movil,
                s.sancion_condu,
                COALESCE(c.conduc_nombres,'') as conductor,

                s.sancion_tipo,
                t.tpsa_sancion as tipo,
                t.tpsa_horas as horas,
                t.tpsa_valor as valor,

                s.sancion_fecha as fecha,
                s.sancion_hora  as hora,
                s.sancion_operadora as operadora,

                s.sancion_activa,
                s.sancion_levantada_fecha,
                s.sancion_levantada_hora,
                s.sancion_levantada_operadora,
                s.sancion_levantada_motivo,

                TIMESTAMP(s.sancion_fecha, s.sancion_hora) as inicio,
                TIMESTAMPADD(HOUR, t.tpsa_horas, TIMESTAMP(s.sancion_fecha, s.sancion_hora)) as fin,

                CASE 
                  WHEN s.sancion_activa = 1 
                   AND TIMESTAMPADD(HOUR, t.tpsa_horas, TIMESTAMP(s.sancion_fecha, s.sancion_hora)) > ? 
                  THEN 1 ELSE 0 
                END as vigente,

                CASE 
                  WHEN s.sancion_activa = 1 
                   AND TIMESTAMPADD(HOUR, t.tpsa_horas, TIMESTAMP(s.sancion_fecha, s.sancion_hora)) > ? 
                  THEN TIMESTAMPDIFF(
                    MINUTE, 
                    ?, 
                    TIMESTAMPADD(HOUR, t.tpsa_horas, TIMESTAMP(s.sancion_fecha, s.sancion_hora))
                  ) 
                  ELSE 0 
                END as minutos_restantes
            ", [$nowStr, $nowStr, $nowStr])
            ->orderByDesc('s.sancion_id')
            ->limit($limit)
            ->get();

        return response()->json([
            'data'  => $rows,
            'total' => $rows->count(),
        ]);
    }



    public function levantar(Request $request, $id)
{
    $request->validate([
        'motivo' => 'required|string|max:255',
    ]);

    $now = Carbon::now('America/Bogota');
    $operadora = Auth::user()->name ?? Auth::user()->email ?? 'OPERADORA';

    return DB::transaction(function () use ($id, $request, $now, $operadora) {

        $sancion = DB::table('sancion')
            ->where('sancion_id', $id)
            ->lockForUpdate()
            ->first();

        if (!$sancion) {
            return response()->json(['success' => false, 'message' => 'Sanción no encontrada.'], 404);
        }

        // si ya está levantada
        if (isset($sancion->sancion_activa) && (int)$sancion->sancion_activa === 0) {
            return response()->json(['success' => false, 'message' => 'Esta sanción ya fue levantada.'], 422);
        }

        // 1) levantar (anular)
        DB::table('sancion')
            ->where('sancion_id', $id)
            ->update([
                'sancion_activa'             => 0,
                'sancion_levantada_fecha'    => $now->toDateString(),
                'sancion_levantada_hora'     => $now->format('H:i:s'),
                'sancion_levantada_operadora'=> $operadora,
                'sancion_levantada_motivo'   => trim($request->motivo),
            ]);

        $cedula = $sancion->sancion_condu;

        // 2) ¿quedan sanciones ACTIVAS vigentes para este conductor?
        $nowStr = $now->format('Y-m-d H:i:s');

        $tieneActivaVigente = DB::table('sancion as s')
            ->join('tiposancion as t', 't.tpsa_id', '=', 's.sancion_tipo')
            ->where('s.sancion_condu', $cedula)
            ->where('s.sancion_activa', 1)
            ->whereRaw("TIMESTAMPADD(HOUR, t.tpsa_horas, TIMESTAMP(s.sancion_fecha, s.sancion_hora)) > ?", [$nowStr])
            ->exists();

        DB::table('conductores')
            ->where('conduc_cc', $cedula)
            ->update(['conduc_estado' => $tieneActivaVigente ? 3 : 1]);

        return response()->json(['success' => true]);
    });
}

public function vistaSancionar()
{
    $tipos = DB::table('tiposancion')->orderBy('tpsa_id')->get();
    return view('operadora.sancionar', compact('tipos'));
}

public function vistaLevantar()
{
    return view('operadora.sanciones_levantar');
}

}
