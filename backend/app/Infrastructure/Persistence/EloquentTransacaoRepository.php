<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Entities\Conta;
use App\Domain\Entities\Transacao;
use App\Domain\Entities\DetalheTransacao;
use App\Domain\Interfaces\TransacaoRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentTransacaoRepository implements TransacaoRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function buscarPorId(int $id): ?Transacao
    {
        return Transacao::with('detalhes')->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function buscarPorPublicId(string $publicId): ?Transacao
    {
        return Transacao::with('detalhes')->where('public_id', $publicId)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function buscarPorConta(int $idConta, int $porPagina = 15): LengthAwarePaginator
    {
        return Transacao::where('account_id', $idConta)
            ->orderBy('created_at', 'desc')
            ->paginate($porPagina);
    }

    /**
     * {@inheritdoc}
     */
    public function criar(array $dados): Transacao
    {
        return Transacao::create($dados);
    }

    /**
     * {@inheritdoc}
     */
    public function salvar(Transacao $transacao): Transacao
    {
        $transacao->save();
        return $transacao;
    }

    /**
     * {@inheritdoc}
     */
    public function buscarTodasPorConta(Conta $conta): Collection
    {
        return Transacao::with('detalhes')
            ->where('account_id', $conta->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function buscarTodasPorContaPublicId(string $publicIdConta): Collection
    {
        $conta = Conta::where('public_id', $publicIdConta)->first();
        if (!$conta) {
            return collect([]);
        }
        
        return $this->buscarTodasPorConta($conta);
    }

    /**
     * {@inheritdoc}
     */
    public function criarComDetalhes(array $dadosTransacao, array $dadosDetalhes): Transacao
    {
        return DB::transaction(function () use ($dadosTransacao, $dadosDetalhes) {
            // Cria transação
            $transacao = $this->criar($dadosTransacao);

            // Cria detalhes
            $dadosDetalhes['transaction_id'] = $transacao->id;
            $detalhes = DetalheTransacao::create($dadosDetalhes);

            // Carrega a relação de detalhes
            $transacao->setRelation('detalhes', $detalhes);

            return $transacao;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function buscarPorTransactionKey(string $transactionKey): ?Transacao
    {
        return Transacao::with('detalhes')->where('transaction_key', $transactionKey)->first();
    }
} 