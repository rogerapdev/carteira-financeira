<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Entities\Usuario;
use App\Domain\Interfaces\UsuarioRepositoryInterface;

class EloquentUsuarioRepository implements UsuarioRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function buscarPorId(int $id): ?Usuario
    {
        return Usuario::find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function buscarPorEmail(string $email): ?Usuario
    {
        return Usuario::where('email', $email)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function buscarPorDocumento(string $documento): ?Usuario
    {
        return Usuario::where('document', $documento)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function salvar(Usuario $usuario): Usuario
    {
        $usuario->save();
        return $usuario;
    }

    /**
     * {@inheritdoc}
     */
    public function deletar(Usuario $usuario): bool
    {
        return $usuario->delete();
    }
} 