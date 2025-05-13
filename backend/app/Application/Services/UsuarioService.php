<?php

namespace App\Application\Services;

use App\Application\DTOs\UsuarioDTO;
use App\Application\Interfaces\UsuarioServiceInterface;
use App\Domain\Entities\Usuario;
use App\Domain\Interfaces\UsuarioRepositoryInterface;
use App\Domain\Interfaces\ContaRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

class UsuarioService implements UsuarioServiceInterface
{
    /**
     * @param UsuarioRepositoryInterface $repositorioUsuario
     * @param ContaRepositoryInterface $repositorioConta
     */
    public function __construct(
        private UsuarioRepositoryInterface $repositorioUsuario,
        private ContaRepositoryInterface $repositorioConta
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function criarUsuario(UsuarioDTO $usuarioDTO): Usuario
    {
        // Verifica se o email já existe
        if ($this->repositorioUsuario->buscarPorEmail($usuarioDTO->email)) {
            throw new InvalidArgumentException('Email já registrado');
        }

        // Verifica se o documento já existe
        if ($this->repositorioUsuario->buscarPorDocumento($usuarioDTO->document)) {
            throw new InvalidArgumentException('Documento já registrado');
        }

        // Cria usuário com os dados validados
        $dadosUsuario = $usuarioDTO->paraArray();
        
        // Criptografa a senha se fornecida
        if (!empty($dadosUsuario['password'])) {
            $dadosUsuario['password'] = Hash::make($dadosUsuario['password']);
        }

        $usuario = new Usuario();
        $usuario->fill($dadosUsuario);
        
        // Salva o usuário no banco de dados
        $usuario = $this->repositorioUsuario->salvar($usuario);

        // Cria uma conta para o usuário
        $this->repositorioConta->criarParaUsuario($usuario);

        return $usuario;
    }

    /**
     * {@inheritdoc}
     */
    public function atualizarUsuario(int $idUsuario, UsuarioDTO $usuarioDTO): Usuario
    {
        $usuario = $this->repositorioUsuario->buscarPorId($idUsuario);

        if (!$usuario) {
            throw new InvalidArgumentException("Usuário não encontrado com o ID: {$idUsuario}");
        }

        // Verifica se o email está sendo alterado e já existe
        if ($usuarioDTO->email !== $usuario->email && $this->repositorioUsuario->buscarPorEmail($usuarioDTO->email)) {
            throw new InvalidArgumentException('Email já registrado');
        }

        // Verifica se o documento está sendo alterado e já existe
        if ($usuarioDTO->document !== $usuario->document && $this->repositorioUsuario->buscarPorDocumento($usuarioDTO->document)) {
            throw new InvalidArgumentException('Documento já registrado');
        }

        $dadosUsuario = $usuarioDTO->paraArray();
        
        // Criptografa a senha se fornecida, caso contrário remove do array de atualização
        if (!empty($dadosUsuario['password'])) {
            $dadosUsuario['password'] = Hash::make($dadosUsuario['password']);
        } else {
            unset($dadosUsuario['password']);
        }

        $usuario->fill($dadosUsuario);
        
        return $this->repositorioUsuario->salvar($usuario);
    }

    /**
     * {@inheritdoc}
     */
    public function buscarUsuarioPorId(int $idUsuario): ?Usuario
    {
        return $this->repositorioUsuario->buscarPorId($idUsuario);
    }

    /**
     * {@inheritdoc}
     */
    public function buscarUsuarioPorEmail(string $email): ?Usuario
    {
        return $this->repositorioUsuario->buscarPorEmail($email);
    }

    /**
     * {@inheritdoc}
     */
    public function deletarUsuario(int $idUsuario): bool
    {
        $usuario = $this->repositorioUsuario->buscarPorId($idUsuario);

        if (!$usuario) {
            throw new InvalidArgumentException("Usuário não encontrado com o ID: {$idUsuario}");
        }

        return $this->repositorioUsuario->deletar($usuario);
    }
} 