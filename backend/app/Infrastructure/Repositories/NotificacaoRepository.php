<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\Notificacao;
use App\Domain\Interfaces\NotificacaoRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class NotificacaoRepository implements NotificacaoRepositoryInterface
{
    /**
     * Cria uma nova notificação.
     *
     * @param array $dados
     * @return Notificacao
     */
    public function criar(array $dados): Notificacao
    {
        return Notificacao::create($dados);
    }
    
    /**
     * Busca uma notificação pelo ID.
     *
     * @param int $id
     * @return Notificacao|null
     */
    public function buscarPorId(int $id): ?Notificacao
    {
        return Notificacao::find($id);
    }
    
    /**
     * Busca todas as notificações de um usuário.
     *
     * @param int $usuarioId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function buscarPorUsuario(int $usuarioId, int $perPage = 15): LengthAwarePaginator
    {
        return Notificacao::where('usuario_id', $usuarioId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
    
    /**
     * Busca as notificações não lidas de um usuário.
     *
     * @param int $usuarioId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function buscarNaoLidasPorUsuario(int $usuarioId, int $perPage = 15): LengthAwarePaginator
    {
        return Notificacao::where('usuario_id', $usuarioId)
            ->where('lida', false)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
    
    /**
     * Busca as notificações de um usuário relacionadas a um recurso específico.
     *
     * @param int $usuarioId
     * @param string $recursoTipo
     * @param string $recursoId
     * @return array
     */
    public function buscarPorRecurso(int $usuarioId, string $recursoTipo, string $recursoId): array
    {
        return Notificacao::where('usuario_id', $usuarioId)
            ->where('recurso_tipo', $recursoTipo)
            ->where('recurso_id', $recursoId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }
    
    /**
     * Marca uma notificação como lida.
     *
     * @param int $id
     * @return bool
     */
    public function marcarComoLida(int $id): bool
    {
        $notificacao = $this->buscarPorId($id);
        
        if (!$notificacao) {
            return false;
        }
        
        return $notificacao->marcarComoLida() !== null;
    }
    
    /**
     * Marca todas as notificações de um usuário como lidas.
     *
     * @param int $usuarioId
     * @return bool
     */
    public function marcarTodasComoLidas(int $usuarioId): bool
    {
        return Notificacao::where('usuario_id', $usuarioId)
            ->where('lida', false)
            ->update(['lida' => true]) > 0;
    }
    
    /**
     * Marca uma notificação como enviada.
     *
     * @param int $id
     * @return bool
     */
    public function marcarComoEnviada(int $id): bool
    {
        $notificacao = $this->buscarPorId($id);
        
        if (!$notificacao) {
            return false;
        }
        
        return $notificacao->marcarComoEnviada() !== null;
    }
    
    /**
     * Busca notificações pendentes de envio.
     *
     * @param int $limit
     * @return array
     */
    public function buscarPendentesDeEnvio(int $limit = 100): array
    {
        return Notificacao::where('enviada', false)
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
    
    /**
     * Conta o número de notificações não lidas de um usuário.
     *
     * @param int $usuarioId
     * @return int
     */
    public function contarNaoLidas(int $usuarioId): int
    {
        return Notificacao::where('usuario_id', $usuarioId)
            ->where('lida', false)
            ->count();
    }
} 