<?php

namespace App\Application\DTOs;

class UsuarioDTO
{
    /**
     * @param int|null $id
     * @param string $name
     * @param string $email
     * @param string|null $password
     * @param string $phone
     * @param string $document
     * @param string $status
     */
    public function __construct(
        public readonly ?int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $password,
        public readonly string $phone,
        public readonly string $document,
        public readonly string $status = 'active'
    ) {
    }

    /**
     * Cria DTO a partir de um array.
     *
     * @param array $dados
     * @return static
     */
    public static function deArray(array $dados): self
    {
        return new self(
            $dados['id'] ?? null,
            $dados['name'],
            $dados['email'],
            $dados['password'] ?? null,
            $dados['phone'],
            $dados['document'],
            $dados['status'] ?? 'active'
        );
    }

    /**
     * Converte DTO para array.
     *
     * @return array
     */
    public function paraArray(): array
    {
        $dados = [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'document' => $this->document,
            'status' => $this->status,
        ];

        if (!is_null($this->id)) {
            $dados['id'] = $this->id;
        }

        if (!is_null($this->password)) {
            $dados['password'] = $this->password;
        }

        return $dados;
    }
} 