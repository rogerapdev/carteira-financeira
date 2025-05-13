<?php

namespace App\Application\Jobs;

use App\Application\DTOs\TransacaoDTO;
use App\Application\Interfaces\TransacaoServiceInterface;
use App\Application\Interfaces\NotificacaoServiceInterface;
use App\Application\Services\MonitoramentoJobsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessarDeposito implements ShouldQueue
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
     */
    public function handle(TransacaoServiceInterface $servicoTransacao): void
    {
        try {
            // Verifica se já existe uma transação com a mesma chave de idempotência
            if ($this->transacaoDTO->transaction_key) {
                $transacaoExistente = $servicoTransacao->buscarTransacaoPorTransactionKey($this->transacaoDTO->transaction_key);
                
                if ($transacaoExistente) {
                    Log::info('Depósito ignorado por idempotência', [
                        'transaction_key' => $this->transacaoDTO->transaction_key,
                        'id_transacao_existente' => $transacaoExistente->id,
                        'status' => $transacaoExistente->status
                    ]);
                    return; // Encerra o processamento, pois a transação já existe
                }
            }

            // Processa o depósito
            $transacao = $servicoTransacao->criarDeposito($this->transacaoDTO);
            
            Log::info('Depósito processado com sucesso', [
                'id_transacao' => $transacao->id,
                'public_id' => $transacao->public_id,
                'valor' => $transacao->amount,
                'tipo' => $transacao->type,
                'conta_destino' => $transacao->detalhes?->to_account_id,
                'transaction_key' => $transacao->transaction_key
            ]);
            
            // Notifica o usuário sobre o depósito recebido
            app(NotificacaoServiceInterface::class)->notificarTransacaoConcluida(
                $transacao, 
                false, // Não notificar remetente (não existe nesse caso)
                true // Notificar somente o destinatário
            );

        } catch (\Exception $e) {
            Log::error('Falha ao processar depósito', [
                'erro' => $e->getMessage(),
                'dados_transacao' => $this->transacaoDTO->paraArray(),
                'transaction_key' => $this->transacaoDTO->transaction_key
            ]);
            
            // Registra a falha em uma transação
            $this->registrarErroTransacao($servicoTransacao, $e->getMessage());
            
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
        Log::error('Processamento do depósito falhou após máximo de tentativas', [
            'erro' => $exception->getMessage(),
            'dados_transacao' => $this->transacaoDTO->paraArray(),
            'transaction_key' => $this->transacaoDTO->transaction_key
        ]);
    }
}