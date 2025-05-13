<?php

namespace App\Presentation\Transformers;

use App\Domain\Entities\Usuario;
use League\Fractal\TransformerAbstract;

class UsuarioTransformer extends TransformerAbstract
{
    /**
     * Lista de recursos que podem ser incluídos
     *
     * @var array
     */
    protected array $availableIncludes = [
        'conta'
    ];

    /**
     * Transforma a entidade usuário para o formato da API
     *
     * @param Usuario $usuario
     * @return array
     */
    public function transform(Usuario $usuario): array
    {
        return [
            'id' => $usuario->public_id,
            'name' => $usuario->name,
            'email' => $usuario->email,
            'phone' => $usuario->phone,
            'document' => $usuario->document,
            'status' => $usuario->status,
            'created_at' => $usuario->created_at->toIso8601String(),
            'updated_at' => $usuario->updated_at->toIso8601String()
        ];
    }

    /**
     * Inclui Conta
     *
     * @param Usuario $usuario
     * @return \League\Fractal\Resource\Item|null
     */
    public function includeConta(Usuario $usuario)
    {
        if ($usuario->conta()) {
            return $this->item($usuario->conta, new ContaTransformer());
        }
        
        return null;
    }
}