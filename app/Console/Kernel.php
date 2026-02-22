<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Comandos personalizados
     */
    protected $commands = [
        \App\Console\Commands\AutoFacturarPendientes::class,
    ];

    /**
     * Definición del scheduler
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('canarios:auto-facturar --days=3')
            ->dailyAt('02:00')
            ->timezone('America/Bogota')
            ->withoutOverlapping();
        
        $schedule->command('canarios:levantar-sanciones')
    ->everyThirtyMinutes()
    ->timezone('America/Bogota')
    ->withoutOverlapping();
    }

    

    /**
     * Carga automática de comandos
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
