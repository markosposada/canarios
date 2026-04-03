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
        $moId = DB::table('disponibles as d')
            ->join('movil as m', 'm.mo_id', '=', 'd.dis_conmo')
            ->where('m.mo_conductor', $cc)
            ->where('d.dis_servicio', 1)
            ->where('d.dis_facturado', 0)
            ->orderByDesc('d.dis_fecha')
            ->orderByDesc('d.dis_hora')
            ->value('m.mo_id');

        if ($moId) return (int)$moId;

        $moId = DB::table('movil')
            ->where('mo_conductor', $cc)
            ->orderByDesc('mo_id')
            ->value('mo_id');

        return $moId ? (int)$moId : null;
    }

    /**
     * Normaliza arreglo de fechas recibido por query o body
     */
    private function normalizarFechas($fechas): array
    {
        if (is_string($fechas)) {
            $decoded = json_decode($fechas, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $fechas = $decoded;
            } else {
                $fechas = [$fechas];
            }
        }

        if (!is_array($fechas)) {
            $fechas = [];
        }

        return collect($fechas)
            ->filter()
            ->map(fn($f) => trim((string)$f))
            ->filter(fn($f) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $f))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * ✅ Pendientes POR CÉDULA y por múltiples días
     */
    public function pendientesPorCedula(Request $request)
    {
        $cc = trim($request->query('cedula', ''));
        $fechas = $this->normalizarFechas($request->query('fechas', []));

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

        $moId = $this->resolverMoIdPorConductor($cc);

        $movilTaxi = DB::table('movil')
            ->where('mo_conductor', $cc)
            ->orderByDesc('mo_id')
            ->value('mo_taxi');

        $valorServicio = (int) (DB::table('valorservicio')
            ->orderByDesc('fecha')
            ->value('vs_valor') ?? 0);

        $fechasServicios = collect();
        if ($moId) {
            $fechasServicios = DB::table('disponibles as d')
                ->where('d.dis_conmo', $moId)
                ->where('d.dis_servicio', 1)
                ->where('d.dis_facturado', 0)
                ->select('d.dis_fecha')
                ->distinct()
                ->pluck('d.dis_fecha');
        }

        $fechasSanciones = DB::table('sancion as s')
            ->where('s.sancion_condu', $cc)
            ->where('s.sancion_activa', 1)
            ->where('s.sancion_facturada', 0)
            ->select('s.sancion_fecha')
            ->distinct()
            ->pluck('s.sancion_fecha');

        $fechasPendientes = $fechasServicios
            ->merge($fechasSanciones)
            ->filter()
            ->map(fn($f) => (string)$f)
            ->unique()
            ->sortDesc()
            ->values();

        $servicios = collect();
        if ($moId && !empty($fechas)) {
            $servicios = DB::table('disponibles as d')
                ->where('d.dis_conmo', $moId)
                ->where('d.dis_servicio', 1)
                ->where('d.dis_facturado', 0)
                ->whereIn('d.dis_fecha', $fechas)
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

        $sanciones = collect();
        if (!empty($fechas)) {
            $sanciones = DB::table('sancion as s')
                ->join('tiposancion as t', 't.tpsa_id', '=', 's.sancion_tipo')
                ->where('s.sancion_condu', $cc)
                ->where('s.sancion_activa', 1)
                ->where('s.sancion_facturada', 0)
                ->whereIn('s.sancion_fecha', $fechas)
                ->orderByDesc('s.sancion_fecha')
                ->orderByDesc('s.sancion_hora')
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
        }

        $totalServicios = $servicios->count() * $valorServicio;
        $totalSanciones = (int) $sanciones->sum('valor');
        $total = $totalServicios + $totalSanciones;

        return response()->json([
            'ok' => true,
            'movil' => [
                'mo_id' => $moId,
                'mo_taxi' => $movilTaxi ?? '—',
                'conductor_cc' => $conductor->conduc_cc,
                'conductor_nombre' => $conductor->conduc_nombres,
                'conductor_estado' => (int)$conductor->conduc_estado,
            ],
            'valor_servicio' => $valorServicio,
            'fechas_pendientes' => $fechasPendientes,
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
     * ✅ Buscar conductores por móvil
     */
    public function buscarConductores(Request $request)
    {
        $q = trim($request->query('q', ''));

        if ($q === '') {
            return response()->json(['ok' => true, 'data' => []]);
        }

        if (!ctype_digit($q)) {
            return response()->json([
                'ok' => false,
                'data' => [],
                'message' => 'Ingrese solo números de móvil.'
            ]);
        }

        $rows = DB::table('movil as m')
            ->join('conductores as c', 'c.conduc_cc', '=', 'm.mo_conductor')
            ->where('m.mo_taxi', (int)$q)
            ->orderBy('c.conduc_nombres')
            ->select([
                'c.conduc_cc as cedula',
                'c.conduc_nombres as nombre',
                'm.mo_taxi as movil',
            ])
            ->get();

        return response()->json([
            'ok' => true,
            'data' => $rows,
        ]);
    }

    /**
     * ✅ Facturar múltiples días seleccionados
     */
    public function facturar(Request $request)
    {
        $request->validate([
            'cedula' => 'required|string|max:50',
            'fechas' => 'required|array|min:1',
            'fechas.*' => 'required|date',
        ]);

        $cc = trim($request->cedula);
        $fechas = $this->normalizarFechas($request->fechas);

        if (empty($fechas)) {
            return response()->json([
                'ok' => false,
                'message' => 'Debes seleccionar al menos un día válido.',
            ], 422);
        }

        $now = Carbon::now('America/Bogota');
        $operadora = Auth::user()->name ?? Auth::user()->email ?? 'OPERADORA';

        return DB::transaction(function () use ($cc, $fechas, $now, $operadora) {

            $moId = $this->resolverMoIdPorConductor($cc);

            $conductor = DB::table('conductores')
                ->where('conduc_cc', $cc)
                ->select('conduc_cc', 'conduc_nombres')
                ->lockForUpdate()
                ->first();

            if (!$conductor) {
                return response()->json(['ok' => false, 'message' => 'Conductor no encontrado.'], 404);
            }

            $movilTaxi = DB::table('movil')
                ->where('mo_conductor', $cc)
                ->orderByDesc('mo_id')
                ->value('mo_taxi');

            $valorServicio = (int) (DB::table('valorservicio')
                ->orderByDesc('fecha')
                ->value('vs_valor') ?? 0);

            $servicios = collect();
            if ($moId) {
                $servicios = DB::table('disponibles')
                    ->where('dis_conmo', $moId)
                    ->where('dis_servicio', 1)
                    ->where('dis_facturado', 0)
                    ->whereIn('dis_fecha', $fechas)
                    ->lockForUpdate()
                    ->pluck('dis_id');
            }

            $sanciones = DB::table('sancion as s')
                ->join('tiposancion as t', 't.tpsa_id', '=', 's.sancion_tipo')
                ->where('s.sancion_condu', $cc)
                ->where('s.sancion_activa', 1)
                ->where('s.sancion_facturada', 0)
                ->whereIn('s.sancion_fecha', $fechas)
                ->lockForUpdate()
                ->select('s.sancion_id', 't.tpsa_valor')
                ->get();

            $totalServicios = $servicios->count() * $valorServicio;
            $totalSanciones = (int) $sanciones->sum('tpsa_valor');
            $total = $totalServicios + $totalSanciones;

            if ($servicios->count() === 0 && $sanciones->count() === 0) {
                return response()->json([
                    'ok' => false,
                    'message' => 'No hay pendientes para facturar en los días seleccionados.',
                ], 422);
            }

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