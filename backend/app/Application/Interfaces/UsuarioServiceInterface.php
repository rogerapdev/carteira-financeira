<?php

namespace App\Application\Interfaces;

use App\Application\DTOs\UsuarioDTO;
use App\Domain\Entities\Usuario;

interface UsuarioServiceInterface
{
    /**
     * Cria um novo usuário a partir do DTO.
     *
     * @param UsuarioDTO $usuarioDTO
     * @return Usuario
     * @throws \InvalidArgumentException
     */
    public function criarUsuario(UsuarioDTO $usuarioDTO): Usuario;

    /**
     * Atualiza um usuário existente a partir do DTO.
     *
     * @param int $idUsuario
     * @param UsuarioDTO $usuarioDTO
     * @return Usuario
     * @throws \InvalidArgumentException
     */
    public function atualizarUsuario(int $idUsuario, UsuarioDTO $usuarioDTO): Usuario;

    /**
     * Busca um usuário pelo ID.
     *
     * @param int $idUsuario
     * @return Usuario|null
     */
    public function buscarUsuarioPorId(int $idUsuario): ?Usuario;

    /**
     * Busca um usuário pelo email.
     *
     * @param string $email
     * @return Usuario|null
     */
    public function buscarUsuarioPorEmail(string $email): ?Usuario;

    /**
     * Deleta um usuário pelo ID.
     *
     * @param int $idUsuario
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function deletarUsuario(int $idUsuario): bool;
} 