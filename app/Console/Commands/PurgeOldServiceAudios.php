<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class PurgeOldServiceAudios extends Command
{
    protected $signature = 'audios:purge {--hours=48 : Hours to keep}';
    protected $description = 'Elimina audios de servicios con más de X horas y limpia dis_audio';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $cutoff = Carbon::now()->subHours($hours);

        $rows = DB::table('disponibles')
            ->whereNotNull('dis_audio')
            ->where('dis_audio', '!=', '')
            ->whereRaw('TIMESTAMP(dis_fecha, dis_hora) < ?', [$cutoff->toDateTimeString()])
            ->select('dis_id', 'dis_audio')
            ->limit(5000)
            ->get();

        $deleted = 0;
        $missing = 0;

        foreach ($rows as $r) {
            $path = $r->dis_audio;

            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
                $deleted++;
            } else {
                $missing++;
            }

            DB::table('disponibles')
                ->where('dis_id', $r->dis_id)
                ->update(['dis_audio' => null]);
        }

        $this->info("OK. Eliminados: {$deleted}, no encontrados: {$missing}, procesados: ".count($rows));
        return self::SUCCESS;
    }
}
