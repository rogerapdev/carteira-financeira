<?php

namespace App\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Domain\Entities\Usuario;
use App\Domain\Entities\Transacao;
use App\Domain\Exceptions\SaldoInsuficienteException;
use Illuminate\Support\Str;

class Conta extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\ContaFactory::new();
    }
    
    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'public_id',
        'balance',
        'status',
        'type',
    ];

    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array
     */
    protected $casts = [
        'balance' => 'decimal:2',
        'public_id' => 'string',
    ];

    /**
     * Define a tabela associada ao modelo.
     *
     * @var string
     */
    protected $table = 'accounts';

    /**
     * Boot function do modelo.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // Gera um UUID automaticamente antes de salvar
        static::creating(function ($conta) {
            if (empty($conta->public_id)) {
                $conta->public_id = (string) Str::uuid();
            }
        });
    }

    /**
     * Obtém o usuário que possui a conta.
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }

    /**
     * Obtém as transações para a conta.
     */
    public function transacoes()
    {
        return $this->hasMany(Transacao::class, 'account_id');
    }

    /**
     * Deposita dinheiro na conta.
     *
     * @param float $valor
     * @return self
     */
    public function depositar(float $valor): self
    {
        $this->balance += $valor;
        return $this;
    }

    /**
     * Saca dinheiro da conta.
     *
     * @param float $valor
     * @return self
     * @throws SaldoInsuficienteException
     */
    public function sacar(float $valor): self
    {
        if ($this->balance < $valor) {
            throw new SaldoInsuficienteException(
                "Saldo insuficiente. Necessário: R$ {$valor}, Disponível: R$ {$this->balance}"
            );
        }

        $this->balance -= $valor;
        return $this;
    }

    /**
     * Transfere dinheiro para outra conta.
     *
     * @param Conta $destinatario
     * @param float $valor
     * @return void
     * @throws SaldoInsuficienteException
     */
    public function transferir(Conta $destinatario, float $valor): void
    {
        $this->sacar($valor);
        $destinatario->depositar($valor);
    }

    /**
     * Verifica se a conta está ativa.
     */
    public function estaAtiva(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Ativa a conta.
     */
    public function ativar(): self
    {
        $this->status = 'active';
        return $this;
    }

    /**
     * Desativa a conta.
     */
    public function desativar(): self
    {
        $this->status = 'inactive';
        return $this;
    }
}