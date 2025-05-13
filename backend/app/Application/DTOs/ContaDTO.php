<?php

namespace App\Application\DTOs;

class ContaDTO
{
    /**
     * @param int|null $id
     * @param int $user_id
     * @param float $balance
     * @param string $status
     */
    public function __construct(
        public readonly ?int $id,
        public readonly int $user_id,
        public readonly float $balance,
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
            $dados['user_id'],
            (float) $dados['balance'],
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
            'user_id' => $this->user_id,
            'balance' => $this->balance,
            'status' => $this->status,
        ];

        if (!is_null($this->id)) {
            $dados['id'] = $this->id;
        }

        return $dados;
    }
} 