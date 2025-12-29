<?php

namespace App\Http\Controllers\Operadora;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FacturacionController extends Controller
{
    public function vista()
    {
        return view('operadora.facturacion');
    }

    /**
     * Resolver mo_id correcto para un conductor (NO depende de activo)
     * Prioridad:
     *  1) mo_id que tenga servicios pendientes en disponibles (dis_facturado=0 y dis_servicio=1)
     *  2) último mo_id del conductor
     */
    private function resolverMoIdPorConductor(string $cc): ?int
    {
        // 1) Si hay servicios pendientes, ese mo_id es el correcto
        $moId = DB::table('disponibles as d')
            ->join('movil as m', 'm.mo_id', '=', 'd.dis_conmo')
            ->where('m.mo_conductor', $cc)
            ->where('d.dis_servicio', 1)
            ->where('d.dis_facturado', 0)
            ->orderByDesc('d.dis_fecha')
            ->orderByDesc('d.dis_hora')
            ->value('m.mo_id');

        if ($moId) return (int)$moId;

        // 2) Fallback: último mo_id del conductor
        $moId = DB::table('movil')
            ->where('mo_conductor', $cc)
            ->orderByDesc('mo_id')
            ->value('mo_id');

        return $moId ? (int)$moId : null;
    }

    /**
     * ✅ Pendientes POR CÉDULA (igual a recaudo)
     */
    public function pendientesPorCedula(Request $request)
    {
        $cc = trim($request->query('cedula', ''));
        if ($cc === '') {
            return response()->json(['ok' => false, 'message' => 'Debes ingresar la cédula.'], 422);
        }

        $conductor = DB::table('conductores')
            ->where('conduc_cc', $cc)
            ->select('conduc_cc', 'conduc_nombres', 'conduc_estado')
            ->first();

        if (!$conductor) {
            return response()->json(['ok' => false, 'message' => 'Conductor no encontrado.'], 404);
        }

        // ✅ mo_id correcto (sin exigir activo)
        $moId = $this->resolverMoIdPorConductor($cc);

        // móvil referencia (para mostrar)
        $movilTaxi = DB::table('movil')
            ->where('mo_conductor', $cc)
            ->orderByDesc('mo_id')
            ->value('mo_taxi');

        // valor servicio (fecha más actual)
        $valorServicio = (int) (DB::table('valorservicio')
            ->orderByDesc('fecha')
            ->value('vs_valor') ?? 0);

        // Servicios pendientes (solo aceptados)
        $servicios = collect();
        if ($moId) {
            $servicios = DB::table('disponibles as d')
                ->where('d.dis_conmo', $moId)
                ->where('d.dis_servicio', 1)
                ->where('d.dis_facturado', 0)
                ->orderByDesc('d.dis_fecha')
                ->orderByDesc('d.dis_hora')
                ->select([
                    'd.dis_id',
                    'd.dis_dire',
                    'd.dis_usuario',
                    'd.dis_fecha',
                    'd.dis_hora',
                    'd.dis_operadora',
                ])
                ->get();
        }

        // ✅ Sanciones pendientes (SOLO ACTIVAS)
        $sanciones = DB::table('sancion as s')
            ->join('tiposancion as t', 't.tpsa_id', '=', 's.sancion_tipo')
            ->where('s.sancion_condu', $cc)
            ->where('s.sancion_activa', 1)
            ->where('s.sancion_facturada', 0)
            ->orderByDesc('s.sancion_id')
            ->select([
                's.sancion_id',
                's.sancion_fecha',
                's.sancion_hora',
                's.sancion_operadora',
                's.sancion_activa',
                't.tpsa_sancion as tipo',
                't.tpsa_valor as valor',
                't.tpsa_horas as horas',
            ])
            ->get();

        $totalServicios = $servicios->count() * $valorServicio;
        $totalSanciones = (int) $sanciones->sum('valor');
        $total = $totalServicios + $totalSanciones;

        return response()->json([
            'ok' => true,
            'movil' => [
                'mo_id' => $moId,                       // puede ser null si no hay móvil
                'mo_taxi' => $movilTaxi ?? '—',
                'conductor_cc' => $conductor->conduc_cc,
                'conductor_nombre' => $conductor->conduc_nombres,
                'conductor_estado' => (int)$conductor->conduc_estado,
            ],
            'valor_servicio' => $valorServicio,
            'servicios' => $servicios,
            'sanciones' => $sanciones,
            'totales' => [
                'cantidad_servicios' => $servicios->count(),
                'total_servicios' => $totalServicios,
                'total_sanciones' => $totalSanciones,
                'total' => $total,
            ],
        ]);
    }

    /**
     * ✅ Buscar conductores por nombre o cédula (para modal) - SIN exigir móvil activo
     */
    public function buscarConductores(Request $request)
    {
        $q = trim($request->query('q', ''));
        if ($q === '') {
            return response()->json(['ok' => true, 'data' => []]);
        }

        $like = "%{$q}%";

        $rows = DB::table('conductores as c')
            ->leftJoin('movil as m', 'm.mo_conductor', '=', 'c.conduc_cc')
            ->where(function ($w) use ($like) {
                $w->where('c.conduc_nombres', 'like', $like)
                  ->orWhereRaw('CAST(c.conduc_cc AS CHAR) LIKE ?', [$like]);
            })
            ->groupBy('c.conduc_cc', 'c.conduc_nombres')
            ->orderBy('c.conduc_nombres')
            ->limit(20)
            ->selectRaw("
                c.conduc_cc as cedula,
                c.conduc_nombres as nombre,
                MAX(m.mo_id) as last_mo_id
            ")
            ->get();

        $data = [];
        foreach ($rows as $r) {
            $movil = null;
            if (!empty($r->last_mo_id)) {
                $movil = DB::table('movil')->where('mo_id', $r->last_mo_id)->value('mo_taxi');
            }
            $data[] = [
                'cedula' => $r->cedula,
                'nombre' => $r->nombre,
                'movil'  => $movil ?? '—',
            ];
        }

        return response()->json(['ok' => true, 'data' => $data]);
    }

    /**
     * ✅ Facturar TODO por cédula (sin tocar el insert)
     * Nota: lo único distinto es que ahora recibimos "cedula"
     */
    public function facturar(Request $request)
    {
        $request->validate([
            'cedula' => 'required|string|max:50',
        ]);

        $cc = trim($request->cedula);
        $now = Carbon::now('America/Bogota');
        $operadora = Auth::user()->name ?? Auth::user()->email ?? 'OPERADORA';

        return DB::transaction(function () use ($cc, $now, $operadora) {

            // ✅ mo_id correcto (recalcular dentro de la transacción)
            $moId = $this->resolverMoIdPorConductor($cc);

            // conductor
            $conductor = DB::table('conductores')
                ->where('conduc_cc', $cc)
                ->select('conduc_cc', 'conduc_nombres')
                ->lockForUpdate()
                ->first();

            if (!$conductor) {
                return response()->json(['ok' => false, 'message' => 'Conductor no encontrado.'], 404);
            }

            // móvil referencia (para guardar en cabecera, igual que antes)
            $movilTaxi = DB::table('movil')
                ->where('mo_conductor', $cc)
                ->orderByDesc('mo_id')
                ->value('mo_taxi');

            $valorServicio = (int) (DB::table('valorservicio')
                ->orderByDesc('fecha')
                ->value('vs_valor') ?? 0);

            // IDs de servicios pendientes (del mo_id correcto)
            $servicios = collect();
            if ($moId) {
                $servicios = DB::table('disponibles')
                    ->where('dis_conmo', $moId)
                    ->where('dis_servicio', 1)
                    ->where('dis_facturado', 0)
                    ->lockForUpdate()
                    ->pluck('dis_id');
            }

            // ✅ Sanciones pendientes (SOLO ACTIVAS) del conductor real
            $sanciones = DB::table('sancion as s')
                ->join('tiposancion as t', 't.tpsa_id', '=', 's.sancion_tipo')
                ->where('s.sancion_condu', $cc)
                ->where('s.sancion_activa', 1)
                ->where('s.sancion_facturada', 0)
                ->lockForUpdate()
                ->select('s.sancion_id', 't.tpsa_valor')
                ->get();

            $totalServicios = $servicios->count() * $valorServicio;
            $totalSanciones = (int) $sanciones->sum('tpsa_valor');
            $total = $totalServicios + $totalSanciones;

            if ($servicios->count() === 0 && $sanciones->count() === 0) {
                return response()->json([
                    'ok' => false,
                    'message' => 'No hay pendientes para facturar.',
                ], 422);
            }

            // ✅ INSERT (no lo cambiamos de lógica, solo el móvil puede ser '—' si no existe)
            $foId = DB::table('facturacion_operadora')->insertGetId([
                'fo_movil' => (int)($movilTaxi ?? 0),
                'fo_conductor' => $cc,
                'fo_fecha' => $now->toDateString(),
                'fo_hora' => $now->format('H:i:s'),
                'fo_operadora' => $operadora,
                'fo_valor_servicio' => $valorServicio,
                'fo_total_servicios' => $totalServicios,
                'fo_total_sanciones' => $totalSanciones,
                'fo_total' => $total,
            ]);

            foreach ($servicios as $disId) {
                DB::table('facturacion_operadora_det')->insert([
                    'fo_id' => $foId,
                    'fod_tipo' => 'SERVICIO',
                    'fod_ref_id' => (int)$disId,
                    'fod_valor' => $valorServicio,
                ]);
            }

            foreach ($sanciones as $s) {
                DB::table('facturacion_operadora_det')->insert([
                    'fo_id' => $foId,
                    'fod_tipo' => 'SANCION',
                    'fod_ref_id' => (int)$s->sancion_id,
                    'fod_valor' => (int)$s->tpsa_valor,
                ]);
            }

            if ($servicios->count() > 0) {
                DB::table('disponibles')
                    ->whereIn('dis_id', $servicios->toArray())
                    ->update([
                        'dis_facturado' => 1,
                        'dis_facturado_at' => $now->format('Y-m-d H:i:s'),
                        'dis_facturado_operadora' => $operadora,
                    ]);
            }

            if ($sanciones->count() > 0) {
                DB::table('sancion')
                    ->whereIn('sancion_id', $sanciones->pluck('sancion_id')->toArray())
                    ->update([
                        'sancion_facturada' => 1,
                        'sancion_facturada_at' => $now->format('Y-m-d H:i:s'),
                        'sancion_facturada_operadora' => $operadora,
                    ]);
            }

            return response()->json([
                'ok' => true,
                'fo_id' => $foId,
                'total' => $total,
            ]);
        });
    }
}
