<?php

namespace App\Presentation\Transformers;

use App\Domain\Entities\Transacao;
use League\Fractal\TransformerAbstract;

class TransacaoTransformer extends TransformerAbstract
{
    /**
     * Lista de recursos que podem ser incluídos
     *
     * @var array
     */
    protected array $availableIncludes = [
        'conta',
        'detalhes',
        'transacaoOriginal',
        'estornos'
    ];

    /**
     * Transforma a entidade transação para o formato da API
     *
     * @param Transacao $transacao
     * @return array
     */
    public function transform(Transacao $transacao): array
    {
        $dadosTransformados = [
            'id' => $transacao->public_id,
            'account_id' => $transacao->conta?->public_id ?? $transacao->account_id,
            'type' => $transacao->type,
            'amount' => (float) $transacao->amount,
            'status' => $transacao->status,
            'description' => $transacao->description,
            'transaction_key' => $transacao->transaction_key,
            'reference_id' => $transacao->reference_id ? $this->buscarPublicId($transacao->reference_id) : null,
            'created_at' => $transacao->created_at->toIso8601String(),
            'updated_at' => $transacao->updated_at->toIso8601String(),
        ];
        
        // Inclui a mensagem de erro se a transação falhou
        if ($transacao->temFalha() && $transacao->error_message) {
            $dadosTransformados['error_message'] = $transacao->error_message;
        }
        
        return $dadosTransformados;
    }

    /**
     * Busca o public_id de uma transação pelo id interno
     * 
     * @param int $id
     * @return string|null
     */
    private function buscarPublicId(int $id): ?string
    {
        $transacao = Transacao::find($id);
        return $transacao ? $transacao->public_id : null;
    }

    /**
     * Inclui Conta
     *
     * @param Transacao $transacao
     * @return \League\Fractal\Resource\Item|null
     */
    public function includeConta(Transacao $transacao)
    {
        if ($transacao->conta) {
            return $this->item($transacao->conta, new ContaTransformer());
        }
        
        return null;
    }

    /**
     * Inclui Detalhes da Transação
     *
     * @param Transacao $transacao
     * @return \League\Fractal\Resource\Item|null
     */
    public function includeDetalhes(Transacao $transacao)
    {
        if ($transacao->detalhes) {
            return $this->item($transacao->detalhes, new DetalheTransacaoTransformer());
        }
        
        return null;
    }

    /**
     * Inclui Transação Original (em caso de estorno)
     *
     * @param Transacao $transacao
     * @return \League\Fractal\Resource\Item|null
     */
    public function includeTransacaoOriginal(Transacao $transacao)
    {
        if ($transacao->ehEstorno() && $transacao->transacaoOriginal) {
            return $this->item($transacao->transacaoOriginal, new TransacaoTransformer());
        }
        
        return null;
    }

    /**
     * Inclui Estornos da Transação
     *
     * @param Transacao $transacao
     * @return \League\Fractal\Resource\Collection|null
     */
    public function includeEstornos(Transacao $transacao)
    {
        if ($transacao->estornos && $transacao->estornos->count() > 0) {
            return $this->collection($transacao->estornos, new TransacaoTransformer());
        }
        
        return null;
    }
} 