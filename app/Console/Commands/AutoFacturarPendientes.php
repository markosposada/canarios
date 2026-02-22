<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AutoFacturarPendientes extends Command
{
    protected $signature = 'canarios:auto-facturar {--days=3 : Días de gracia}';
    protected $description = 'Auto-factura servicios y sanciones (>=3 días) pendientes y sanciona al conductor (estado=3).';

    public function handle()
    {
        \Log::info('AUTO-FACTURAR ejecutado', ['now' => now()->toDateTimeString()]);

        $days = (int)$this->option('days');
        if ($days < 1) $days = 3;

        $tz = 'America/Bogota';
        $now = Carbon::now($tz);

        // Todo lo que sea <= corte se considera vencido
        $corte = (clone $now)->subDays($days)->toDateString();

        $operadoraAuto = 'AUTO';

        // Valor servicio más reciente
        $valorServicio = (int) (DB::table('valorservicio')->orderByDesc('fecha')->value('vs_valor') ?? 0);
        if ($valorServicio <= 0) {
            $this->error('No hay valor de servicio válido en valorservicio (vs_valor).');
            return Command::FAILURE;
        }

        /**
         * Lista base de conductores/móviles que tengan pendientes vencidos por:
         * - servicios (disponibles) vencidos
         * - sanciones (sancion) vencidas
         *
         * OJO: en sanciones usamos sancion_fecha <= corte
         */
        $conServiciosVencidos = DB::table('disponibles as d')
            ->join('movil as m', 'm.mo_id', '=', 'd.dis_conmo')
            ->where('d.dis_servicio', 1)
            ->where('d.dis_facturado', 0)
            ->whereDate('d.dis_fecha', '<=', $corte)
            ->selectRaw('m.mo_id as mo_id, m.mo_taxi as mo_taxi, m.mo_conductor as conductor_cc');

        $conSancionesVencidas = DB::table('sancion as s')
            ->join('movil as m', 'm.mo_conductor', '=', 's.sancion_condu')
            ->where('s.sancion_activa', 1)
            ->where('s.sancion_facturada', 0)
            ->whereDate('s.sancion_fecha', '<=', $corte)   // ✅ SOLO SI CUMPLIÓ 3 DÍAS
            ->selectRaw('m.mo_id as mo_id, m.mo_taxi as mo_taxi, m.mo_conductor as conductor_cc');

        $grupos = $conServiciosVencidos
            ->union($conSancionesVencidas)
            ->get()
            ->unique(function ($r) {
                return $r->mo_id . '|' . $r->conductor_cc;
            })
            ->values();

        if ($grupos->count() === 0) {
            $this->info("No hay pendientes vencidos para auto-facturar. Corte: {$corte}");
            return Command::SUCCESS;
        }

        $this->info("Encontrados {$grupos->count()} móviles con pendientes vencidos. Corte: {$corte}");

        $procesados = 0;

        foreach ($grupos as $g) {
            DB::transaction(function () use ($g, $now, $corte, $valorServicio, $operadoraAuto, &$procesados) {

                // Bloquear móvil + conductor
                $movil = DB::table('movil as m')
                    ->join('conductores as c', 'c.conduc_cc', '=', 'm.mo_conductor')
                    ->where('m.mo_id', $g->mo_id)
                    ->select('m.mo_taxi','m.mo_id','m.mo_conductor','c.conduc_estado')
                    ->lockForUpdate()
                    ->first();

                if (!$movil) return;

                // 1) Servicios vencidos pendientes (lock)
                $serviciosIds = DB::table('disponibles')
                    ->where('dis_conmo', $movil->mo_id)
                    ->where('dis_servicio', 1)
                    ->where('dis_facturado', 0)
                    ->whereDate('dis_fecha', '<=', $corte)
                    ->lockForUpdate()
                    ->pluck('dis_id');

                // 2) Sanciones vencidas activas pendientes (lock)
                $sanciones = DB::table('sancion as s')
                    ->join('tiposancion as t', 't.tpsa_id', '=', 's.sancion_tipo')
                    ->where('s.sancion_condu', $movil->mo_conductor)
                    ->where('s.sancion_activa', 1)
                    ->where('s.sancion_facturada', 0)
                    ->whereDate('s.sancion_fecha', '<=', $corte)  // ✅ SOLO SI CUMPLIÓ 3 DÍAS
                    ->lockForUpdate()
                    ->select('s.sancion_id', 't.tpsa_valor')
                    ->get();

                // Si no hay nada realmente bajo lock, no hacemos nada
                if ($serviciosIds->count() === 0 && $sanciones->count() === 0) {
                    return;
                }

                $totalServicios = $serviciosIds->count() * $valorServicio;
                $totalSanciones = (int) $sanciones->sum('tpsa_valor');
                $total = $totalServicios + $totalSanciones;

                // Cabecera
                $foId = DB::table('facturacion_operadora')->insertGetId([
                    'fo_movil' => $movil->mo_taxi,
                    'fo_conductor' => $movil->mo_conductor,
                    'fo_fecha' => $now->toDateString(),
                    'fo_hora' => $now->format('H:i:s'),
                    'fo_operadora' => $operadoraAuto,
                    'fo_valor_servicio' => $valorServicio,
                    'fo_total_servicios' => $totalServicios,
                    'fo_total_sanciones' => $totalSanciones,
                    'fo_total' => $total,
                ]);

                // Detalles: servicios
                foreach ($serviciosIds as $disId) {
                    DB::table('facturacion_operadora_det')->insert([
                        'fo_id' => $foId,
                        'fod_tipo' => 'SERVICIO',
                        'fod_ref_id' => (int)$disId,
                        'fod_valor' => $valorServicio,
                    ]);
                }

                // Detalles: sanciones
                foreach ($sanciones as $s) {
                    DB::table('facturacion_operadora_det')->insert([
                        'fo_id' => $foId,
                        'fod_tipo' => 'SANCION',
                        'fod_ref_id' => (int)$s->sancion_id,
                        'fod_valor' => (int)$s->tpsa_valor,
                    ]);
                }

                // Marcar servicios facturados
                if ($serviciosIds->count() > 0) {
                    DB::table('disponibles')
                        ->whereIn('dis_id', $serviciosIds->toArray())
                        ->update([
                            'dis_facturado' => 1,
                            'dis_facturado_at' => $now->format('Y-m-d H:i:s'),
                            'dis_facturado_operadora' => $operadoraAuto,
                        ]);
                }

                // Marcar sanciones facturadas
                if ($sanciones->count() > 0) {
                    DB::table('sancion')
                        ->whereIn('sancion_id', $sanciones->pluck('sancion_id')->toArray())
                        ->update([
                            'sancion_facturada' => 1,
                            'sancion_facturada_at' => $now->format('Y-m-d H:i:s'),
                            'sancion_facturada_operadora' => $operadoraAuto,
                        ]);
                }

                // Sancionar conductor por no presentarse: estado = 3
                DB::table('conductores')
                    ->where('conduc_cc', $movil->mo_conductor)
                    ->update(['conduc_estado' => 3]);

                $procesados++;
            });
        }

        $this->info("✅ Auto-facturados: {$procesados} móviles/conductores. Estado conductor -> 3.");
        return Command::SUCCESS;
    }
}
