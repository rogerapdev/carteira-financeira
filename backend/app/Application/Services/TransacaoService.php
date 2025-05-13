<?php

namespace App\Application\Services;

use App\Application\DTOs\TransacaoDTO;
use App\Application\Interfaces\TransacaoServiceInterface;
use App\Domain\Entities\Transacao;
use App\Domain\Entities\DetalheTransacao;
use App\Domain\Interfaces\TransacaoRepositoryInterface;
use App\Domain\Interfaces\ContaRepositoryInterface;
use App\Domain\Exceptions\SaldoInsuficienteException;
use App\Domain\Exceptions\TransacaoException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Illuminate\Support\Facades\Log;

class TransacaoService implements TransacaoServiceInterface
{
    /**
     * @param TransacaoRepositoryInterface $repositorioTransacao
     * @param ContaRepositoryInterface $repositorioConta
     */
    public function __construct(
        private TransacaoRepositoryInterface $repositorioTransacao,
        private ContaRepositoryInterface $repositorioConta
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function criarDeposito(TransacaoDTO $transacaoDTO): Transacao
    {
        // Valida o valor do depósito
        if ($transacaoDTO->amount <= 0) {
            throw new InvalidArgumentException("Valor do depósito deve ser maior que zero");
        }

        // Verifica se a conta existe
        $conta = $this->repositorioConta->buscarPorPublicId($transacaoDTO->to_account_id);
        if (!$conta) {
            throw new InvalidArgumentException("Conta não encontrada com o ID: {$transacaoDTO->account_id}");
        }

        if (!$conta->estaAtiva()) {
            throw new InvalidArgumentException("Não é possível depositar em uma conta inativa");
        }

        // Prepara dados da transação
        $dados = $transacaoDTO->paraArray();
        $dadosTransacao = $dados['transacao'];
        $dadosDetalhes = $dados['detalhes'];

        $dadosDetalhes['to_account_id'] = $conta->id;

        // Define o tipo como depósito
        $dadosTransacao['type'] = Transacao::TIPO_DEPOSITO;
        $dadosTransacao['status'] = Transacao::STATUS_PENDENTE;

        // Cria a transação e seus detalhes
        DB::beginTransaction();
        try {
            $transacao = $this->repositorioTransacao->criarComDetalhes($dadosTransacao, $dadosDetalhes);

            $transacao = $this->processarTransacao($transacao);
            DB::commit();
            return $transacao;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new TransacaoException("Falha ao criar depósito: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function criarTransferencia(TransacaoDTO $transacaoDTO): Transacao
    {
        // Valida o valor da transferência
        if ($transacaoDTO->amount <= 0) {
            throw new InvalidArgumentException("Valor da transferência deve ser maior que zero");
        }

        // Valida campos obrigatórios para transferência
        if (empty($transacaoDTO->from_account_id)) {
            throw new InvalidArgumentException("Conta de origem é obrigatória para transferências");
        }

        if (empty($transacaoDTO->to_account_id)) {
            throw new InvalidArgumentException("Conta de destino é obrigatória para transferências");
        }

        // Verifica se as contas existem
        $contaOrigem = $this->repositorioConta->buscarPorPublicId($transacaoDTO->from_account_id);
        if (!$contaOrigem) {
            throw new InvalidArgumentException("Conta de origem não encontrada com o ID: {$transacaoDTO->from_account_id}");
        }

        $contaDestino = $this->repositorioConta->buscarPorPublicId($transacaoDTO->to_account_id);
        if (!$contaDestino) {
            throw new InvalidArgumentException("Conta de destino não encontrada com o ID: {$transacaoDTO->to_account_id}");
        }

        // Verifica se as contas estão ativas
        if (!$contaOrigem->estaAtiva()) {
            throw new InvalidArgumentException("Não é possível transferir de uma conta inativa");
        }

        if (!$contaDestino->estaAtiva()) {
            throw new InvalidArgumentException("Não é possível transferir para uma conta inativa");
        }

        // Verifica saldo disponível de forma mais explícita
        if ($contaOrigem->balance < $transacaoDTO->amount) {
            $saldoFaltante = $transacaoDTO->amount - $contaOrigem->balance;
            throw new SaldoInsuficienteException(
                "Saldo insuficiente para transferência. Necessário: R$ " . 
                number_format($transacaoDTO->amount, 2, ',', '.') . 
                ", Disponível: R$ " . 
                number_format($contaOrigem->balance, 2, ',', '.') .
                ", Faltam: R$ " .
                number_format($saldoFaltante, 2, ',', '.')
            );
        }

        // Prepara dados da transação
        $dados = $transacaoDTO->paraArray();
        $dadosTransacao = $dados['transacao'];
        $dadosDetalhes = $dados['detalhes'];

        $dadosDetalhes['from_account_id'] = $contaOrigem->id;
        $dadosDetalhes['to_account_id'] = $contaDestino->id;

        // Define o tipo como transferência
        $dadosTransacao['type'] = Transacao::TIPO_TRANSFERENCIA;
        $dadosTransacao['status'] = Transacao::STATUS_PENDENTE;

        // Cria a transação e seus detalhes
        DB::beginTransaction();
        try {
            $transacao = $this->repositorioTransacao->criarComDetalhes($dadosTransacao, $dadosDetalhes);
            $transacao = $this->processarTransacao($transacao);
            DB::commit();
            return $transacao;
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Captura exceção de saldo insuficiente e lança como SaldoInsuficienteException
            if ($e instanceof SaldoInsuficienteException) {
                throw $e;
            }
            
            throw new TransacaoException("Falha ao criar transferência: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function estornarTransacao(int $idTransacao, ?string $descricao = null, ?string $transaction_key = null): Transacao
    {
        
        // Verifica se já existe uma transação com a mesma chave de idempotência
        if ($transaction_key) {
            $transacaoExistente = $this->buscarTransacaoPorTransactionKey($transaction_key);
            if ($transacaoExistente) {
                Log::info('Estorno recuperado por idempotência', [
                    'transaction_key' => $transaction_key,
                    'id_transacao' => $transacaoExistente->id,
                    'public_id' => $transacaoExistente->public_id
                ]);
                return $transacaoExistente;
            }
        }

        // Busca a transação original
        $transacaoOriginal = $this->repositorioTransacao->buscarPorId($idTransacao);
        if (!$transacaoOriginal) {
            throw new InvalidArgumentException("Transação não encontrada com o ID: {$idTransacao}");
        }

        // Verifica se a transação pode ser estornada
        if (!$transacaoOriginal->podeSerEstornada()) {
            throw new TransacaoException(
                "Transação não pode ser estornada: não está concluída, já foi estornada ou é um estorno"
            );
        }
        
        // Verifica se já existe um estorno concluído ou pendente para esta transação
        $estornosExistentes = $transacaoOriginal->estornos()
            ->whereIn('status', [
                Transacao::STATUS_CONCLUIDA, 
                Transacao::STATUS_PENDENTE
            ])
            ->count();
            
        if ($estornosExistentes > 0) {
            Log::warning('Tentativa de estorno duplicado detectada', [
                'id_transacao' => $transacaoOriginal->id,
                'public_id' => $transacaoOriginal->public_id,
                'count_estornos' => $estornosExistentes,
                'transaction_key' => $transaction_key
            ]);
            
            throw new TransacaoException(
                "Esta transação já possui um estorno em andamento ou concluído"
            );
        }

        // Prepara os dados do estorno
        $dadosTransacao = [
            'account_id' => $transacaoOriginal->account_id,
            'type' => Transacao::TIPO_ESTORNO,
            'amount' => $transacaoOriginal->amount,
            'reference_id' => $transacaoOriginal->id,
            'status' => Transacao::STATUS_PENDENTE,
            'description' => $descricao ?? "Estorno da transação #{$transacaoOriginal->id}",
            'transaction_key' => $transaction_key
        ];

        // Copia os detalhes da transação original, invertendo origens e destinos
        $detalhesOriginais = $transacaoOriginal->detalhes;
        $dadosDetalhes = [];
        
        if ($detalhesOriginais) {
            $dadosDetalhes = [
                'from_account_id' => $detalhesOriginais->to_account_id,
                'to_account_id' => $detalhesOriginais->from_account_id,
                'metadata' => [
                    'original_transaction_id' => $transacaoOriginal->id,
                    'original_public_id' => $transacaoOriginal->public_id,
                    'reason' => $descricao,
                    'estorno_timestamp' => now()->toIso8601String(),
                    'transaction_key' => $transaction_key
                ],
            ];
        }

        // Cria a transação de estorno
        DB::beginTransaction();
        try {
            // Cria a transação de estorno
            $estorno = $this->repositorioTransacao->criarComDetalhes($dadosTransacao, $dadosDetalhes);

            Log::info('Transação de estorno criada, iniciando processamento', [
                'id_estorno' => $estorno->id,
                'public_id_estorno' => $estorno->public_id,
                'id_transacao_original' => $transacaoOriginal->id,
                'public_id_transacao_original' => $transacaoOriginal->public_id,
                'valor' => $estorno->amount,
                'transaction_key' => $transaction_key
            ]);

            // Processa o estorno (reverte os saldos)
            $estorno = $this->processarTransacao($estorno);
            
            // Marca a transação original como estornada somente se o processamento for bem-sucedido
            if ($estorno->estaConcluida()) {
                $transacaoOriginal->marcarComoEstornada();
                $this->repositorioTransacao->salvar($transacaoOriginal);
                
                Log::info('Transação original marcada como estornada', [
                    'id_transacao' => $transacaoOriginal->id,
                    'public_id' => $transacaoOriginal->public_id,
                    'id_estorno' => $estorno->id,
                    'public_id_estorno' => $estorno->public_id
                ]);
            }
            
            DB::commit();
            
            Log::info('Estorno concluído com sucesso', [
                'id_estorno' => $estorno->id,
                'public_id_estorno' => $estorno->public_id,
                'id_transacao_original' => $transacaoOriginal->id,
                'status' => $estorno->status,
                'transaction_key' => $transaction_key
            ]);
            
            return $estorno;
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Para erros de saldo insuficiente, lança a exceção específica
            if ($e instanceof SaldoInsuficienteException) {
                throw $e;
            }
            
            throw new TransacaoException("Falha ao estornar transação: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function estornarTransacaoPorPublicId(string $publicIdTransacao, ?string $descricao = null, ?string $transaction_key = null): Transacao
    {
        // Verifica se já existe uma transação com a mesma chave de idempotência
        if ($transaction_key) {
            $transacaoExistente = $this->buscarTransacaoPorTransactionKey($transaction_key);
            if ($transacaoExistente) {
                Log::info('Estorno recuperado por idempotência via public_id', [
                    'transaction_key' => $transaction_key,
                    'id_transacao' => $transacaoExistente->id,
                    'public_id' => $transacaoExistente->public_id
                ]);
                return $transacaoExistente;
            }
        }

        // Busca a transação original pelo public_id
        $transacaoOriginal = $this->buscarTransacaoPorPublicId($publicIdTransacao);
        
        if (!$transacaoOriginal) {
            throw new InvalidArgumentException("Transação não encontrada com o public_id: {$publicIdTransacao}");
        }
        
        // Chama o método de estorno pelo ID interno
        return $this->estornarTransacao($transacaoOriginal->id, $descricao, $transaction_key);
    }

    /**
     * {@inheritdoc}
     */
    public function buscarTransacaoPorId(int $idTransacao): ?Transacao
    {
        return $this->repositorioTransacao->buscarPorId($idTransacao);
    }

    /**
     * {@inheritdoc}
     */
    public function buscarTransacoesPorConta(int $idConta, int $porPagina = 15): LengthAwarePaginator
    {
        return $this->repositorioTransacao->buscarPorConta($idConta, $porPagina);
    }

    /**
     * {@inheritdoc}
     */
    public function buscarTransacaoPorPublicId(string $publicId): ?Transacao
    {
        return $this->repositorioTransacao->buscarPorPublicId($publicId);
    }

    /**
     * {@inheritdoc}
     */
    public function buscarTransacoesPorContaPublicId(string $publicIdConta, int $porPagina = 15): LengthAwarePaginator
    {
        // Busca a conta pelo public_id
        $conta = $this->repositorioConta->buscarPorPublicId($publicIdConta);
        if (!$conta) {
            throw new InvalidArgumentException("Conta não encontrada com o public_id: {$publicIdConta}");
        }

        // Reutiliza o método existente passando o id interno
        return $this->buscarTransacoesPorConta($conta->id, $porPagina);
    }

    /**
     * {@inheritdoc}
     */
    public function atualizarTransacao(Transacao $transacao): Transacao
    {
        return $this->repositorioTransacao->salvar($transacao);
    }

    /**
     * Processa uma transação, atualizando os saldos de conta conforme necessário.
     *
     * @param Transacao $transacao
     * @return Transacao
     * @throws TransacaoException
     * @throws SaldoInsuficienteException
     */
    public function processarTransacao(Transacao $transacao): Transacao
    {
        if (!$transacao->estaPendente()) {
            return $transacao; // Transação já processada ou falha
        }

        DB::beginTransaction();
        try {
            // Atualiza os saldos das contas com base no tipo de transação
            if ($transacao->ehDeposito()) {
                $conta = $this->repositorioConta->buscarPorId($transacao->account_id);
                $conta->depositar($transacao->amount);
                $this->repositorioConta->salvar($conta);
            } 
            else if ($transacao->ehTransferencia()) {
                $detalhes = $transacao->detalhes;
                if (!$detalhes || !$detalhes->from_account_id || !$detalhes->to_account_id) {
                    throw new TransacaoException("Detalhes de transferência incompletos");
                }

                // Saca da conta de origem
                $contaOrigem = $this->repositorioConta->buscarPorId($detalhes->from_account_id);
                
                // Verifica explicitamente o saldo da conta de origem antes de tentar sacar
                if ($contaOrigem->balance < $transacao->amount) {
                    $saldoFaltante = $transacao->amount - $contaOrigem->balance;
                    throw new SaldoInsuficienteException(
                        "Saldo insuficiente para transferência. Necessário: R$ " . 
                        number_format($transacao->amount, 2, ',', '.') . 
                        ", Disponível: R$ " . 
                        number_format($contaOrigem->balance, 2, ',', '.') .
                        ", Faltam: R$ " .
                        number_format($saldoFaltante, 2, ',', '.')
                    );
                }
                
                $contaOrigem->sacar($transacao->amount);
                $this->repositorioConta->salvar($contaOrigem);

                // Deposita na conta de destino
                $contaDestino = $this->repositorioConta->buscarPorId($detalhes->to_account_id);
                $contaDestino->depositar($transacao->amount);
                $this->repositorioConta->salvar($contaDestino);
            }
            else if ($transacao->ehEstorno()) {
                $transacaoOriginal = $this->repositorioTransacao->buscarPorId($transacao->reference_id);
                if (!$transacaoOriginal) {
                    throw new TransacaoException("Transação original não encontrada");
                }

                $detalhes = $transacao->detalhes;
                if ($transacaoOriginal->ehDeposito()) {
                    // Estorno de depósito: saca da conta
                    $conta = $this->repositorioConta->buscarPorId($transacao->account_id);
                    
                    // Verifica se a conta tem saldo suficiente para o estorno
                    if ($conta->balance < $transacao->amount) {
                        $saldoFaltante = $transacao->amount - $conta->balance;
                        throw new SaldoInsuficienteException(
                            "Saldo insuficiente para estorno. Necessário: R$ " . 
                            number_format($transacao->amount, 2, ',', '.') . 
                            ", Disponível: R$ " . 
                            number_format($conta->balance, 2, ',', '.') .
                            ", Faltam: R$ " .
                            number_format($saldoFaltante, 2, ',', '.')
                        );
                    }
                    
                    $conta->sacar($transacao->amount);
                    $this->repositorioConta->salvar($conta);
                } 
                else if ($transacaoOriginal->ehTransferencia() && $detalhes) {
                    // Estorno de transferência: reverte a operação
                    // A conta de origem da transferência recebe o valor de volta
                    if ($detalhes->to_account_id) {
                        $contaOrigem = $this->repositorioConta->buscarPorId($detalhes->to_account_id);
                        $contaOrigem->depositar($transacao->amount);
                        $this->repositorioConta->salvar($contaOrigem);
                    }

                    // A conta de destino da transferência tem o valor retirado
                    if ($detalhes->from_account_id) {
                        $contaDestino = $this->repositorioConta->buscarPorId($detalhes->from_account_id);
                        
                        // Verifica se a conta de destino tem saldo suficiente para o estorno
                        if ($contaDestino->balance < $transacao->amount) {
                            $saldoFaltante = $transacao->amount - $contaDestino->balance;
                            throw new SaldoInsuficienteException(
                                "Saldo insuficiente para estorno da transferência. Necessário: R$ " . 
                                number_format($transacao->amount, 2, ',', '.') . 
                                ", Disponível: R$ " . 
                                number_format($contaDestino->balance, 2, ',', '.') .
                                ", Faltam: R$ " .
                                number_format($saldoFaltante, 2, ',', '.')
                            );
                        }
                        
                        $contaDestino->sacar($transacao->amount);
                        $this->repositorioConta->salvar($contaDestino);
                    }
                }
            }

            // Marca a transação como concluída
            $transacao->marcarComoConcluida();
            $this->repositorioTransacao->salvar($transacao);

            DB::commit();
            return $transacao;
        } catch (SaldoInsuficienteException $e) {
            DB::rollBack();
            
            // Marca a transação como falha com a mensagem de erro específica
            $transacao->marcarComoFalha($e->getMessage());
            $this->repositorioTransacao->salvar($transacao);
            
            Log::warning('Transação falhou por saldo insuficiente', [
                'id' => $transacao->id,
                'public_id' => $transacao->public_id,
                'valor' => $transacao->amount,
                'erro' => $e->getMessage()
            ]);
            
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            // Marca a transação como falha
            $transacao->marcarComoFalha($e->getMessage());
            $this->repositorioTransacao->salvar($transacao);
            
            Log::error('Falha ao processar transação', [
                'id' => $transacao->id, 
                'public_id' => $transacao->public_id,
                'erro' => $e->getMessage()
            ]);
            
            throw new TransacaoException("Falha ao processar transação: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buscarTransacaoPorTransactionKey(string $transaction_key): ?Transacao
    {
        return $this->repositorioTransacao->buscarPorTransactionKey($transaction_key);
    }

    /**
     * {@inheritdoc}
     */
    public function criar(array $dados): Transacao
    {
        return $this->repositorioTransacao->criar($dados);
    }

    /**
     * {@inheritdoc}
     */
    public function criarComDetalhes(array $dadosTransacao, array $dadosDetalhes): Transacao
    {
        return $this->repositorioTransacao->criarComDetalhes($dadosTransacao, $dadosDetalhes);
    }
}