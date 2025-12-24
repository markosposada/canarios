<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\User;
use App\Notifications\ServicioAsignadoNotification;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;



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
                    ->whereDate('disponibles.dis_fecha', $hoy)
                    ->where(function($w) {
                        $w->whereNull('disponibles.dis_servicio')
                          ->orWhere('disponibles.dis_servicio', 1);
                    });
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
        $letras = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
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
        'operadora'  => 'required|string|max:200',
        'audio_path' => 'nullable|string|max:1000',
    ]);

    // Buscar móvil + placa + cedula conductor (mo_conductor = cédula)
    $movil = DB::table('movil')
        ->leftJoin('taxi', 'movil.mo_taxi', '=', 'taxi.ta_movil')
        ->where('movil.mo_id', $request->conmo)
        ->select('movil.mo_taxi', 'movil.mo_conductor', 'taxi.ta_placa')
        ->first();

    $now   = Carbon::now(config('app.timezone'));
    $token = $this->generarTokenUnicoMix();
    $exp   = (clone $now)->addDay();

    // Registrar servicio (retorna ID)
    $disId = DB::table('disponibles')->insertGetId([
        'dis_conmo'            => $request->conmo,
        'dis_servicio'         => 1,
        'dis_dire'             => trim($request->direccion),
        'dis_audio'            => $request->audio_path ?: null,
        'dis_usuario'          => trim($request->usuario),
        'dis_fecha'            => $now->toDateString(),
        'dis_hora'             => $now->format('H:i:s'),
        'dis_operadora'        => trim($request->operadora),
        'dis_token'            => $token,
        'dis_token_expires_at' => $exp,
    ]);

    /**
     * NOTIFICAR AL CONDUCTOR
     * movil.mo_conductor = cédula
     * users.cedula = cédula
     */
    try {
        $conductorUser = null;

        if ($movil && !empty($movil->mo_conductor)) {
            $conductorUser = User::where('cedula', $movil->mo_conductor)->first();
        }

        // 1) Notificación en BD (campana)
        if ($conductorUser) {
            $conductorUser->notify(new ServicioAsignadoNotification([
                'servicio_id' => $disId,
                'direccion'   => trim($request->direccion),
                'movil'       => $movil->mo_taxi ?? null,
                'placa'       => $movil->ta_placa ?? null,
                'token'       => $token,
                'operadora'   => trim($request->operadora),
                'fecha'       => $now->toDateString(),
                'hora'        => $now->format('H:i:s'),
            ]));
        }

        // 2) Push real (Android/Chrome)
        if ($conductorUser) {
            $subs = DB::table('push_subscriptions')
                ->where('user_id', $conductorUser->id)
                ->get();

            if ($subs->count() > 0) {
                $webPush = new WebPush([
                    'VAPID' => [
                        'subject'    => 'mailto:soporte@loscanarios.com', // cámbialo si quieres
                        'publicKey'  => config('services.webpush.public_key'),
                        'privateKey' => config('services.webpush.private_key'),
                    ],
                ]);

                foreach ($subs as $s) {
                    $subscription = Subscription::create([
                        'endpoint' => $s->endpoint,
                        'keys' => [
                            'p256dh' => $s->p256dh,
                            'auth'   => $s->auth,
                        ],
                    ]);

                    $payload = json_encode([
                        'title' => '🚕 Nuevo servicio asignado',
                        'body'  => trim($request->direccion),
                        'data'  => [
                            // ✅ aquí la ruta exacta que me confirmaste
                            'url'   => url('/conductor/servicios-asignados'),
                            'token' => $token,
                            'movil' => $movil->mo_taxi ?? null,
                        ],
                    ]);

                    $webPush->queueNotification($subscription, $payload);
                }

                foreach ($webPush->flush() as $report) {
                    // opcional: limpiar subs inválidas
                }
            }
        }
    } catch (\Throwable $e) {
        report($e); // no romper el registro por fallo de push
    }

    return response()->json([
        'success'    => true,
        'token'      => $token,
        'expires_at' => $exp->toDateTimeString(),
        'movil'      => $movil->mo_taxi ?? null,
        'placa'      => $movil->ta_placa ?? null,
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

        if (!preg_match('/^[A-Z]\d{2}$/', $token)) {
            return response()->json([
                'found' => false,
                'message' => 'Token inválido. Debe ser una letra seguida de 2 números (ej: A07).'
            ], 400);
        }

        $now = Carbon::now();

        $row = DB::table('disponibles')
            ->join('movil', 'disponibles.dis_conmo', '=', 'movil.mo_id')
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
                disponibles.dis_operadora    AS operadora,
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
            'operadora' => $row->operadora,
            'valor'     => $row->valor,
        ]);
    }

    /** Mostrar la vista del listado */
    public function listadoVista()
    {
        return view('servicios.listado');
    }

    /** Devolver servicios para la tabla (con filtros opcionales) */
    /** Devolver servicios para la tabla (con filtros opcionales) */
public function listarServicios(Request $request)
{
    $desde = $request->query('desde');
    $hasta = $request->query('hasta');
    $q     = trim($request->query('q', ''));

    $now = Carbon::now()->toDateTimeString();

    $sql = DB::table('disponibles')
        ->join('movil', 'disponibles.dis_conmo', '=', 'movil.mo_id')
        ->join('conductores', 'movil.mo_conductor', '=', 'conductores.conduc_cc')
        ->leftJoin('taxi', 'taxi.ta_movil', '=', 'movil.mo_taxi')
        // ⬇️ ELIMINADO: ->leftJoin('usuarios as op', ...)
        ->selectRaw("
            disponibles.dis_id          AS id,
            disponibles.dis_fecha       AS fecha,
            disponibles.dis_hora        AS hora,
            disponibles.dis_dire        AS direccion,
            disponibles.dis_usuario     AS usuario,
            disponibles.dis_token       AS token,
            disponibles.dis_operadora   AS operadora,
            COALESCE(taxi.ta_placa,'')  AS placa,
            movil.mo_taxi               AS movil,
            conductores.conduc_nombres  AS conductor,
            COALESCE(disponibles.dis_servicio, 1) AS estado,
            CASE
                WHEN COALESCE(disponibles.dis_servicio, 1) = 2 THEN 0
                WHEN TIMESTAMP(disponibles.dis_fecha, disponibles.dis_hora) >= DATE_SUB(?, INTERVAL 1 HOUR)
                    THEN 1
                ELSE 0
            END AS puede_cancelar
        ", [$now]);

    if ($desde) {
        $sql->where('disponibles.dis_fecha', '>=', $desde);
    }
    if ($hasta) {
        $sql->where('disponibles.dis_fecha', '<=', $hasta);
    }

    if ($q !== '') {
        $like = "%{$q}%";
        $sql->where(function ($w) use ($like) {
            $w->where('disponibles.dis_usuario', 'like', $like)
              ->orWhere('disponibles.dis_dire', 'like', $like)
              ->orWhere('disponibles.dis_token', 'like', $like)
              ->orWhere('disponibles.dis_operadora', 'like', $like)
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


    /** Cancelar: pone dis_servicio = 2 (cancelado) */
    public function cancelarServicio($id)
    {
        $affected = DB::table('disponibles')
            ->where('dis_id', $id)
            ->where(function($w){
                $w->whereNull('dis_servicio')->orWhere('dis_servicio', '!=', 2);
            })
            ->update(['dis_servicio' => 2]);

        return response()->json(['success' => $affected > 0]);
    }

  public function subirAudio(Request $request)
{
    try {
        $request->validate([
            // Edge/Chrome graban webm normalmente
            'audio' => 'required|file|mimetypes:audio/webm,video/webm,audio/ogg,audio/wav,audio/mpeg|max:10240',
        ]);

        $file = $request->file('audio');

        // Nombre único
        $name = 'direccion_' . now()->format('Ymd_His') . '_' . Str::random(10) . '.webm';

        // Guarda en storage/app/public/audios_direcciones/
        $path = $file->storeAs('audios_direcciones', $name, 'public');

        return response()->json([
            'success' => true,
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
        ]);
    } catch (\Throwable $e) {
        report($e);

        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}

}
