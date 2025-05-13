<?php

namespace App\Application\DTOs;

class TransacaoDTO
{
    /**
     * @param int|null $id
     * @param int $account_id
     * @param string $type
     * @param float $amount
     * @param int|null $reference_id
     * @param string $status
     * @param string|null $description
     * @param int|null $from_account_id
     * @param int|null $to_account_id
     * @param array|null $metadata
     * @param string|null $transaction_key
     * @param string|null $error_message
     */
    public function __construct(
        public readonly ?int $id,
        public readonly ?int $account_id,
        public readonly ?string $type,
        public readonly float $amount,
        public readonly ?int $reference_id,
        public readonly string $status,
        public readonly ?string $description,
        public readonly ?string $from_account_id = null,
        public readonly ?string $to_account_id = null,
        public readonly ?array $metadata = null,
        public readonly ?string $transaction_key = null,
        public readonly ?string $error_message = null
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
            isset($dados['id']) ? (int)$dados['id'] : null,
            isset($dados['account_id']) ? (int)$dados['account_id'] : auth()->user()->id,
            $dados['type'] ?? null,
            (float) $dados['amount'],
            isset($dados['reference_id']) ? (int)$dados['reference_id'] : null,
            $dados['status'] ?? 'pending',
            $dados['description'] ?? null,
            isset($dados['from_account_id']) ? $dados['from_account_id'] : null,
            isset($dados['to_account_id']) ? $dados['to_account_id'] : null,
            $dados['metadata'] ?? null,
            $dados['transaction_key'] ?? null,
            $dados['error_message'] ?? null
        );
    }

    /**
     * Converte DTO para array.
     *
     * @return array
     */
    public function paraArray(): array
    {
        $dadosTransacao = [
            'account_id' => $this->account_id,
            'type' => $this->type,
            'amount' => $this->amount,
            'status' => $this->status,
        ];

        $dadosDetalhes = [];

        if (!is_null($this->id)) {
            $dadosTransacao['id'] = $this->id;
        }

        if (!is_null($this->reference_id)) {
            $dadosTransacao['reference_id'] = (int)$this->reference_id;
        }

        if (!is_null($this->description)) {
            $dadosTransacao['description'] = $this->description;
        }

        if (!is_null($this->transaction_key)) {
            $dadosTransacao['transaction_key'] = $this->transaction_key;
        }

        if (!is_null($this->error_message)) {
            $dadosTransacao['error_message'] = $this->error_message;
        }

        if (!is_null($this->from_account_id)) {
            $dadosDetalhes['from_account_id'] = $this->from_account_id;
        }

        if (!is_null($this->to_account_id)) {
            $dadosDetalhes['to_account_id'] = $this->to_account_id;
        }

        if (!is_null($this->metadata)) {
            $dadosDetalhes['metadata'] = $this->metadata;
        }

        return [
            'transacao' => $dadosTransacao,
            'detalhes' => $dadosDetalhes
        ];
    }
}
