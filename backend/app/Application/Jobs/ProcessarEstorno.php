<?php

namespace App\Application\Jobs;

use App\Application\Interfaces\TransacaoServiceInterface;
use App\Application\Interfaces\NotificacaoServiceInterface;
use App\Domain\Exceptions\SaldoInsuficienteException;
use App\Domain\Exceptions\TransacaoException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Application\Services\MonitoramentoJobsService;

class ProcessarEstorno implements ShouldQueue
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
     * O ID da transação a ser estornada (pode ser id interno ou public_id)
     * 
     * @var string|int
     */
    private $idTransacao;
    
    /**
     * Indica se o ID da transação é um public_id
     * 
     * @var bool
     */
    private bool $usaPublicId;
    
    /**
     * Chave de idempotência para esta operação de estorno
     * 
     * @var string|null
     */
    private ?string $transactionKey;

    /**
     * @param string|int $idTransacao ID ou public_id da transação a ser estornada
     * @param string|null $descricao Descrição opcional para o estorno
     * @param bool $usaPublicId Indica se o ID fornecido é um public_id
     * @param bool $retryErroSaldo Indica se o job deve ser tentado novamente em caso de saldo insuficiente
     * @param string|null $transactionKey Chave de idempotência para prevenir duplicação
     */
    public function __construct(
        $idTransacao,
        private ?string $descricao = null,
        bool $usaPublicId = false,
        bool $retryErroSaldo = false,
        ?string $transactionKey = null
    ) {
        $this->idTransacao = $idTransacao;
        $this->usaPublicId = $usaPublicId;
        $this->retryErroSaldo = $retryErroSaldo;
        $this->transactionKey = $transactionKey ?? Str::uuid()->toString();
        $this->jobId = (string) Str::uuid();
        $this->monitoramentoJobsService = app(MonitoramentoJobsService::class);
    }

    /**
     * Execute the job.
     */
    public function handle(
        TransacaoServiceInterface $servicoTransacao
    ): void {
        try {
            // Verifica se já existe uma transação com a mesma chave de idempotência
            if ($this->transactionKey) {
                $transacaoExistente = $servicoTransacao->buscarTransacaoPorTransactionKey($this->transactionKey);
                
                if ($transacaoExistente) {
                    Log::info('Estorno ignorado por idempotência', [
                        'transaction_key' => $this->transactionKey,
                        'id_transacao_existente' => $transacaoExistente->id,
                        'status' => $transacaoExistente->status
                    ]);
                    return; // Encerra o processamento, pois a transação já existe
                }
            }
            
            // Busca a transação original pelo ID
            $transacaoOriginal = $this->usaPublicId 
                ? $servicoTransacao->buscarTransacaoPorPublicId($this->idTransacao)
                : $servicoTransacao->buscarTransacaoPorId($this->idTransacao);
            
            if (!$transacaoOriginal) {
                throw new TransacaoException("Transação original não encontrada para estorno");
            }
            
            // Verifica se a transação pode ser estornada
            if (!$transacaoOriginal->podeSerEstornada()) {
                if ($transacaoOriginal->foiEstornada()) {
                    throw new TransacaoException("Esta transação já foi estornada");
                }
                
                if ($transacaoOriginal->ehEstorno()) {
                    throw new TransacaoException("Não é possível estornar um estorno");
                }
                
                if (!$transacaoOriginal->estaConcluida()) {
                    throw new TransacaoException("Apenas transações concluídas podem ser estornadas");
                }
                
                throw new TransacaoException("Esta transação não pode ser estornada");
            }
            
            // Processa o estorno
            $transacaoEstorno = $servicoTransacao->estornarTransacao(
                $transacaoOriginal->id, 
                $this->descricao,
                $this->transactionKey
            );
            
            Log::info('Estorno processado com sucesso', [
                'id_transacao_estorno' => $transacaoEstorno->id,
                'public_id_estorno' => $transacaoEstorno->public_id,
                'id_transacao_original' => $transacaoOriginal->id,
                'public_id_original' => $transacaoOriginal->public_id,
                'valor' => $transacaoEstorno->amount,
                'tipo' => $transacaoEstorno->type
            ]);
            
            // Notifica os usuários envolvidos sobre o estorno
            app(NotificacaoServiceInterface::class)->notificarEstornoConcluido(
                $transacaoEstorno,
                $transacaoOriginal
            );
        } catch (SaldoInsuficienteException $e) {
            Log::warning('Saldo insuficiente para realizar estorno', [
                'erro' => $e->getMessage(),
                'id_transacao' => $this->idTransacao,
                'eh_public_id' => $this->usaPublicId
            ]);
            
            // Tenta criar uma transação de estorno com erro
            $this->registrarErroEstorno(
                $servicoTransacao, 
                $this->idTransacao, 
                $this->usaPublicId, 
                "Saldo insuficiente para estorno: {$e->getMessage()}"
            );
            
            throw $e;
        } catch (TransacaoException $e) {
            Log::warning('Erro específico durante estorno', [
                'erro' => $e->getMessage(),
                'id_transacao' => $this->idTransacao,
                'eh_public_id' => $this->usaPublicId
            ]);
            
            // Tenta criar uma transação de estorno com erro
            $this->registrarErroEstorno(
                $servicoTransacao, 
                $this->idTransacao, 
                $this->usaPublicId, 
                $e->getMessage()
            );
            
            throw $e;
        } catch (\Exception $e) {
            Log::error('Falha ao processar estorno', [
                'erro' => $e->getMessage(),
                'id_transacao' => $this->idTransacao,
                'eh_public_id' => $this->usaPublicId,
                'stack' => $e->getTraceAsString()
            ]);
            
            // Tenta criar uma transação de estorno com erro
            $this->registrarErroEstorno(
                $servicoTransacao, 
                $this->idTransacao, 
                $this->usaPublicId, 
                "Erro ao processar estorno: {$e->getMessage()}"
            );
            
            throw $e;
        }
    }

    /**
     * Registra um erro de estorno em uma transação.
     *
     * @param TransacaoServiceInterface $servicoTransacao
     * @param string|int $idTransacao
     * @param bool $ehPublicId
     * @param string $mensagemErro
     * @return void
     */
    private function registrarErroEstorno(
        TransacaoServiceInterface $servicoTransacao, 
        $idTransacao, 
        bool $ehPublicId, 
        string $mensagemErro
    ): void {
        try {
            // Busca a transação original
            $transacaoOriginal = $ehPublicId 
                ? $servicoTransacao->buscarTransacaoPorPublicId($idTransacao)
                : $servicoTransacao->buscarTransacaoPorId($idTransacao);
                
            if (!$transacaoOriginal) {
                Log::warning('Não foi possível registrar erro de estorno: Transação original não encontrada', [
                    'id_transacao' => $idTransacao,
                    'eh_public_id' => $ehPublicId
                ]);
                return;
            }
            
            // Verifica se já existe uma transação de estorno falha para esta transação original
            $estornoExistente = $transacaoOriginal->estornos()
                ->where('status', \App\Domain\Entities\Transacao::STATUS_FALHA)
                ->first();
                
            if ($estornoExistente) {
                // Atualiza o estorno existente
                $estornoExistente->error_message = $mensagemErro;
                $servicoTransacao->atualizarTransacao($estornoExistente);
                
                Log::info('Transação de estorno atualizada com erro', [
                    'id_estorno' => $estornoExistente->id,
                    'public_id_estorno' => $estornoExistente->public_id,
                    'id_transacao_original' => $transacaoOriginal->id,
                    'erro' => $mensagemErro
                ]);
                
                // Notifica os usuários sobre a falha no estorno
                app(NotificacaoServiceInterface::class)->notificarTransacaoFalha($estornoExistente);
                
                return;
            }
            
            // Cria uma nova transação de estorno com status de falha
            $dadosEstorno = [
                'account_id' => $transacaoOriginal->account_id,
                'type' => \App\Domain\Entities\Transacao::TIPO_ESTORNO,
                'amount' => $transacaoOriginal->amount,
                'status' => \App\Domain\Entities\Transacao::STATUS_FALHA,
                'description' => $this->descricao ?? 'Estorno (falha)',
                'transaction_key' => $this->transactionKey,
                'error_message' => $mensagemErro,
                'parent_id' => $transacaoOriginal->id
            ];
            
            $transacaoEstornoFalha = $servicoTransacao->criar($dadosEstorno);
            
            Log::info('Criada nova transação de estorno com falha', [
                'id_estorno' => $transacaoEstornoFalha->id,
                'public_id_estorno' => $transacaoEstornoFalha->public_id,
                'id_transacao_original' => $transacaoOriginal->id,
                'erro' => $mensagemErro
            ]);
            
            // Notifica os usuários sobre a falha no estorno
            app(NotificacaoServiceInterface::class)->notificarTransacaoFalha($transacaoEstornoFalha);
        } catch (\Exception $e) {
            Log::error('Não foi possível registrar erro no estorno', [
                'erro_original' => $mensagemErro,
                'erro_ao_registrar' => $e->getMessage(),
                'id_transacao' => $idTransacao
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
        // Verifica se o erro é de saldo insuficiente
        if ($exception instanceof SaldoInsuficienteException) {
            Log::warning('Processamento do estorno falhou por saldo insuficiente', [
                'erro' => $exception->getMessage(),
                'id_transacao_original' => $this->idTransacao,
                'descricao' => $this->descricao,
                'transaction_key' => $this->transactionKey
            ]);
        } else {
            Log::error('Processamento do estorno falhou após máximo de tentativas', [
                'erro' => $exception->getMessage(),
                'tipo_erro' => get_class($exception),
                'id_transacao_original' => $this->idTransacao,
                'descricao' => $this->descricao,
                'transaction_key' => $this->transactionKey
            ]);
        }
    }
}