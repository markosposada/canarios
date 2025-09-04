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

    /** Genera token: 1 letra + 2 números (A00–Z99), único entre tokens no vencidos */
    private function generarTokenUnicoMix(): string
    {
        $letras = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'; // puedes excluir letras si quieres evitar confusiones
        do {
            $letra = $letras[random_int(0, strlen($letras) - 1)];
            $num   = str_pad((string) random_int(0, 99), 2, '0', STR_PAD_LEFT);
            $token = $letra . $num;

            $existe = DB::table('disponibles')
                ->where('dis_token', $token)
                ->where('dis_token_expires_at', '>=', Carbon::now())
                ->exists();
        } while ($existe);

        return $token;
    }

    /** Registrar servicio + token (vigencia 24h) */
    public function registrar(Request $request)
    {
        $request->validate([
            'conmo'      => 'required',
            'usuario'    => 'required|string|max:200',
            'direccion'  => 'required|string|max:255',
            'barrio'     => 'required|string|max:120',
            'operadora'  => 'required|string|max:120',
        ]);

        // Usa la zona horaria de la app (config('app.timezone') -> America/Bogota)
        $now   = Carbon::now(config('app.timezone'));
        $token = $this->generarTokenUnicoMix();  // <<-- usar el nuevo generador
        $exp   = (clone $now)->addDay();

        DB::table('disponibles')->insert([
            'dis_conmo'            => $request->conmo, // ⚠️ si guardas la cédula en vez de mo_id, ajusta aquí y los JOINs
            'dis_dire'             => trim($request->direccion).' - '.$request->barrio,
            'dis_usuario'          => $request->usuario,
            'dis_fecha'            => $now->toDateString(),   // YYYY-MM-DD
            'dis_hora'             => $now->format('H:i:s'),  // HH:mm:ss
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

    /** Vista de consulta por token (pública) */
    public function vistaConsulta()
    {
        return view('servicios.consulta-token');
    }

    /** Buscar datos de un servicio a partir del token (1 letra + 2 números) */
    public function buscarPorToken(Request $request)
    {
        $token = strtoupper(trim($request->get('token', '')));

        // patrón: 1 letra + 2 números, p.ej. A07
        if (!preg_match('/^[A-Z]\d{2}$/', $token)) {
            return response()->json([
                'found' => false,
                'message' => 'Token inválido. Debe ser una letra seguida de 2 números (ej: A07).'
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

// Mostrar la vista del listado
// Mostrar la vista del listado
public function listadoVista()
{
    return view('servicios.listado');
}

// Devolver servicios para la tabla (con filtros opcionales)
public function listarServicios(Request $request)
{
    $desde = $request->query('desde'); // YYYY-MM-DD
    $hasta = $request->query('hasta'); // YYYY-MM-DD
    $q     = trim($request->query('q', ''));

    $sql = DB::table('disponibles')
        ->join('movil', 'disponibles.dis_conmo', '=', 'movil.mo_id')
        ->join('conductores', 'movil.mo_conductor', '=', 'conductores.conduc_cc')
        ->leftJoin('taxi', 'taxi.ta_movil', '=', 'movil.mo_taxi')
        ->selectRaw("
            disponibles.dis_id         AS id,          -- << CORREGIDO
            disponibles.dis_fecha      AS fecha,
            disponibles.dis_hora       AS hora,
            disponibles.dis_dire       AS direccion,
            disponibles.dis_usuario    AS usuario,
            disponibles.dis_token      AS token,
            COALESCE(taxi.ta_placa,'') AS placa,
            movil.mo_taxi              AS movil,
            conductores.conduc_nombres AS conductor,
            COALESCE(disponibles.dis_servicio, 1) AS estado
        ");

    if ($desde) { $sql->where('disponibles.dis_fecha', '>=', $desde); }
    if ($hasta) { $sql->where('disponibles.dis_fecha', '<=', $hasta); }

    if ($q !== '') {
        $like = "%{$q}%";
        $sql->where(function ($w) use ($like) {
            $w->where('disponibles.dis_usuario', 'like', $like)
              ->orWhere('disponibles.dis_dire', 'like', $like)
              ->orWhere('disponibles.dis_token', 'like', $like)
              ->orWhere('conductores.conduc_nombres', 'like', $like)
              ->orWhereRaw('CAST(movil.mo_taxi AS CHAR) LIKE ?', [$like])
              ->orWhere('taxi.ta_placa', 'like', $like);
        });
    }

    $rows = $sql->orderByDesc('disponibles.dis_fecha')
                ->orderByDesc('disponibles.dis_hora')
                ->limit(500)
                ->get();

    return response()->json($rows);
}

// Cancelar: pone dis_servicio = 2 (cancelado)
public function cancelarServicio($id)
{
    $affected = DB::table('disponibles')
        ->where('dis_id', $id)   // << CORREGIDO
        ->where(function($w){
            $w->whereNull('dis_servicio')->orWhere('dis_servicio', '!=', 2);
        })
        ->update(['dis_servicio' => 2]);

    return response()->json(['success' => $affected > 0]);
}


}
