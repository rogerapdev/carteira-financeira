<?php

namespace App\Presentation\Http\Controllers\API;

use App\Application\DTOs\ContaDTO;
use App\Application\Interfaces\ContaServiceInterface;
use App\Application\Services\AuditoriaService;
use App\Http\Controllers\Controller;
use App\Presentation\Http\Requests\ContaRequest;
use App\Presentation\Http\Requests\DepositoRequest;
use App\Presentation\Http\Requests\SaqueRequest;
use App\Presentation\Http\Traits\UsaPublicId;
use App\Presentation\Transformers\ContaTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\DataArraySerializer;

/**
 * @OA\Tag(
 *     name="Contas",
 *     description="Endpoints para gerenciamento de contas bancárias"
 * )
 */

class ContaController extends Controller
{
    use UsaPublicId;

    /**
     * @param ContaServiceInterface $servicoConta
     * @param Manager $fractal
     * @param AuditoriaService $auditoriaService
     */
    public function __construct(
        private ContaServiceInterface $servicoConta,
        private Manager $fractal,
        private AuditoriaService $auditoriaService
    ) {
        $this->fractal->setSerializer(new DataArraySerializer());
    }

    /**
     * Exibe uma conta específica.
     *
     * @OA\Get(
     *     path="/api/contas/{publicId}",
     *     summary="Busca detalhes de uma conta",
     *     description="Retorna os detalhes completos de uma conta específica",
     *     tags={"Contas"},
     *     @OA\Parameter(
     *         name="publicId",
     *         in="path",
     *         required=true,
     *         description="ID público da conta",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalhes da conta retornados com sucesso",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="id", type="string", description="ID público da conta"),
     *                 @OA\Property(property="user_id", type="string", description="ID público do usuário"),
     *                 @OA\Property(property="balance", type="number", format="float", description="Saldo atual da conta"),
     *                 @OA\Property(property="status", type="string", description="Status atual da conta"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", description="Data de criação"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", description="Data de última atualização")
     *             }
     *         )
     *     ),
     *     @OA\Response(response=404, description="Conta não encontrada"),
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
            $idInterno = $this->obterIdConta($publicId);
            
            if (!$idInterno) {
                return $this->erroIdNaoEncontrado('conta');
            }
            
            $conta = $this->servicoConta->buscarContaPorId($idInterno);
            
            if (!$conta) {
                return response()->json(['mensagem' => 'Conta não encontrada'], 404);
            }
            
            $this->auditoriaService->registrarOperacaoBemSucedida(
                'Consulta de conta',
                'conta',
                ['conta_id' => $publicId],
                request()
            );

            $recurso = new Item($conta, new ContaTransformer(), 'accounts');
            $dados = $this->fractal->createData($recurso)->toArray();

            return response()->json($dados);
        } catch (\Exception $e) {
            $this->auditoriaService->registrarErroOperacao(
                'Consulta de conta',
                'conta',
                $e,
                request()
            );
            
            return response()->json(['mensagem' => $e->getMessage()], 500);
        }
    }

    /**
     * Realiza um depósito na conta.
     *
     * @OA\Post(
     *     path="/api/contas/{publicId}/depositar",
     *     summary="Realiza um depósito direto na conta",
     *     description="Efetua um depósito direto em uma conta específica",
     *     tags={"Contas"},
     *     @OA\Parameter(
     *         name="publicId",
     *         in="path",
     *         required=true,
     *         description="ID público da conta",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"amount"},
     *             @OA\Property(property="amount", type="number", format="float", description="Valor do depósito")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Depósito realizado com sucesso",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/Conta"),
     *             @OA\Property(property="meta", type="object", ref="#/components/schemas/MetaPaginacao", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=422, description="Erro de validação dos dados"),
     *     security={{"sanctum":{}}}
     * )
     *
     * @param DepositoRequest $requisicao
     * @param string $publicId
     * @return JsonResponse
     */
    public function depositar(DepositoRequest $requisicao, string $publicId): JsonResponse
    {
        try {
            $idInterno = $this->obterIdConta($publicId);
            
            if (!$idInterno) {
                return $this->erroIdNaoEncontrado('conta');
            }
            
            $valor = (float) $requisicao->input('amount');
            $conta = $this->servicoConta->depositar($idInterno, $valor);
            
            $this->auditoriaService->registrarOperacaoBemSucedida(
                'Depósito direto',
                'conta',
                [
                    'conta_id' => $publicId,
                    'valor' => $valor
                ],
                request()
            );

            $recurso = new Item($conta, new ContaTransformer(), 'accounts');
            $dados = $this->fractal->createData($recurso)->toArray();

            return response()->json($dados);
        } catch (\Exception $e) {
            $this->auditoriaService->registrarErroOperacao(
                'Depósito direto',
                'conta',
                $e,
                request()
            );
            
            return response()->json(['mensagem' => $e->getMessage()], 422);
        }
    }

    /**
     * Realiza um saque da conta.
     *
     * @OA\Post(
     *     path="/api/contas/{publicId}/sacar",
     *     summary="Realiza um saque da conta",
     *     description="Efetua um saque de uma conta específica",
     *     tags={"Contas"},
     *     @OA\Parameter(
     *         name="publicId",
     *         in="path",
     *         required=true,
     *         description="ID público da conta",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"amount"},
     *             @OA\Property(property="amount", type="number", format="float", description="Valor do saque")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Saque realizado com sucesso",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/Conta"),
     *             @OA\Property(property="meta", type="object", ref="#/components/schemas/MetaPaginacao", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação ou saldo insuficiente",
     *         @OA\JsonContent(
     *             @OA\Property(property="mensagem", type="string")
     *         )
     *     ),
     *     security={{"sanctum":{}}}
     * )
     *
     * @param SaqueRequest $requisicao
     * @param string $publicId
     * @return JsonResponse
     */
    public function sacar(SaqueRequest $requisicao, string $publicId): JsonResponse
    {
        try {
            $idInterno = $this->obterIdConta($publicId);
            
            if (!$idInterno) {
                return $this->erroIdNaoEncontrado('conta');
            }
            
            $valor = (float) $requisicao->input('amount');
            $conta = $this->servicoConta->sacar($idInterno, $valor);
            
            $this->auditoriaService->registrarOperacaoBemSucedida(
                'Saque',
                'conta',
                [
                    'conta_id' => $publicId,
                    'valor' => $valor
                ],
                request()
            );

            $recurso = new Item($conta, new ContaTransformer(), 'accounts');
            $dados = $this->fractal->createData($recurso)->toArray();

            return response()->json($dados);
        } catch (\Exception $e) {
            if ($e instanceof \App\Domain\Exceptions\SaldoInsuficienteException) {
                $this->auditoriaService->registrarAcao(
                    'Tentativa de saque com saldo insuficiente',
                    'conta',
                    [
                        'conta_id' => $publicId,
                        'valor_solicitado' => $valor,
                        'erro' => $e->getMessage()
                    ],
                    'warning',
                    request()
                );
            } else {
                $this->auditoriaService->registrarErroOperacao(
                    'Saque',
                    'conta',
                    $e,
                    request()
                );
            }
            
            return response()->json(['mensagem' => $e->getMessage()], 422);
        }
    }
}