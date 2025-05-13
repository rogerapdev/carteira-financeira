<?php

namespace App\Application\Interfaces;

use App\Application\DTOs\TransacaoDTO;
use App\Domain\Entities\Transacao;
use Illuminate\Pagination\LengthAwarePaginator;

interface TransacaoServiceInterface
{
    /**
     * Cria uma nova transação de depósito.
     *
     * @param TransacaoDTO $transacaoDTO
     * @return Transacao
     */
    public function criarDeposito(TransacaoDTO $transacaoDTO): Transacao;
    
    /**
     * Cria uma nova transação de transferência.
     *
     * @param TransacaoDTO $transacaoDTO
     * @return Transacao
     */
    public function criarTransferencia(TransacaoDTO $transacaoDTO): Transacao;
    
    /**
     * Estorna uma transação existente.
     *
     * @param int $idTransacao
     * @param string|null $descricao
     * @param string|null $transactionKey
     * @return Transacao
     */
    public function estornarTransacao(int $idTransacao, ?string $descricao = null, ?string $transactionKey = null): Transacao;
    
    /**
     * Estorna uma transação existente usando o public_id.
     *
     * @param string $publicIdTransacao
     * @param string|null $descricao
     * @param string|null $transactionKey
     * @return Transacao
     */
    public function estornarTransacaoPorPublicId(string $publicIdTransacao, ?string $descricao = null, ?string $transactionKey = null): Transacao;
    
    /**
     * Atualiza os dados de uma transação existente.
     *
     * @param Transacao $transacao
     * @return Transacao
     */
    public function atualizarTransacao(Transacao $transacao): Transacao;
    
    /**
     * Busca uma transação pelo ID.
     *
     * @param int $idTransacao
     * @return Transacao|null
     */
    public function buscarTransacaoPorId(int $idTransacao): ?Transacao;
    
    /**
     * Busca uma transação pelo public_id.
     *
     * @param string $publicId
     * @return Transacao|null
     */
    public function buscarTransacaoPorPublicId(string $publicId): ?Transacao;
    
    /**
     * Busca uma transação pela chave de transação.
     *
     * @param string $transactionKey
     * @return Transacao|null
     */
    public function buscarTransacaoPorTransactionKey(string $transactionKey): ?Transacao;
    
    /**
     * Busca todas as transações de uma conta.
     *
     * @param int $idConta
     * @param int $porPagina
     * @return LengthAwarePaginator
     */
    public function buscarTransacoesPorConta(int $idConta, int $porPagina = 15): LengthAwarePaginator;
    
    /**
     * Busca todas as transações de uma conta pelo public_id.
     *
     * @param string $publicIdConta
     * @param int $porPagina
     * @return LengthAwarePaginator
     */
    public function buscarTransacoesPorContaPublicId(string $publicIdConta, int $porPagina = 15): LengthAwarePaginator;
    
    /**
     * Processa uma transação (atualiza saldos e status).
     *
     * @param Transacao $transacao
     * @return Transacao
     */
    public function processarTransacao(Transacao $transacao): Transacao;
    
    /**
     * Cria uma nova transação com detalhes.
     *
     * @param array $dadosTransacao
     * @param array $dadosDetalhes
     * @return Transacao
     */
    public function criarComDetalhes(array $dadosTransacao, array $dadosDetalhes): Transacao;
    
    /**
     * Cria uma nova transação.
     *
     * @param array $dados
     * @return Transacao
     */
    public function criar(array $dados): Transacao;
} 