<?php

namespace App\OpenApi\Schemas;

/**
 * @OA\Schema(
 *     schema="Notificacao",
 *     title="Notificação",
 *     description="Modelo de notificação conforme resposta da API",
 *     @OA\Property(property="id", type="integer", description="ID interno da notificação", example=101),
 *     @OA\Property(property="tipo", type="string", description="Tipo da notificação", example="info"),
 *     @OA\Property(property="titulo", type="string", description="Título da notificação", example="Saldo atualizado"),
 *     @OA\Property(property="mensagem", type="string", description="Conteúdo da notificação", example="Seu saldo foi atualizado com sucesso."),
 *     @OA\Property(property="dados", type="string", description="Dados adicionais da notificação", example={"valor": 1500.75}),
 *     @OA\Property(property="lida", type="boolean", description="Se a notificação foi lida", example=false),
 *     @OA\Property(property="enviada", type="boolean", description="Se a notificação foi enviada", example=true),
 *     @OA\Property(property="canal", type="string", description="Canal de envio da notificação", example="email"),
 *     @OA\Property(property="data_enviada", type="string", format="date-time", nullable=true, description="Data de envio da notificação", example="2024-05-10T16:00:00Z"),
 *     @OA\Property(property="recurso_tipo", type="string", description="Tipo do recurso relacionado", example="conta"),
 *     @OA\Property(property="recurso_id", type="string", description="ID do recurso relacionado", example="c1a2b3c4-d5e6-7890-abcd-1234567890ef"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Data de criação", example="2024-05-10T15:55:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Data da última atualização", example="2024-05-10T16:00:00Z"),
 * )
 */
class Notificacao
{
}