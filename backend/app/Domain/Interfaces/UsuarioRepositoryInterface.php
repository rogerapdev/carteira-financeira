<?php

namespace App\Domain\Interfaces;

use App\Domain\Entities\Usuario;

interface UsuarioRepositoryInterface
{
    /**
     * Busca um usuário pelo ID.
     *
     * @param int $id
     * @return Usuario|null
     */
    public function buscarPorId(int $id): ?Usuario;

    /**
     * Busca um usuário pelo email.
     *
     * @param string $email
     * @return Usuario|null
     */
    public function buscarPorEmail(string $email): ?Usuario;

    /**
     * Busca um usuário pelo documento.
     *
     * @param string $documento
     * @return Usuario|null
     */
    public function buscarPorDocumento(string $documento): ?Usuario;

    /**
     * Salva um usuário.
     *
     * @param Usuario $usuario
     * @return Usuario
     */
    public function salvar(Usuario $usuario): Usuario;

    /**
     * Deleta um usuário.
     *
     * @param Usuario $usuario
     * @return bool
     */
    public function deletar(Usuario $usuario): bool;
} 