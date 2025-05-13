<?php

namespace App\Application\Interfaces;

use App\Domain\Entities\Transacao;
use App\Domain\Entities\Usuario;

interface NotificacaoServiceInterface
{
    /**
     * Envia uma notificação após uma transação ser concluída.
     *
     * @param Transacao $transacao
     * @param bool $notificarRemetente Se deve notificar o remetente
     * @param bool $notificarDestinatario Se deve notificar o destinatário 
     * @return void
     */
    public function notificarTransacaoConcluida(
        Transacao $transacao, 
        bool $notificarRemetente = true, 
        bool $notificarDestinatario = true
    ): void;
    
    /**
     * Envia uma notificação após uma transação falhar.
     *
     * @param Transacao $transacao
     * @return void
     */
    public function notificarTransacaoFalha(Transacao $transacao): void;
    
    /**
     * Envia uma notificação após um estorno ser concluído.
     *
     * @param Transacao $transacao
     * @param Transacao $transacaoOriginal
     * @return void
     */
    public function notificarEstornoConcluido(
        Transacao $transacao, 
        Transacao $transacaoOriginal
    ): void;
    
    /**
     * Envia uma notificação sobre saldo baixo para o usuário.
     *
     * @param Usuario $usuario
     * @param float $saldo
     * @param string $numeroConta
     * @return void
     */
    public function notificarSaldoBaixo(
        Usuario $usuario, 
        float $saldo, 
        string $numeroConta
    ): void;
    
    /**
     * Envia uma notificação genérica para o usuário.
     *
     * @param Usuario $usuario
     * @param string $titulo
     * @param string $mensagem
     * @param array $dados
     * @param string $canal Canal de notificação (email, sms, app, etc)
     * @return void
     */
    public function enviarNotificacao(
        Usuario $usuario, 
        string $titulo, 
        string $mensagem, 
        array $dados = [], 
        string $canal = 'email'
    ): void;
} 