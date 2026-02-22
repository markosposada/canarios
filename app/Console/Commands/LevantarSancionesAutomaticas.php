<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LevantarSancionesAutomaticas extends Command
{
    protected $signature = 'canarios:levantar-sanciones';
    protected $description = 'Levanta sanciones automáticamente cuando se cumplen las horas del tipo de sanción';

    public function handle()
    {
        $tz = 'America/Bogota';
        $now = Carbon::now($tz);

        $this->info("Revisando sanciones activas...");

        // Traer sanciones activas con su tipo
        $sanciones = DB::table('sancion as s')
            ->join('tiposancion as t', 't.tpsa_id', '=', 's.sancion_tipo')
            ->where('s.sancion_activa', 1)
            ->select(
                's.sancion_id',
                's.sancion_condu',
                's.sancion_fecha',
                's.sancion_hora',
                't.tpsa_horas'
            )
            ->get();

        if ($sanciones->count() === 0) {
            $this->info("No hay sanciones activas.");
            return Command::SUCCESS;
        }

        $levantadas = 0;

        foreach ($sanciones as $s) {

            // Construir fecha inicio
            $inicio = Carbon::parse(
                $s->sancion_fecha . ' ' . $s->sancion_hora,
                $tz
            );

            $fin = (clone $inicio)->addHours((int)$s->tpsa_horas);

            // Si ya pasó el tiempo
            if ($now->greaterThanOrEqualTo($fin)) {

                DB::transaction(function () use ($s, $now, &$levantadas) {

                    // Levantar sanción
                    DB::table('sancion')
                        ->where('sancion_id', $s->sancion_id)
                        ->update([
                            'sancion_activa' => 0,
                            'sancion_levantada_fecha' => $now->toDateString(),
                            'sancion_levantada_hora' => $now->format('H:i:s'),
                            'sancion_levantada_operadora' => 'AUTO',
                            'sancion_levantada_motivo' => 'Cumplió tiempo automáticamente',
                        ]);

                    // Verificar si el conductor aún tiene sanciones activas
                    $tieneActivas = DB::table('sancion')
                        ->where('sancion_condu', $s->sancion_condu)
                        ->where('sancion_activa', 1)
                        ->exists();

                    // Si no tiene más sanciones activas → activar conductor
                    if (!$tieneActivas) {
                        DB::table('conductores')
                            ->where('conduc_cc', $s->sancion_condu)
                            ->update([
                                'conduc_estado' => 1
                            ]);
                    }

                    $levantadas++;
                });
            }
        }

        $this->info("Sanciones levantadas automáticamente: {$levantadas}");

        return Command::SUCCESS;
    }
}