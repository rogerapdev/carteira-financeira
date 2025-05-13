<?php

namespace App\Domain\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notificacao extends Model
{
    use HasFactory;

    /**
     * Nome da tabela associada ao modelo.
     *
     * @var string
     */
    protected $table = 'notificacoes';

    /**
     * Atributos que podem ser atribuídos em massa.
     *
     * @var array
     */
    protected $fillable = [
        'usuario_id',
        'tipo',
        'titulo',
        'mensagem',
        'dados',
        'lida',
        'enviada',
        'canal',
        'data_enviada',
        'recurso_tipo',
        'recurso_id',
    ];

    /**
     * Atributos que devem ser convertidos para tipos específicos.
     *
     * @var array
     */
    protected $casts = [
        'dados' => 'array',
        'lida' => 'boolean',
        'enviada' => 'boolean',
        'data_enviada' => 'datetime',
    ];

    /**
     * Constantes para tipos de notificações.
     */
    const TIPO_TRANSACAO_CONCLUIDA = 'transacao_concluida';
    const TIPO_TRANSACAO_FALHA = 'transacao_falha';
    const TIPO_ESTORNO_CONCLUIDO = 'estorno_concluido';
    const TIPO_SALDO_BAIXO = 'saldo_baixo';
    const TIPO_TENTATIVA_ACESSO = 'tentativa_acesso';
    const TIPO_NOVO_ACESSO = 'novo_acesso';
    const TIPO_GERAL = 'geral';

    /**
     * Constantes para canais de notificação.
     */
    const CANAL_EMAIL = 'email';
    const CANAL_SMS = 'sms';
    const CANAL_APP = 'app';
    const CANAL_TODOS = 'todos';

    /**
     * Retorna o usuário associado à notificação.
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /**
     * Verifica se a notificação já foi lida.
     *
     * @return bool
     */
    public function estaLida(): bool
    {
        return $this->lida;
    }

    /**
     * Verifica se a notificação já foi enviada.
     *
     * @return bool
     */
    public function foiEnviada(): bool
    {
        return $this->enviada;
    }

    /**
     * Marca a notificação como lida.
     *
     * @return $this
     */
    public function marcarComoLida()
    {
        $this->lida = true;
        $this->save();

        return $this;
    }

    /**
     * Marca a notificação como enviada.
     *
     * @return $this
     */
    public function marcarComoEnviada()
    {
        $this->enviada = true;
        $this->data_enviada = now();
        $this->save();

        return $this;
    }
} 