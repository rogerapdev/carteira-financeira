<?php

namespace App\Domain\Interfaces;

use App\Domain\Entities\Notificacao;
use Illuminate\Pagination\LengthAwarePaginator;

interface NotificacaoRepositoryInterface
{
    /**
     * Cria uma nova notificação.
     *
     * @param array $dados
     * @return Notificacao
     */
    public function criar(array $dados): Notificacao;
    
    /**
     * Busca uma notificação pelo ID.
     *
     * @param int $id
     * @return Notificacao|null
     */
    public function buscarPorId(int $id): ?Notificacao;
    
    /**
     * Busca todas as notificações de um usuário.
     *
     * @param int $usuarioId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function buscarPorUsuario(int $usuarioId, int $perPage = 15): LengthAwarePaginator;
    
    /**
     * Busca as notificações não lidas de um usuário.
     *
     * @param int $usuarioId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function buscarNaoLidasPorUsuario(int $usuarioId, int $perPage = 15): LengthAwarePaginator;
    
    /**
     * Busca as notificações de um usuário relacionadas a um recurso específico.
     *
     * @param int $usuarioId
     * @param string $recursoTipo
     * @param string $recursoId
     * @return array
     */
    public function buscarPorRecurso(int $usuarioId, string $recursoTipo, string $recursoId): array;
    
    /**
     * Marca uma notificação como lida.
     *
     * @param int $id
     * @return bool
     */
    public function marcarComoLida(int $id): bool;
    
    /**
     * Marca todas as notificações de um usuário como lidas.
     *
     * @param int $usuarioId
     * @return bool
     */
    public function marcarTodasComoLidas(int $usuarioId): bool;
    
    /**
     * Marca uma notificação como enviada.
     *
     * @param int $id
     * @return bool
     */
    public function marcarComoEnviada(int $id): bool;
    
    /**
     * Busca notificações pendentes de envio.
     *
     * @param int $limit
     * @return array
     */
    public function buscarPendentesDeEnvio(int $limit = 100): array;
    
    /**
     * Conta o número de notificações não lidas de um usuário.
     *
     * @param int $usuarioId
     * @return int
     */
    public function contarNaoLidas(int $usuarioId): int;
} 