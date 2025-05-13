<?php

namespace App\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Domain\Entities\Conta;
use App\Domain\Entities\DetalheTransacao;
use Illuminate\Support\Str;

class Transacao extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\TransacaoFactory::new();
    }

    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array
     */
    protected $fillable = [
        'account_id',
        'public_id',
        'type',
        'amount',
        'reference_id',
        'status',
        'description',
        'transaction_key',
        'error_message',
    ];

    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'public_id' => 'string',
        'transaction_key' => 'string',
    ];

    /**
     * Define a tabela associada ao modelo.
     *
     * @var string
     */
    protected $table = 'transactions';

    /**
     * Tipos de transação
     */
    public const TIPO_TRANSFERENCIA = 'transfer';
    public const TIPO_DEPOSITO = 'deposit';
    public const TIPO_ESTORNO = 'reversal';

    /**
     * Status de transação
     */
    public const STATUS_PENDENTE = 'pending';
    public const STATUS_CONCLUIDA = 'completed';
    public const STATUS_FALHA = 'failed';
    public const STATUS_ESTORNADA = 'reversed';

    /**
     * Boot function do modelo.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // Gera um UUID automaticamente antes de salvar
        static::creating(function ($transacao) {
            if (empty($transacao->public_id)) {
                $transacao->public_id = (string) Str::uuid();
            }
        });
    }

    /**
     * Obtém a conta que possui a transação.
     */
    public function conta()
    {
        return $this->belongsTo(Conta::class, 'account_id');
    }

    /**
     * Obtém os detalhes da transação.
     */
    public function detalhes()
    {
        return $this->hasOne(DetalheTransacao::class, 'transaction_id');
    }
    
    /**
     * Obtém a transação original para um estorno.
     */
    public function transacaoOriginal()
    {
        return $this->belongsTo(Transacao::class, 'reference_id');
    }
    
    /**
     * Obtém os estornos para esta transação.
     */
    public function estornos()
    {
        return $this->hasMany(Transacao::class, 'reference_id');
    }

    /**
     * Verifica se esta transação é uma transferência.
     */
    public function ehTransferencia(): bool
    {
        return $this->type === self::TIPO_TRANSFERENCIA;
    }

    /**
     * Verifica se esta transação é um depósito.
     */
    public function ehDeposito(): bool
    {
        return $this->type === self::TIPO_DEPOSITO;
    }

    /**
     * Verifica se esta transação é um estorno.
     */
    public function ehEstorno(): bool
    {
        return $this->type === self::TIPO_ESTORNO;
    }

    /**
     * Verifica se esta transação está concluída.
     */
    public function estaConcluida(): bool
    {
        return $this->status === self::STATUS_CONCLUIDA;
    }

    /**
     * Verifica se esta transação está pendente.
     */
    public function estaPendente(): bool
    {
        return $this->status === self::STATUS_PENDENTE;
    }

    /**
     * Verifica se esta transação falhou.
     */
    public function temFalha(): bool
    {
        return $this->status === self::STATUS_FALHA;
    }

    /**
     * Verifica se esta transação foi estornada.
     */
    public function foiEstornada(): bool
    {
        return $this->status === self::STATUS_ESTORNADA;
    }

    /**
     * Verifica se esta transação pode ser estornada.
     */
    public function podeSerEstornada(): bool
    {
        return $this->estaConcluida() && !$this->foiEstornada() && !$this->ehEstorno();
    }

    /**
     * Marca a transação como concluída.
     */
    public function marcarComoConcluida(): self
    {
        $this->status = self::STATUS_CONCLUIDA;
        return $this;
    }

    /**
     * Marca a transação como falha com uma mensagem de erro.
     * 
     * @param string $mensagemErro A mensagem de erro que causou a falha
     * @return self
     */
    public function marcarComoFalha(string $mensagemErro = null): self
    {
        $this->status = self::STATUS_FALHA;
        $this->error_message = $mensagemErro;
        return $this;
    }

    /**
     * Marca a transação como estornada.
     */
    public function marcarComoEstornada(): self
    {
        $this->status = self::STATUS_ESTORNADA;
        return $this;
    }
}