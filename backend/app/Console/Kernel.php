<?php

namespace App\Console;

use App\Application\Jobs\ProcessarNotificacoesPendentes;
use App\Console\Commands\GerarRelatorioTransacoes;
use App\Console\Commands\TestarAutorizacao;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        GerarRelatorioTransacoes::class,
        TestarAutorizacao::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Gerar relatório diário de transações à meia-noite
        $schedule->command('relatorio:transacoes')
            ->daily()
            ->appendOutputTo(storage_path('logs/reports.log'));
            
        // Manutenção da fila
        $schedule->command('queue:work --stop-when-empty --tries=3')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/queue.log'));
        
        // Limpeza de dados
        $schedule->command('sanctum:prune-expired --hours=24')
            ->daily();
            
        // Processar notificações pendentes
        $schedule->job(new ProcessarNotificacoesPendentes(50))
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/notifications.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
