<?php

namespace App\Presentation\Http\Controllers\API;

use App\Application\DTOs\TransacaoDTO;
use App\Application\Interfaces\TransacaoServiceInterface;
use App\Application\Jobs\ProcessarDeposito;
use App\Application\Jobs\ProcessarEstorno;
use App\Application\Jobs\ProcessarTransferencia;
use App\Application\Services\AuditoriaService;
use App\Domain\Entities\Transacao;
use App\Http\Controllers\Controller;
use App\Presentation\Http\Requests\DepositoTransacaoRequest;
use App\Presentation\Http\Requests\EstornoTransacaoRequest;
use App\Presentation\Http\Requests\TransferenciaTransacaoRequest;
use App\Presentation\Http\Traits\UsaPublicId;
use App\Presentation\Transformers\TransacaoTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use League\Fractal\Manager;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\DataArraySerializer;
use Illuminate\Support\Str;


/**
 * @OA\Tag(
 *     name="Transações",
 *     description="Endpoints para gerenciamento de transações financeiras"
 * )
 */

class TransacaoController extends Controller
{
    use UsaPublicId;

    /**
     * @param TransacaoServiceInterface $servicoTransacao
     * @param Manager $fractal
     * @param AuditoriaService $auditoriaService
     */
    public function __construct(
        private TransacaoServiceInterface $servicoTransacao,
        private Manager $fractal,
        private AuditoriaService $auditoriaService
    ) {
        $this->fractal->setSerializer(new DataArraySerializer());
    }

    /**
     * Lista as transações de uma conta.
     *
     * @OA\Get(
     *     path="/api/contas/{publicIdConta}/transacoes",
     *     summary="Lista transações de uma conta",
     *     description="Retorna uma lista paginada de todas as transações associadas a uma conta específica",
     *     tags={"Transações"},
     *     @OA\Parameter(
     *         name="publicIdConta",
     *         in="path",
     *         required=true,
     *         description="ID público da conta",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de transações retornada com sucesso",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
     *                     @OA\Property(property="account_id", type="string", example="550e8400-e29b-41d4-a716-446655440001"),
     *                     @OA\Property(property="type", type="string", example="deposit"),
     *                     @OA\Property(property="amount", type="number", format="float", example=100.50),
     *                     @OA\Property(property="status", type="string", example="completed"),
     *                     @OA\Property(property="description", type="string", example="Depósito via PIX"),
     *                     @OA\Property(property="reference_id", type="string", nullable=true),
     *                     @OA\Property(property="error_message", type="string", nullable=true),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             ),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="pagination", type="object",
     *                     @OA\Property(property="total", type="integer", example=50),
     *                     @OA\Property(property="count", type="integer", example=10),
     *                     @OA\Property(property="per_page", type="integer", example=10),
     *                     @OA\Property(property="current_page", type="integer", example=1),
     *                     @OA\Property(property="total_pages", type="integer", example=5)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Conta não encontrada"),
     *     @OA\Response(response=422, description="Erro de validação ou processamento"),
     *     security={{"sanctum":{}}}
     * )
     *
     * @param string $publicIdConta
     * @return JsonResponse
     */
    public function listarPorConta(string $publicIdConta): JsonResponse
    {
        try {
            $idConta = $this->obterIdConta($publicIdConta);
            
            if (!$idConta) {
                return $this->erroIdNaoEncontrado('conta');
            }
            
            $paginador = $this->servicoTransacao->buscarTransacoesPorConta($idConta);
            
            $this->auditoriaService->registrarOperacaoBemSucedida(
                'Consulta de transações',
                'conta',
                ['conta_id' => $publicIdConta],
                request()
            );
            
            $recurso = new Collection($paginador->items(), new TransacaoTransformer(), 'transactions');
            $recurso->setPaginator(new IlluminatePaginatorAdapter($paginador));
            
            $dados = $this->fractal->createData($recurso)->toArray();

            return response()->json($dados);
        } catch (\Exception $e) {
            $this->auditoriaService->registrarErroOperacao(
                'Consulta de transações',
                'conta',
                $e,
                request()
            );
            
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Exibe uma transação específica.
     *
     * @OA\Get(
     *     path="/api/transacoes/{publicId}",
     *     summary="Busca detalhes de uma transação",
     *     description="Retorna os detalhes completos de uma transação específica",
     *     tags={"Transações"},
     *     @OA\Parameter(
     *         name="publicId",
     *         in="path",
     *         required=true,
     *         description="ID público da transação",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalhes da transação retornados com sucesso",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="account_id", type="string", example="550e8400-e29b-41d4-a716-446655440001"),
     *                 @OA\Property(property="type", type="string", example="deposit"),
     *                 @OA\Property(property="amount", type="number", format="float", example=100.50),
     *                 @OA\Property(property="status", type="string", example="completed"),
     *                 @OA\Property(property="description", type="string", example="Depósito via PIX"),
     *                 @OA\Property(property="reference_id", type="string", nullable=true),
     *                 @OA\Property(property="error_message", type="string", nullable=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Transação não encontrada"),
     *     @OA\Response(response=500, description="Erro interno do servidor"),
     *     security={{"sanctum":{}}}
     * )
     *
     * @param string $publicId
     * @return JsonResponse
     */
    public function mostrar(string $publicId): JsonResponse
    {
        try {
            $idTransacao = $this->obterIdTransacao($publicId);
            
            if (!$idTransacao) {
                return $this->erroIdNaoEncontrado('transação');
            }
            
            $transacao = $this->servicoTransacao->buscarTransacaoPorId($idTransacao);
            
            if (!$transacao) {
                return response()->json(['message' => 'Transação não encontrada'], 404);
            }
            
            $this->auditoriaService->registrarOperacaoBemSucedida(
                'Consulta de transação',
                'transacao',
                ['transacao_id' => $publicId],
                request()
            );

            $recurso = new Item($transacao, new TransacaoTransformer(), 'transactions');
            $dados = $this->fractal->createData($recurso)->toArray();

            return response()->json($dados);
        } catch (\Exception $e) {
            $this->auditoriaService->registrarErroOperacao(
                'Consulta de transação',
                'transacao',
                $e,
                request()
            );
            
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Cria uma nova transação de depósito e a enfileira para processamento.
     *
     * @OA\Post(
     *     path="/api/transacoes/depositar",
     *     summary="Realiza um depósito",
     *     description="Cria e enfileira uma nova transação de depósito para processamento",
     *     tags={"Transações"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"to_account_id", "amount"},
     *             @OA\Property(property="to_account_id", type="string", description="ID público da conta de destino"),
     *             @OA\Property(property="amount", type="number", format="float", description="Valor do depósito"),
     *             @OA\Property(property="description", type="string", description="Descrição opcional do depósito"),
     *             @OA\Property(property="transaction_key", type="string", description="Chave UUID única da transação")
     *         )
     *     ),
     *     @OA\Response(
     *         response=202,
     *         description="Depósito aceito para processamento",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Depósito enfileirado para processamento"),
     *             @OA\Property(property="amount", type="number", format="float"),
     *             @OA\Property(property="status", type="string", example="pending"),
     *             @OA\Property(property="transaction_key", type="string")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Erro de validação dos dados"),
     *     security={{"sanctum":{}}}
     * )
     *
     * @param DepositoTransacaoRequest $requisicao
     * @return JsonResponse
     */
    public function depositar(DepositoTransacaoRequest $requisicao): JsonResponse
    {
        try {
            
            $transacaoDTO = TransacaoDTO::deArray($requisicao->validated());

            $transacaoPendente = new TransacaoDTO(
                id: null,
                account_id: $transacaoDTO->account_id,
                type: Transacao::TIPO_DEPOSITO,
                amount: $transacaoDTO->amount,
                reference_id: null,
                status: Transacao::STATUS_PENDENTE,
                description: $transacaoDTO->description ?? 'Depósito em processamento',
                to_account_id: $transacaoDTO->to_account_id ?? auth()->user()->public_id,
                transaction_key: $transacaoDTO->transaction_key,
            );

            if(auth()->user()->conta == null || $transacaoPendente->to_account_id != auth()->user()->conta->public_id) {
                return response()->json(['message' => 'Usuário não autorizado para realizar esta operação'], 403);
            }

            // Enfileira o job para processamento assíncrono
            ProcessarDeposito::dispatch($transacaoPendente);
            
            $this->auditoriaService->registrarOperacaoBemSucedida(
                'Depósito enfileirado',
                'transacao',
                [
                    'conta_id' => $transacaoDTO->account_id,
                    'valor' => $transacaoDTO->amount,
                    'transaction_key' => $transacaoDTO->transaction_key
                ],
                request()
            );
            
            Log::info('Depósito enfileirado para processamento', [
                'conta_id' => $transacaoDTO->account_id,
                'valor' => $transacaoDTO->amount,
                'transaction_key' => $transacaoDTO->transaction_key
            ]);

            return response()->json([
                'message' => 'Depósito enfileirado para processamento',
                'amount' => $transacaoDTO->amount,
                'status' => 'pending',
                'transaction_key' => $transacaoDTO->transaction_key
            ], 202);
        } catch (\Exception $e) {
            // dd($e);
            $this->auditoriaService->registrarErroOperacao(
                'Depósito',
                'transacao',
                $e,
                request()
            );
            
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Cria uma nova transação de transferência e a enfileira para processamento.
     *
     * @OA\Post(
     *     path="/api/transacoes/transferir",
     *     summary="Realiza uma transferência entre contas",
     *     description="Cria e enfileira uma nova transação de transferência para processamento",
     *     tags={"Transações"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"from_account_id", "to_account_id", "amount", "transaction_key"},
     *             @OA\Property(property="from_account_id", type="string", description="ID público da conta de origem"),
     *             @OA\Property(property="to_account_id", type="string", description="ID público da conta de destino"),
     *             @OA\Property(property="amount", type="number", format="float", description="Valor da transferência"),
     *             @OA\Property(property="description", type="string", description="Descrição opcional da transferência"),
     *             @OA\Property(property="transaction_key", type="string", description="Chave única da transação")
     *         )
     *     ),
     *     @OA\Response(
     *         response=202,
     *         description="Transferência aceita para processamento",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Transferência enfileirada para processamento"),
     *             @OA\Property(property="from_account_id", type="string"),
     *             @OA\Property(property="to_account_id", type="string"),
     *             @OA\Property(property="amount", type="number", format="float"),
     *             @OA\Property(property="status", type="string", example="pending"),
     *             @OA\Property(property="transaction_key", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação ou saldo insuficiente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="error", type="string", example="saldo_insuficiente")
     *         )
     *     ),
     *     security={{"sanctum":{}}}
     * )
     *
     * @param TransferenciaTransacaoRequest $requisicao
     * @return JsonResponse
     */
    public function transferir(TransferenciaTransacaoRequest $requisicao): JsonResponse
    {
        try {
            $transacaoDTO = TransacaoDTO::deArray($requisicao->validated());
            // dd($transacaoDTO);
            // Verifica saldo antes de enfileirar para evitar processamento desnecessário
            try {
                // Obtém o ID da conta de origem do usuário autenticado
                $idContaOrigem = $this->obterIdConta($transacaoDTO->from_account_id);
                
                
                if (!$idContaOrigem) {
                    return $this->erroIdNaoEncontrado('conta de origem');
                }
                
                // Obtém o ID da conta de destino
                $idContaDestino = $this->obterIdConta($transacaoDTO->to_account_id);
                
                if (!$idContaDestino) {
                    return $this->erroIdNaoEncontrado('conta de destino');
                }
                
                // Obtém o serviço de contas para verificar o saldo
                $servicoConta = app(\App\Application\Interfaces\ContaServiceInterface::class);
                $contaOrigem = $servicoConta->buscarContaPorId($idContaOrigem);
                
                if (!$contaOrigem) {
                    return response()->json(['mensagem' => 'Conta de origem não encontrada'], 404);
                }
                
                if ($contaOrigem->balance < $transacaoDTO->amount) {
                    $this->auditoriaService->registrarAcao(
                        'Tentativa de transferência com saldo insuficiente',
                        'conta',
                        [
                            'conta_origem' => $transacaoDTO->from_account_id,
                            'saldo_disponivel' => $contaOrigem->balance,
                            'valor_transferencia' => $transacaoDTO->amount
                        ],
                        'warning',
                        request()
                    );
                    
                    return response()->json([
                        'message' => 'Saldo insuficiente para realizar esta transferência',
                        'balance' => number_format($contaOrigem->balance, 2, ',', '.'),
                        'amount' => number_format($transacaoDTO->amount, 2, ',', '.'),
                        'fault' => number_format($transacaoDTO->amount - $contaOrigem->balance, 2, ',', '.')
                    ], 422);
                }
                
            } catch (\App\Domain\Exceptions\SaldoInsuficienteException $e) {
                $this->auditoriaService->registrarAcao(
                    'Tentativa de transferência com saldo insuficiente',
                    'conta',
                    [
                        'conta_origem' => $transacaoDTO->from_account_id,
                        'erro' => $e->getMessage()
                    ],
                    'warning',
                    request()
                );
                
                return response()->json([
                    'message' => $e->getMessage(),
                    'error' => 'saldo_insuficiente'
                ], 422);
            }
            
            // Enfileira o job para processamento assíncrono
            ProcessarTransferencia::dispatch($transacaoDTO);
            
            $this->auditoriaService->registrarOperacaoBemSucedida(
                'Transferência enfileirada',
                'transacao',
                [
                    'conta_origem' => $transacaoDTO->from_account_id,
                    'conta_destino' => $transacaoDTO->to_account_id,
                    'valor' => $transacaoDTO->amount,
                    'transaction_key' => $transacaoDTO->transaction_key
                ],
                request()
            );
            
            Log::info('Transferência enfileirada para processamento', [
                'conta_origem' => $transacaoDTO->from_account_id,
                'conta_destino' => $transacaoDTO->to_account_id,
                'valor' => $transacaoDTO->amount,
                'transaction_key' => $transacaoDTO->transaction_key
            ]);

            return response()->json([
                'message' => 'Transferência enfileirada para processamento',
                'from_account_id' => $transacaoDTO->from_account_id,
                'to_account_id' => $transacaoDTO->to_account_id,
                'amount' => $transacaoDTO->amount,
                'status' => 'pending',
                'transaction_key' => $transacaoDTO->transaction_key
            ], 202);
        } catch (\App\Domain\Exceptions\SaldoInsuficienteException $e) {
            $this->auditoriaService->registrarAcao(
                'Tentativa de transferência com saldo insuficiente',
                'conta',
                [
                    'conta_origem' => $transacaoDTO->from_account_id ?? null,
                    'valor' => $transacaoDTO->amount ?? null,
                    'erro' => $e->getMessage()
                ],
                'warning',
                request()
            );
            
            Log::warning('Tentativa de transferência com saldo insuficiente', [
                'erro' => $e->getMessage(),
                'conta_origem' => $transacaoDTO->from_account_id ?? null,
                'valor' => $transacaoDTO->amount ?? null
            ]);
            
            return response()->json([
                'message' => $e->getMessage(),
                'error' => 'saldo_insuficiente'
            ], 422);
        } catch (\Exception $e) {
            $this->auditoriaService->registrarErroOperacao(
                'Transferência',
                'transacao',
                $e,
                request()
            );
            
            Log::error('Erro ao processar transferência', [
                'erro' => $e->getMessage(),
                'tipo' => get_class($e)
            ]);
            
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Enfileira um estorno de transação para processamento.
     *
     * @OA\Post(
     *     path="/api/transacoes/{publicId}/estornar",
     *     summary="Solicita estorno de uma transação",
     *     description="Cria e enfileira uma solicitação de estorno para uma transação específica",
     *     tags={"Transações"},
     *     @OA\Parameter(
     *         name="publicId",
     *         in="path",
     *         required=true,
     *         description="ID público da transação a ser estornada",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"reason"},
     *             @OA\Property(property="reason", type="string", description="Motivo do estorno"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=202,
     *         description="Estorno aceito para processamento",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Estorno enfileirado para processamento"),
     *             @OA\Property(property="original_transaction_id", type="string"),
     *             @OA\Property(property="amount", type="string"),
     *             @OA\Property(property="status", type="string", example="pending"),
     *             @OA\Property(property="original_transaction_type", type="string"),
     *             @OA\Property(property="request_date", type="string", format="date-time"),
     *             @OA\Property(property="transaction_key", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação ou transação não pode ser estornada",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="reason", type="string")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Transação não encontrada"),
     *     @OA\Response(response=500, description="Erro interno do servidor"),
     *     security={{"sanctum":{}}}
     * )
     *
     * @param EstornoTransacaoRequest $requisicao
     * @param string $publicId
     * @return JsonResponse
     */
    public function estornar(EstornoTransacaoRequest $requisicao, string $publicId): JsonResponse
    {
        try {

            // dd(auth()->user()->conta);
            // Busca a transação pelo public_id
            $transacao = $this->servicoTransacao->buscarTransacaoPorPublicId($publicId);
            if (!$transacao) {
                $this->auditoriaService->registrarAcao(
                    'Tentativa de estorno para transação inexistente',
                    'transacao',
                    ['public_id' => $publicId],
                    'warning',
                    request()
                );
                
                Log::warning('Tentativa de estorno para public_id inexistente', [
                    'public_id' => $publicId,
                    'ip' => request()->ip(),
                    'user_id' => auth()->id() ?? 'guest'
                ]);
                
                return $this->erroIdNaoEncontrado('transação');
            }

            if(auth()->user()->conta == null || $transacao->conta->public_id != auth()->user()->conta->public_id) {
                return response()->json(['message' => 'Usuário não autorizado para realizar esta operação'], 403);
            }
            
            // Verifica se a transação pode ser estornada
            if (!$transacao->podeSerEstornada()) {
                $motivo = '';
                
                if ($transacao->foiEstornada()) {
                    $motivo = 'A transação já foi estornada';
                } elseif ($transacao->ehEstorno()) {
                    $motivo = 'A transação é um estorno e não pode ser estornada';
                } elseif (!$transacao->estaConcluida()) {
                    $motivo = 'A transação não está concluída';
                }
                
                Log::warning('Tentativa de estorno para transação que não pode ser estornada', [
                    'public_id' => $publicId,
                    'motivo' => $motivo,
                    'status' => $transacao->status,
                    'tipo' => $transacao->type,
                    'ip' => request()->ip(),
                    'user_id' => auth()->id() ?? 'guest'
                ]);
                
                return response()->json([
                    'message' => 'Esta transação não pode ser estornada',
                    'reason' => $motivo
                ], 422);
            }
            
            $descricao = $requisicao->input('reason');
            $transactionKey = Str::uuid();

            
            // Verificar se já existe um estorno pendente ou concluído
            $estornosExistentes = $transacao->estornos()
                ->whereIn('status', [
                    \App\Domain\Entities\Transacao::STATUS_PENDENTE,
                    \App\Domain\Entities\Transacao::STATUS_CONCLUIDA,
                ])
                ->count();
                
            if ($estornosExistentes > 0) {
                Log::warning('Tentativa de estorno duplicado', [
                    'public_id' => $publicId,
                    'count_estornos' => $estornosExistentes,
                    'ip' => request()->ip(),
                    'user_id' => auth()->id() ?? 'guest',
                    'transaction_key' => $transactionKey
                ]);
                
                return response()->json([
                    'message' => 'Esta transação já possui um estorno em andamento ou concluído',
                    'count_estornos' => $estornosExistentes
                ], 422);
            }
            
            // Enfileira o job para processamento assíncrono, usando o public_id diretamente
            ProcessarEstorno::dispatch($publicId, $descricao, true, false, $transactionKey); // true indica que é public_id
            
            Log::info('Estorno enfileirado para processamento', [
                'id_transacao' => $transacao->id,
                'public_id' => $publicId,
                'descricao' => $descricao,
                'valor' => $transacao->amount,
                'user_id' => auth()->id() ?? 'guest',
                'transaction_key' => $transactionKey
            ]);

            return response()->json([
                'message' => 'Estorno enfileirado para processamento',
                'original_transaction_id' => $publicId,
                'amount' => number_format($transacao->amount, 2, ',', '.'),
                'status' => 'pending',
                'original_transaction_type' => $transacao->type,
                'request_date' => now()->toIso8601String(),
                'transaction_key' => $transactionKey
            ], 202);
        } catch (\App\Domain\Exceptions\TransacaoException $e) {
            Log::warning('Erro específico de transação ao solicitar estorno', [
                'public_id' => $publicId,
                'erro' => $e->getMessage(),
                'ip' => request()->ip(),
                'user_id' => auth()->id() ?? 'guest'
            ]);
            
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Erro ao solicitar estorno', [
                'public_id' => $publicId,
                'erro' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
                'ip' => request()->ip(),
                'user_id' => auth()->id() ?? 'guest'
            ]);
            
            return response()->json(['message' => 'Erro ao processar estorno: ' . $e->getMessage()], 500);
        }
    }
}