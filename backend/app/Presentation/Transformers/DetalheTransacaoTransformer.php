<?php

namespace App\Presentation\Transformers;

use App\Domain\Entities\DetalheTransacao;
use League\Fractal\TransformerAbstract;

class DetalheTransacaoTransformer extends TransformerAbstract
{
    /**
     * Lista de recursos que podem ser incluídos
     *
     * @var array
     */
    protected array $availableIncludes = [
        'transacao',
        'contaOrigem',
        'contaDestino'
    ];

    /**
     * Transforma a entidade detalhe de transação para o formato da API
     *
     * @param DetalheTransacao $detalhe
     * @return array
     */
    public function transform(DetalheTransacao $detalhe): array
    {
        $dados = [
            'id' => $detalhe->public_id,
            'transaction_id' => $detalhe->transacao?->public_id ?? $detalhe->transaction_id,
            'from_account_id' => $detalhe->contaOrigem?->public_id ?? $detalhe->from_account_id,
            'to_account_id' => $detalhe->contaDestino?->public_id ?? $detalhe->to_account_id,
            'created_at' => $detalhe->created_at->toIso8601String(),
            'updated_at' => $detalhe->updated_at->toIso8601String(),
        ];

        // Adiciona metadados se existirem
        if ($detalhe->metadata) {
            $dados['metadata'] = $detalhe->metadata;
        }

        return $dados;
    }

    /**
     * Inclui Transação
     *
     * @param DetalheTransacao $detalhe
     * @return \League\Fractal\Resource\Item|null
     */
    public function includeTransacao(DetalheTransacao $detalhe)
    {
        if ($detalhe->transacao) {
            return $this->item($detalhe->transacao, new TransacaoTransformer());
        }
        
        return null;
    }

    /**
     * Inclui Conta de Origem
     *
     * @param DetalheTransacao $detalhe
     * @return \League\Fractal\Resource\Item|null
     */
    public function includeContaOrigem(DetalheTransacao $detalhe)
    {
        if ($detalhe->contaOrigem) {
            return $this->item($detalhe->contaOrigem, new ContaTransformer());
        }
        
        return null;
    }

    /**
     * Inclui Conta de Destino
     *
     * @param DetalheTransacao $detalhe
     * @return \League\Fractal\Resource\Item|null
     */
    public function includeContaDestino(DetalheTransacao $detalhe)
    {
        if ($detalhe->contaDestino) {
            return $this->item($detalhe->contaDestino, new ContaTransformer());
        }
        
        return null;
    }
} 