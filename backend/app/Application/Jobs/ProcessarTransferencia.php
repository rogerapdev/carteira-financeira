<?php

namespace App\Application\Jobs;

use App\Application\DTOs\TransacaoDTO;
use App\Application\Interfaces\TransacaoServiceInterface;
use App\Application\Interfaces\NotificacaoServiceInterface;
use App\Application\Services\MonitoramentoJobsService;
use App\Domain\Exceptions\SaldoInsuficienteException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessarTransferencia implements ShouldQueue
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
     * Indica se o job deve ser tentado novamente em caso de erro.
     *
     * @var bool
     */
    public $retryErroSaldo = false;

    /**
     * @param TransacaoDTO $transacaoDTO
     * @param bool $retryErroSaldo Indica se o job deve ser tentado novamente em caso de saldo insuficiente
     */
    private string $jobId;
    private MonitoramentoJobsService $monitoramentoJobsService;

    public function __construct(
        private TransacaoDTO $transacaoDTO,
        bool $retryErroSaldo = false
    ) {
        $this->retryErroSaldo = $retryErroSaldo;
        $this->jobId = (string) Str::uuid();
        $this->monitoramentoJobsService = app(MonitoramentoJobsService::class);
    }

    /**
     * Execute the job.
     */
    public function handle(TransacaoServiceInterface $servicoTransacao): void
    {
        $this->monitoramentoJobsService->registrarInicioJob(
            $this->jobId,
            self::class,
            ['transacao_id' => $this->transacaoDTO->id]
        );

        try {
            // Verifica se já existe uma transação com a mesma chave de idempotência
            if ($this->transacaoDTO->transaction_key) {
                $transacaoExistente = $servicoTransacao->buscarTransacaoPorTransactionKey($this->transacaoDTO->transaction_key);
                
                if ($transacaoExistente) {
                    Log::info('Transferência ignorada por idempotência', [
                        'transaction_key' => $this->transacaoDTO->transaction_key,
                        'id_transacao_existente' => $transacaoExistente->id,
                        'status' => $transacaoExistente->status
                    ]);
                    return; // Encerra o processamento, pois a transação já existe
                }
            }
            
            // Processa a transferência normalmente
            $transacao = $servicoTransacao->criarTransferencia($this->transacaoDTO);
            
            $this->monitoramentoJobsService->registrarSuccessoJob(
                $this->jobId,
                self::class,
                ['transacao_id' => $this->transacaoDTO->id]
            );

            Log::info('Transferência processada com sucesso', [
                'id_transacao' => $transacao->id,
                'public_id' => $transacao->public_id,
                'valor' => $transacao->amount,
                'tipo' => $transacao->type,
                'conta_origem' => $transacao->detalhes?->from_account_id,
                'conta_destino' => $transacao->detalhes?->to_account_id,
                'transaction_key' => $transacao->transaction_key
            ]);
            
            // Envia notificações sobre a transação
            app(NotificacaoServiceInterface::class)->notificarTransacaoConcluida($transacao);
        } catch (SaldoInsuficienteException $e) {
            Log::warning('Saldo insuficiente para transferência', [
                'erro' => $e->getMessage(),
                'dados_transacao' => $this->transacaoDTO->paraArray(),
                'transaction_key' => $this->transacaoDTO->transaction_key
            ]);
            
            // Registra o erro na transação especificando o problema de saldo
            $this->registrarErroTransacao(
                $servicoTransacao, 
                "Saldo insuficiente para transferência: {$e->getMessage()}"
            );
            
            // Não tenta novamente em caso de saldo insuficiente, a menos que explicitamente configurado
            if (!$this->retryErroSaldo) {
                $this->delete();
            }
            
            $this->monitoramentoJobsService->registrarFalhaJob(
                $this->jobId,
                self::class,
                $e,
                ['transacao_id' => $this->transacaoDTO->id]
            );
            throw $e;
        } catch (\Exception $e) {
            Log::error('Falha ao processar transferência', [
                'erro' => $e->getMessage(),
                'dados_transacao' => $this->transacaoDTO->paraArray(),
                'transaction_key' => $this->transacaoDTO->transaction_key
            ]);
            
            // Tenta registrar o erro na transação, se possível
            $this->registrarErroTransacao($servicoTransacao, $e->getMessage());
            
            $this->monitoramentoJobsService->registrarFalhaJob(
                $this->jobId,
                self::class,
                $e,
                ['transacao_id' => $this->transacaoDTO->id]
            );
            throw $e;
        }
    }

    /**
     * Registra o erro em uma transação existente ou cria uma transação com erro.
     *
     * @param TransacaoServiceInterface $servicoTransacao
     * @param string $mensagemErro
     * @return void
     */
    private function registrarErroTransacao(
        TransacaoServiceInterface $servicoTransacao, 
        string $mensagemErro
    ): void {
        try {
            // Verifica se já existe uma transação com a mesma chave de idempotência
            if ($this->transacaoDTO->transaction_key) {
                $transacaoExistente = $servicoTransacao->buscarTransacaoPorTransactionKey($this->transacaoDTO->transaction_key);
                
                if ($transacaoExistente) {
                    // Atualiza a transação existente com o erro
                    $transacaoExistente->status = \App\Domain\Entities\Transacao::STATUS_FALHA;
                    $transacaoExistente->error_message = $mensagemErro;
                    $servicoTransacao->atualizarTransacao($transacaoExistente);
                    Log::info('Transação atualizada com erro', [
                        'id' => $transacaoExistente->id,
                        'public_id' => $transacaoExistente->public_id,
                        'erro' => $mensagemErro
                    ]);
                    
                    // Notifica o usuário sobre a falha
                    app(NotificacaoServiceInterface::class)->notificarTransacaoFalha($transacaoExistente);
                    
                    return;
                }
            }
            
            // Se não encontrar por idempotência, tenta encontrar por outros critérios
            if (empty($this->transacaoDTO->fromAccountId)) {
                return;
            }
            
            $transacoesPendentes = $servicoTransacao->buscarTransacoesPorConta(
                $this->transacaoDTO->fromAccountId, 
                1
            );
            
            $transacaoPendente = $transacoesPendentes->first(function ($transacao) {
                return $transacao->estaPendente() && 
                       $transacao->ehTransferencia() &&
                       $transacao->amount == $this->transacaoDTO->amount;
            });
            
            if ($transacaoPendente) {
                // Atualiza a transação existente com o erro
                $transacaoPendente->status = \App\Domain\Entities\Transacao::STATUS_FALHA;
                $transacaoPendente->error_message = $mensagemErro;
                $servicoTransacao->atualizarTransacao($transacaoPendente);
                Log::info('Transação atualizada com erro', [
                    'id' => $transacaoPendente->id,
                    'public_id' => $transacaoPendente->public_id,
                    'erro' => $mensagemErro
                ]);
                
                // Notifica o usuário sobre a falha
                app(NotificacaoServiceInterface::class)->notificarTransacaoFalha($transacaoPendente);
            } else {
                // Cria uma nova transação com status de falha para registrar o erro
                $dados = $this->transacaoDTO->paraArray();
                $dados['status'] = \App\Domain\Entities\Transacao::STATUS_FALHA;
                $dados['error_message'] = $mensagemErro;
                
                $transacao = $servicoTransacao->criar($dados);
                Log::info('Criada nova transação com erro', [
                    'id' => $transacao->id,
                    'public_id' => $transacao->public_id,
                    'erro' => $mensagemErro
                ]);
                
                // Notifica o usuário sobre a falha
                app(NotificacaoServiceInterface::class)->notificarTransacaoFalha($transacao);
            }
        } catch (\Exception $e) {
            Log::error('Não foi possível registrar erro na transação', [
                'erro_original' => $mensagemErro,
                'erro_ao_registrar' => $e->getMessage()
            ]);
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
        // Verificar se é erro de saldo insuficiente
        if ($exception instanceof SaldoInsuficienteException) {
            Log::warning('Transferência falhou por saldo insuficiente', [
                'erro' => $exception->getMessage(),
                'dados_transacao' => $this->transacaoDTO->paraArray(),
                'transaction_key' => $this->transacaoDTO->transaction_key
            ]);
        } else {
            Log::error('Processamento da transferência falhou após máximo de tentativas', [
                'erro' => $exception->getMessage(),
                'dados_transacao' => $this->transacaoDTO->paraArray(),
                'transaction_key' => $this->transacaoDTO->transaction_key
            ]);
        }
    }
}