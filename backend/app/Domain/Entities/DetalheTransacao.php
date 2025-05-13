<?php

namespace App\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Domain\Entities\Transacao;
use App\Domain\Entities\Conta;
use Illuminate\Support\Str;

class DetalheTransacao extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\DetalheTransacaoFactory::new();
    }
    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array
     */
    protected $fillable = [
        'public_id',
        'transaction_id',
        'from_account_id',
        'to_account_id',
        'metadata',
    ];

    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array
     */
    protected $casts = [
        'metadata' => 'array',
        'public_id' => 'string',
    ];

    /**
     * Define a tabela associada ao modelo.
     *
     * @var string
     */
    protected $table = 'transaction_details';

    /**
     * Boot function do modelo.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // Gera um UUID automaticamente antes de salvar
        static::creating(function ($detalheTransacao) {
            if (empty($detalheTransacao->public_id)) {
                $detalheTransacao->public_id = (string) Str::uuid();
            }
        });
    }

    /**
     * Obtém a transação associada ao detalhe.
     */
    public function transacao()
    {
        return $this->belongsTo(Transacao::class, 'transaction_id');
    }

    /**
     * Obtém a conta de origem da transação pelo ID interno.
     */
    public function contaOrigem()
    {
        return $this->belongsTo(Conta::class, 'from_account_id');
    }

    /**
     * Obtém a conta de destino da transação pelo ID interno.
     */
    public function contaDestino()
    {
        return $this->belongsTo(Conta::class, 'to_account_id');
    }
    
    /**
     * Obtém a conta de origem pelo public_id.
     * 
     * @param string $publicId
     * @return Conta|null
     */
    public function buscarContaOrigemPorPublicId(string $publicId): ?Conta
    {
        return Conta::where('public_id', $publicId)->first();
    }
    
    /**
     * Obtém a conta de destino pelo public_id.
     * 
     * @param string $publicId
     * @return Conta|null
     */
    public function buscarContaDestinoPorPublicId(string $publicId): ?Conta
    {
        return Conta::where('public_id', $publicId)->first();
    }
    
    /**
     * Define a conta de origem usando o public_id.
     * 
     * @param string $publicId
     * @return void
     */
    public function definirContaOrigemPorPublicId(string $publicId): void
    {
        $conta = Conta::where('public_id', $publicId)->first();
        if ($conta) {
            $this->from_account_id = $conta->id;
        }
    }
    
    /**
     * Define a conta de destino usando o public_id.
     * 
     * @param string $publicId
     * @return void
     */
    public function definirContaDestinoPorPublicId(string $publicId): void
    {
        $conta = Conta::where('public_id', $publicId)->first();
        if ($conta) {
            $this->to_account_id = $conta->id;
        }
    }
    
    /**
     * Obtém a transação pelo public_id.
     * 
     * @param string $publicId
     * @return Transacao|null
     */
    public function buscarTransacaoPorPublicId(string $publicId): ?Transacao
    {
        return Transacao::where('public_id', $publicId)->first();
    }
    
    /**
     * Define a transação usando o public_id.
     * 
     * @param string $publicId
     * @return void
     */
    public function definirTransacaoPorPublicId(string $publicId): void
    {
        $transacao = Transacao::where('public_id', $publicId)->first();
        if ($transacao) {
            $this->transaction_id = $transacao->id;
        }
    }
    
    /**
     * Busca um detalhe de transação pelo public_id.
     * 
     * @param string $publicId
     * @return static|null
     */
    public static function buscarPorPublicId(string $publicId): ?self
    {
        return self::where('public_id', $publicId)->first();
    }
}