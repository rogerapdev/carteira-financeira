<?php

namespace App\OpenApi\Schemas;

/**
 * @OA\Schema(
 *     schema="Transacao",
 *     title="Transação",
 *     description="Modelo de transação conforme resposta da API",
 *     @OA\Property(property="id", type="string", description="ID público da transação", example="t1a2b3c4-d5e6-7890-abcd-1234567890ef"),
 *     @OA\Property(property="account_id", type="string", description="ID público da conta associada", example="c1a2b3c4-d5e6-7890-abcd-1234567890ef"),
 *     @OA\Property(property="type", type="string", description="Tipo da transação. Valores possíveis: 'transfer', 'deposit', 'reversal'", example="transfer"),
 *     @OA\Property(property="amount", type="number", format="float", description="Valor da transação", example=500.00),
 *     @OA\Property(property="status", type="string", description="Status da transação. Valores possíveis: 'pending', 'completed', 'failed', 'reversed'", example="pending"),
 *     @OA\Property(property="description", type="string", description="Descrição da transação", example="Depósito realizado com sucesso."),
 *     @OA\Property(property="reference_id", type="string", nullable=true, description="ID de referência (pode ser null)", example=null),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Data de criação", example="2024-05-10T14:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Data da última atualização", example="2024-05-10T14:05:00Z"),
 *     @OA\Property(property="error_message", type="string", nullable=true, description="Mensagem de erro se houver falha", example=null),
 * )
 *
 * @OA\Schema(
 *     schema="MetaPaginacao",
 *     title="Metadados de Paginação",
 *     description="Informações sobre a paginação dos resultados",
 *     @OA\Property(property="current_page", type="integer", description="Página atual"),
 *     @OA\Property(property="per_page", type="integer", description="Itens por página"),
 *     @OA\Property(property="total", type="integer", description="Total de itens"),
 *     @OA\Property(property="total_pages", type="integer", description="Total de páginas")
 * )
 */
class Transacao
{
}