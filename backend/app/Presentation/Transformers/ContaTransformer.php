<?php

namespace App\Presentation\Transformers;

use App\Domain\Entities\Conta;
use League\Fractal\TransformerAbstract;

class ContaTransformer extends TransformerAbstract
{
    /**
     * Lista de recursos que podem ser incluídos
     *
     * @var array
     */
    protected array $availableIncludes = [
        'usuario',
        'transacoes'
    ];

    /**
     * Transforma a entidade conta para o formato da API
     *
     * @param Conta $conta
     * @return array
     */
    public function transform(Conta $conta): array
    {
        return [
            'id' => $conta->public_id,
            'user_id' => $conta->usuario?->public_id ?? $conta->user_id,
            'balance' => (float) $conta->balance,
            'status' => $conta->status,
            'created_at' => $conta->created_at->toIso8601String(),
            'updated_at' => $conta->updated_at->toIso8601String(),
        ];
    }

    /**
     * Inclui Usuário
     *
     * @param Conta $conta
     * @return \League\Fractal\Resource\Item|null
     */
    public function includeUsuario(Conta $conta)
    {
        if ($conta->usuario) {
            return $this->item($conta->usuario, new UsuarioTransformer());
        }
        
        return null;
    }

    /**
     * Inclui Transações
     *
     * @param Conta $conta
     * @return \League\Fractal\Resource\Collection|null
     */
    public function includeTransacoes(Conta $conta)
    {
        if ($conta->transacoes) {
            return $this->collection($conta->transacoes, new TransacaoTransformer());
        }
        
        return null;
    }
} 