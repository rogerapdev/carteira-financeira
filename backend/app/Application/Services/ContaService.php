<?php

namespace App\Application\Services;

use App\Application\DTOs\ContaDTO;
use App\Application\Interfaces\ContaServiceInterface;
use App\Domain\Entities\Conta;
use App\Domain\Interfaces\ContaRepositoryInterface;
use App\Domain\Interfaces\UsuarioRepositoryInterface;
use InvalidArgumentException;

class ContaService implements ContaServiceInterface
{
    /**
     * @param ContaRepositoryInterface $repositorioConta
     * @param UsuarioRepositoryInterface $repositorioUsuario
     */
    public function __construct(
        private ContaRepositoryInterface $repositorioConta,
        private UsuarioRepositoryInterface $repositorioUsuario
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function criarConta(ContaDTO $contaDTO): Conta
    {
        // Verifica se o usuário existe
        $usuario = $this->repositorioUsuario->buscarPorId($contaDTO->user_id);
        if (!$usuario) {
            throw new InvalidArgumentException("Usuário não encontrado com o ID: {$contaDTO->user_id}");
        }

        // Verifica se já existe uma conta para o usuário
        if ($this->repositorioConta->buscarPorIdUsuario($contaDTO->user_id)) {
            throw new InvalidArgumentException("Usuário já possui uma conta");
        }

        // Cria a conta com os dados fornecidos
        $conta = $this->repositorioConta->criarParaUsuario($usuario, $contaDTO->balance);
        
        // Atualiza o status se necessário
        if ($contaDTO->status !== 'active') {
            $conta->status = $contaDTO->status;
            $conta = $this->repositorioConta->salvar($conta);
        }

        return $conta;
    }

    /**
     * {@inheritdoc}
     */
    public function atualizarConta(int $idConta, ContaDTO $contaDTO): Conta
    {
        $conta = $this->repositorioConta->buscarPorId($idConta);
        if (!$conta) {
            throw new InvalidArgumentException("Conta não encontrada com o ID: {$idConta}");
        }

        // Atualiza apenas os campos permitidos (não permite alteração de user_id)
        $conta->status = $contaDTO->status;
        
        return $this->repositorioConta->salvar($conta);
    }

    /**
     * {@inheritdoc}
     */
    public function buscarContaPorId(int $idConta): ?Conta
    {
        return $this->repositorioConta->buscarPorId($idConta);
    }

    /**
     * {@inheritdoc}
     */
    public function buscarContaPorIdUsuario(int $idUsuario): ?Conta
    {
        return $this->repositorioConta->buscarPorIdUsuario($idUsuario);
    }

    /**
     * {@inheritdoc}
     */
    public function deletarConta(int $idConta): bool
    {
        $conta = $this->repositorioConta->buscarPorId($idConta);
        if (!$conta) {
            throw new InvalidArgumentException("Conta não encontrada com o ID: {$idConta}");
        }

        return $this->repositorioConta->deletar($conta);
    }

    /**
     * {@inheritdoc}
     */
    public function depositar(int $idConta, float $valor): Conta
    {
        if ($valor <= 0) {
            throw new InvalidArgumentException("Valor de depósito deve ser maior que zero");
        }

        $conta = $this->repositorioConta->buscarPorId($idConta);
        if (!$conta) {
            throw new InvalidArgumentException("Conta não encontrada com o ID: {$idConta}");
        }
        
        if (!$conta->estaAtiva()) {
            throw new InvalidArgumentException("Não é possível depositar em uma conta inativa");
        }

        $conta->depositar($valor);
        return $this->repositorioConta->salvar($conta);
    }

    /**
     * {@inheritdoc}
     */
    public function sacar(int $idConta, float $valor): Conta
    {
        if ($valor <= 0) {
            throw new InvalidArgumentException("Valor de saque deve ser maior que zero");
        }

        $conta = $this->repositorioConta->buscarPorId($idConta);
        if (!$conta) {
            throw new InvalidArgumentException("Conta não encontrada com o ID: {$idConta}");
        }
        
        if (!$conta->estaAtiva()) {
            throw new InvalidArgumentException("Não é possível sacar de uma conta inativa");
        }

        $conta->sacar($valor);
        return $this->repositorioConta->salvar($conta);
    }
} 