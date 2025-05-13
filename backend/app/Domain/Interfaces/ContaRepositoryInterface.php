<?php

namespace App\Domain\Interfaces;

use App\Domain\Entities\Conta;
use App\Domain\Entities\Usuario;

interface ContaRepositoryInterface
{
    /**
     * Busca uma conta pelo ID.
     *
     * @param int $id
     * @return Conta|null
     */
    public function buscarPorId(int $id): ?Conta;

    /**
     * Busca uma conta pelo ID do usuário.
     *
     * @param int $userId
     * @return Conta|null
     */
    public function buscarPorIdUsuario(int $userId): ?Conta;

    /**
     * Busca uma conta pelo usuário.
     *
     * @param Usuario $usuario
     * @return Conta|null
     */
    public function buscarPorUsuario(Usuario $usuario): ?Conta;

    /**
     * Cria uma conta para um usuário.
     *
     * @param Usuario $usuario
     * @param float $saldoInicial
     * @return Conta
     */
    public function criarParaUsuario(Usuario $usuario, float $saldoInicial = 0.0): Conta;

    /**
     * Salva uma conta.
     *
     * @param Conta $conta
     * @return Conta
     */
    public function salvar(Conta $conta): Conta;

    /**
     * Deleta uma conta.
     *
     * @param Conta $conta
     * @return bool
     */
    public function deletar(Conta $conta): bool;
} 