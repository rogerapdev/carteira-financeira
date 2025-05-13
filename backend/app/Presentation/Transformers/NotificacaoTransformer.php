<?php

namespace App\Presentation\Transformers;

use App\Domain\Entities\Notificacao;
use League\Fractal\TransformerAbstract;

class NotificacaoTransformer extends TransformerAbstract
{
    /**
     * Transforma uma notificação para o formato de resposta da API.
     *
     * @param Notificacao $notificacao
     * @return array
     */
    public function transform(Notificacao $notificacao): array
    {
        return [
            'id' => (int) $notificacao->id,
            'tipo' => $notificacao->tipo,
            'titulo' => $notificacao->titulo,
            'mensagem' => $notificacao->mensagem,
            'dados' => $notificacao->dados,
            'lida' => (bool) $notificacao->lida,
            'enviada' => (bool) $notificacao->enviada,
            'canal' => $notificacao->canal,
            'data_enviada' => $notificacao->data_enviada ? $notificacao->data_enviada->toIso8601String() : null,
            'recurso_tipo' => $notificacao->recurso_tipo,
            'recurso_id' => $notificacao->recurso_id,
            'created_at' => $notificacao->created_at->toIso8601String(),
            'updated_at' => $notificacao->updated_at->toIso8601String(),
        ];
    }
} 