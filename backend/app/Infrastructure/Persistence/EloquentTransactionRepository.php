<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Entities\Account;
use App\Domain\Entities\Transaction;
use App\Domain\Entities\TransactionDetail;
use App\Domain\Interfaces\TransactionRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentTransactionRepository implements TransactionRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function buscarPorId(int $id): ?Transaction
    {
        return Transaction::with('detalhes')->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function buscarPorConta(Account $conta, int $porPagina = 15): LengthAwarePaginator
    {
        return Transaction::with('detalhes')
            ->where('account_id', $conta->id)
            ->orderBy('created_at', 'desc')
            ->paginate($porPagina);
    }

    /**
     * {@inheritdoc}
     */
    public function criar(array $dados): Transaction
    {
        return Transaction::create($dados);
    }

    /**
     * {@inheritdoc}
     */
    public function salvar(Transaction $transacao): Transaction
    {
        $transacao->save();
        return $transacao;
    }

    /**
     * {@inheritdoc}
     */
    public function buscarTodasPorConta(Account $conta): Collection
    {
        return Transaction::with('detalhes')
            ->where('account_id', $conta->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function criarComDetalhes(array $dadosTransacao, array $dadosDetalhes): Transaction
    {
        return DB::transaction(function () use ($dadosTransacao, $dadosDetalhes) {
            // Cria transação
            $transacao = $this->criar($dadosTransacao);
            
            // Cria detalhes
            $dadosDetalhes['transaction_id'] = $transacao->id;
            $detalhes = TransactionDetail::create($dadosDetalhes);

            // Carrega a relação de detalhes
            $transacao->setRelation('detalhes', $detalhes);

            return $transacao;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function buscarPorTransactionKey(string $transactionKey): ?Transaction
    {
        return Transaction::with('detalhes')
            ->where('transaction_key', $transactionKey)
            ->first();
    }
} 