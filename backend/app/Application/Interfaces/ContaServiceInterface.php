<?php

namespace App\Application\Interfaces;

use App\Application\DTOs\ContaDTO;
use App\Domain\Entities\Conta;

interface ContaServiceInterface
{
    /**
     * Cria uma nova conta a partir do DTO.
     *
     * @param ContaDTO $contaDTO
     * @return Conta
     * @throws \InvalidArgumentException
     */
    public function criarConta(ContaDTO $contaDTO): Conta;

    /**
     * Atualiza uma conta existente a partir do DTO.
     *
     * @param int $idConta
     * @param ContaDTO $contaDTO
     * @return Conta
     * @throws \InvalidArgumentException
     */
    public function atualizarConta(int $idConta, ContaDTO $contaDTO): Conta;

    /**
     * Busca uma conta pelo ID.
     *
     * @param int $idConta
     * @return Conta|null
     */
    public function buscarContaPorId(int $idConta): ?Conta;

    /**
     * Busca uma conta pelo ID do usuário.
     *
     * @param int $idUsuario
     * @return Conta|null
     */
    public function buscarContaPorIdUsuario(int $idUsuario): ?Conta;

    /**
     * Deleta uma conta pelo ID.
     *
     * @param int $idConta
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function deletarConta(int $idConta): bool;

    /**
     * Deposita um valor em uma conta.
     *
     * @param int $idConta
     * @param float $valor
     * @return Conta
     * @throws \InvalidArgumentException
     */
    public function depositar(int $idConta, float $valor): Conta;

    /**
     * Saca um valor de uma conta.
     *
     * @param int $idConta
     * @param float $valor
     * @return Conta
     * @throws \InvalidArgumentException
     * @throws \App\Domain\Exceptions\SaldoInsuficienteException
     */
    public function sacar(int $idConta, float $valor): Conta;
} 