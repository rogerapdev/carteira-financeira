<?php

namespace App\Domain\Interfaces;

use App\Domain\Entities\Conta;
use App\Domain\Entities\Transacao;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface TransacaoRepositoryInterface
{
    /**
     * Busca uma transação pelo ID.
     *
     * @param int $id
     * @return Transacao|null
     */
    public function buscarPorId(int $id): ?Transacao;

    /**
     * Busca uma transação pelo public_id.
     *
     * @param string $publicId
     * @return Transacao|null
     */
    public function buscarPorPublicId(string $publicId): ?Transacao;

    /**
     * Busca transações por conta.
     *
     * @param int $idConta
     * @param int $porPagina
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function buscarPorConta(int $idConta, int $porPagina = 15): \Illuminate\Pagination\LengthAwarePaginator;

    /**
     * Cria uma nova transação.
     *
     * @param array $dados
     * @return Transacao
     */
    public function criar(array $dados): Transacao;

    /**
     * Salva uma transação.
     *
     * @param Transacao $transacao
     * @return Transacao
     */
    public function salvar(Transacao $transacao): Transacao;

    /**
     * Busca todas as transações de uma conta.
     *
     * @param Conta $conta
     * @return Collection
     */
    public function buscarTodasPorConta(Conta $conta): Collection;

    /**
     * Busca todas as transações de uma conta pelo public_id.
     *
     * @param string $publicIdConta
     * @return Collection
     */
    public function buscarTodasPorContaPublicId(string $publicIdConta): Collection;

    /**
     * Cria uma transação com detalhes.
     *
     * @param array $dadosTransacao
     * @param array $dadosDetalhes
     * @return Transacao
     */
    public function criarComDetalhes(array $dadosTransacao, array $dadosDetalhes): Transacao;

    /**
     * Busca uma transação pelo transaction_key.
     *
     * @param string $transactionKey
     * @return Transacao|null
     */
    public function buscarPorTransactionKey(string $transactionKey): ?Transacao;
} 