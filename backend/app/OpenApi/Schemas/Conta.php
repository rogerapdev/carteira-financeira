<?php

namespace App\OpenApi\Schemas;

/**
 * @OA\Schema(
 *     schema="Conta",
 *     title="Conta",
 *     description="Modelo de conta bancária conforme resposta da API",
 *     @OA\Property(property="id", type="string", description="ID público da conta", example="c1a2b3c4-d5e6-7890-abcd-1234567890ef"),
 *     @OA\Property(property="user_id", type="string", description="ID público do usuário", example="u1b2c3d4-e5f6-7890-abcd-1234567890ef"),
 *     @OA\Property(property="balance", type="number", format="float", description="Saldo atual da conta", example=1500.75),
 *     @OA\Property(property="status", type="string", description="Status atual da conta. Valores possíveis: 'active', 'inactive'", example="active"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Data de criação", example="2024-05-01T12:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Data da última atualização", example="2024-05-10T15:30:00Z"),
 * )
 */
class Conta
{
}