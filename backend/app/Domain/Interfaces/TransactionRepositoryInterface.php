<?php

namespace App\Domain\Interfaces;

use App\Domain\Entities\Account;
use App\Domain\Entities\Transaction;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface TransactionRepositoryInterface
{
    /**
     * Busca uma transação pelo ID.
     *
     * @param int $id
     * @return Transaction|null
     */
    public function buscarPorId(int $id): ?Transaction;

    /**
     * Busca todas as transações de uma conta com paginação.
     *
     * @param Account $conta
     * @param int $porPagina
     * @return LengthAwarePaginator
     */
    public function buscarPorConta(Account $conta, int $porPagina = 15): LengthAwarePaginator;

    /**
     * Cria uma nova transação.
     *
     * @param array $dados
     * @return Transaction
     */
    public function criar(array $dados): Transaction;

    /**
     * Salva uma transação.
     *
     * @param Transaction $transacao
     * @return Transaction
     */
    public function salvar(Transaction $transacao): Transaction;

    /**
     * Busca todas as transações de uma conta.
     *
     * @param Account $conta
     * @return Collection
     */
    public function buscarTodasPorConta(Account $conta): Collection;

    /**
     * Cria uma transação com detalhes.
     *
     * @param array $dadosTransacao
     * @param array $dadosDetalhes
     * @return Transaction
     */
    public function criarComDetalhes(array $dadosTransacao, array $dadosDetalhes): Transaction;

    /**
     * Busca uma transação pelo transaction_key.
     *
     * @param string $transactionKey
     * @return Transaction|null
     */
    public function buscarPorTransactionKey(string $transactionKey): ?Transaction;
} 