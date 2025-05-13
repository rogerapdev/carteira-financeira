<?php

namespace App\Domain\Entities;

use App\Domain\ValueObjects\Document;
use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\Phone;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\UsuarioFactory;

class Usuario extends Authenticatable
{
    use HasApiTokens, Notifiable, HasFactory;

    protected static function newFactory()
    {
        return UsuarioFactory::new();
    }

    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'document',
        'status',
        'public_id',
    ];

    /**
     * Os atributos que devem ser ocultados para serialização.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'public_id' => 'string',
    ];

    /**
     * Define a tabela associada ao modelo.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * Boot function do modelo.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // Gera um UUID automaticamente antes de salvar
        static::creating(function ($usuario) {
            if (empty($usuario->public_id)) {
                $usuario->public_id = (string) Str::uuid();
            }
        });
    }

    /**
     * Obtém a conta do usuário.
     */
    public function conta()
    {
        return $this->hasOne(Conta::class, 'user_id');
    }

    /**
     * Obtém o documento do usuário como objeto de valor.
     */
    public function obterDocumento(): Document
    {
        return new Document($this->document);
    }

    /**
     * Obtém o email do usuário como objeto de valor.
     */
    public function obterEmail(): Email
    {
        return new Email($this->email);
    }

    /**
     * Obtém o telefone do usuário como objeto de valor.
     */
    public function obterTelefone(): Phone
    {
        return new Phone($this->phone);
    }

    /**
     * Verifica se o usuário está ativo.
     */
    public function estaAtivo(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Ativa o usuário.
     */
    public function ativar(): self
    {
        $this->status = 'active';
        return $this;
    }

    /**
     * Desativa o usuário.
     */
    public function desativar(): self
    {
        $this->status = 'inactive';
        return $this;
    }
}