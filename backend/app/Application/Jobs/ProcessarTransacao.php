<?php

namespace App\Application\Jobs;

use App\Application\DTOs\TransacaoDTO;
use App\Application\Interfaces\TransacaoServiceInterface;
use App\Application\Services\MonitoramentoJobsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * @deprecated Este job foi substituído por ProcessarDeposito, ProcessarTransferencia e ProcessarEstorno.
 * Mantido por compatibilidade com filas existentes.
 */
class ProcessarTransacao implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * @param TransacaoDTO $transacaoDTO
     */
    public function __construct(
        private TransacaoDTO $transacaoDTO
    ) {
    }

    /**
     * Execute the job.
     * 
     * Redireciona para o novo job ProcessarDeposito.
     */
    public function handle(TransacaoServiceInterface $servicoTransacao): void
    {
        $monitoramento = app(MonitoramentoJobsService::class);
        $jobId = Str::uuid()->toString();
        
        try {
            $monitoramento->iniciarJob($jobId, 'ProcessarTransacao', $this->transacaoDTO->paraArray());
            
            Log::warning('Job ProcessarTransacao está obsoleto. Use ProcessarDeposito em vez disso.');
        
            // Redireciona para o novo job
            (new ProcessarDeposito($this->transacaoDTO))->handle($servicoTransacao);
            
            $monitoramento->finalizarJob($jobId);
        } catch (\Exception $e) {
            $monitoramento->falharJob($jobId, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Processamento da transação falhou após máximo de tentativas', [
            'erro' => $exception->getMessage(),
            'dados_transacao' => $this->transacaoDTO->paraArray()
        ]);
    }
}