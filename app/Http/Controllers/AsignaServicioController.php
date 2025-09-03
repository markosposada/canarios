<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AsignaServicioController extends Controller
{
    /** Mostrar vista principal de asignación */
    public function vista()
    {
        return view('servicios.asignar');
    }

    /** Listado de móviles activos (con filtro q) */
    public function movilesActivos(Request $request)
    {
        $hoy = Carbon::today()->toDateString();
        $q   = trim($request->get('q', ''));

        $moviles = DB::table('movil')
            ->join('conductores', 'movil.mo_conductor', '=', 'conductores.conduc_cc')
            ->leftJoin('taxi', 'taxi.ta_movil', '=', 'movil.mo_taxi')
            ->leftJoin('disponibles', function($join) use ($hoy) {
                $join->on('disponibles.dis_conmo', '=', 'movil.mo_id')
                     ->whereDate('disponibles.dis_fecha', $hoy);
            })
            ->where('movil.mo_estado', 1)
            ->when($q !== '', function($sql) use ($q) {
                $like = "%{$q}%";
                $sql->where(function($w) use ($like) {
                    $w->whereRaw('CAST(movil.mo_taxi AS CHAR) LIKE ?', [$like])
                      ->orWhere('conductores.conduc_nombres', 'like', $like)
                      ->orWhere('taxi.ta_placa', 'like', $like);
                });
            })
            ->groupBy(
                'movil.mo_id',
                'movil.mo_taxi',
                'taxi.ta_placa',
                'conductores.conduc_nombres',
                'movil.mo_conductor'
            )
            ->selectRaw('
                movil.mo_id,
                movil.mo_taxi,
                COALESCE(taxi.ta_placa, "") AS placa,
                conductores.conduc_nombres AS nombre_conductor,
                COUNT(disponibles.dis_conmo) AS cantidad
            ')
            ->orderBy('cantidad')
            ->orderBy('movil.mo_taxi')
            ->get();

        return response()->json($moviles);
    }

    /** Genera token numérico de 3 dígitos (000-999), único entre tokens no vencidos */
    private function generarTokenUnico3(): string
    {
        do {
            $token = str_pad((string) random_int(0, 999), 3, '0', STR_PAD_LEFT);

            $existe = DB::table('disponibles')
                ->where('dis_token', $token)
                ->where('dis_token_expires_at', '>=', Carbon::now())
                ->exists();
        } while ($existe);

        return $token;
    }

    /** Registrar servicio + token */
    public function registrar(Request $request)
    {
        $request->validate([
            'conmo'      => 'required',
            'usuario'    => 'required|string|max:200',
            'direccion'  => 'required|string|max:255',
            'barrio'     => 'required|string|max:120',
            'operadora'  => 'required|string|max:120',
        ]);

        
        $now = \Carbon\Carbon::now(config('app.timezone')); // America/Bogota

        $token = $this->generarTokenUnico3();
        $exp   = (clone $now)->addDay();

        DB::table('disponibles')->insert([
            'dis_conmo'            => $request->conmo, // ⚠️ si guardas la cédula en vez de mo_id, ajusta aquí
            'dis_dire'             => trim($request->direccion).' - '.$request->barrio,
            'dis_usuario'          => $request->usuario,
            'dis_fecha' => $now->toDateString(),      // YYYY-MM-DD
            'dis_hora'  => $now->format('H:i:s'),     // HH:mm:ss
            'dis_operadora'        => $request->operadora,
            'dis_token'            => $token,
            'dis_token_expires_at' => $exp,
        ]);

        return response()->json([
            'success'    => true,
            'token'      => $token,
            'expires_at' => $exp->toDateTimeString()
        ]);
    }

    /** Vista de consulta por token */
    public function vistaConsulta()
    {
        return view('servicios.consulta-token');
    }

    /** Buscar datos de un servicio a partir del token */
    public function buscarPorToken(Request $request)
    {
        $token = strtoupper(trim($request->get('token', '')));

        if (!preg_match('/^\d{3}$/', $token)) {
            return response()->json([
                'found' => false,
                'message' => 'Token inválido. Debe ser un número de 3 dígitos.'
            ], 400);
        }

        $now = Carbon::now();

        $row = DB::table('disponibles')
            ->join('movil', 'disponibles.dis_conmo', '=', 'movil.mo_id') // ⚠️ si usas mo_conductor cámbialo
            ->join('conductores', 'movil.mo_conductor', '=', 'conductores.conduc_cc')
            ->leftJoin('taxi', 'taxi.ta_movil', '=', 'movil.mo_taxi')
            ->leftJoin('tarifas_servicio as t', 't.anio', '=', DB::raw('YEAR(disponibles.dis_fecha)'))
            ->where('disponibles.dis_token', $token)
            ->where('disponibles.dis_token_expires_at', '>=', $now)
            ->orderByDesc('disponibles.dis_fecha')
            ->orderByDesc('disponibles.dis_hora')
            ->selectRaw("
                disponibles.dis_usuario      AS usuario,
                disponibles.dis_dire         AS direccion,
                disponibles.dis_fecha        AS fecha,
                disponibles.dis_hora         AS hora,
                conductores.conduc_nombres   AS conductor,
                movil.mo_taxi                AS movil,
                COALESCE(taxi.ta_placa,'')   AS placa,
                t.valor                      AS valor
            ")
            ->first();

        if (!$row) {
            return response()->json([
                'found' => false,
                'message' => 'Token no encontrado o vencido.'
            ]);
        }

        return response()->json([
            'found'     => true,
            'usuario'   => $row->usuario,
            'direccion' => $row->direccion,
            'fecha'     => $row->fecha,
            'hora'      => $row->hora,
            'conductor' => $row->conductor,
            'movil'     => $row->movil,
            'placa'     => $row->placa,
            'valor'     => $row->valor,
        ]);
    }
}
