<?php

namespace App\Presentation\Http\Traits;

use App\Domain\Entities\Conta;
use App\Domain\Entities\DetalheTransacao;
use App\Domain\Entities\Transacao;
use App\Domain\Entities\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;

trait UsaPublicId
{
    /**
     * Converte um public_id para id interno de um modelo específico
     * 
     * @param string $publicId O public_id a ser convertido
     * @param string $modelo Nome da classe do modelo
     * @return int|null O id interno ou null se não encontrado
     */
    protected function obterIdInterno(string $publicId, string $modelo): ?int
    {
        /** @var Model $registro */
        $registro = $modelo::where('public_id', $publicId)->first();
        
        return $registro ? $registro->id : null;
    }
    
    /**
     * Obtém o ID interno de uma conta a partir do public_id
     * 
     * @param string $publicId
     * @return int|null
     */
    protected function obterIdConta(string $publicId): ?int
    {
        return $this->obterIdInterno($publicId, Conta::class);
    }
    
    /**
     * Obtém o ID interno de um usuário a partir do public_id
     * 
     * @param string $publicId
     * @return int|null
     */
    protected function obterIdUsuario(string $publicId): ?int
    {
        return $this->obterIdInterno($publicId, Usuario::class);
    }
    
    /**
     * Obtém o ID interno de uma transação a partir do public_id
     * 
     * @param string $publicId
     * @return int|null
     */
    protected function obterIdTransacao(string $publicId): ?int
    {
        return $this->obterIdInterno($publicId, Transacao::class);
    }
    
    /**
     * Obtém o ID interno de um detalhe de transação a partir do public_id
     * 
     * @param string $publicId
     * @return int|null
     */
    protected function obterIdDetalheTransacao(string $publicId): ?int
    {
        return $this->obterIdInterno($publicId, DetalheTransacao::class);
    }
    
    /**
     * Retorna uma resposta de erro quando um ID não for encontrado
     * 
     * @param string $tipo
     * @return \Illuminate\Http\JsonResponse
     */
    protected function erroIdNaoEncontrado(string $tipo = 'registro')
    {
        return response()->json([
            'mensagem' => "O {$tipo} informado não foi encontrado"
        ], Response::HTTP_NOT_FOUND);
    }
} 