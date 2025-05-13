<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Entities\Conta;
use App\Domain\Entities\Usuario;
use App\Domain\Interfaces\ContaRepositoryInterface;

class EloquentContaRepository implements ContaRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function buscarPorId(int $id): ?Conta
    {
        return Conta::find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function buscarPorPublicId(string $publicId): ?Conta
    {
        return Conta::where('public_id', $publicId)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function buscarPorIdUsuario(int $userId): ?Conta
    {
        return Conta::where('user_id', $userId)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function buscarPorUsuario(Usuario $usuario): ?Conta
    {
        return $this->buscarPorIdUsuario($usuario->id);
    }

    /**
     * {@inheritdoc}
     */
    public function criarParaUsuario(Usuario $usuario, float $saldoInicial = 0.0): Conta
    {
        $conta = new Conta();
        $conta->user_id = $usuario->id;
        $conta->balance = $saldoInicial;
        $conta->status = 'active';
        $conta->save();

        return $conta;
    }

    /**
     * {@inheritdoc}
     */
    public function salvar(Conta $conta): Conta
    {
        $conta->save();
        return $conta;
    }

    /**
     * {@inheritdoc}
     */
    public function deletar(Conta $conta): bool
    {
        return $conta->delete();
    }
} 