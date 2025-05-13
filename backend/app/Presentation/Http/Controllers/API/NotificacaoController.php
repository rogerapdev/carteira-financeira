<?php

namespace App\Presentation\Http\Controllers\API;

use App\Application\Services\AuditoriaService;
use App\Domain\Interfaces\NotificacaoRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Presentation\Transformers\NotificacaoTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use League\Fractal\Manager;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\DataArraySerializer;

/**
 * @OA\Tag(
 *     name="Notificações",
 *     description="Endpoints para gerenciamento de notificações do usuário"
 * )
 */

class NotificacaoController extends Controller
{
    /**
     * @param NotificacaoRepositoryInterface $notificacaoRepository
     * @param Manager $fractal
     * @param AuditoriaService $auditoriaService
     */
    public function __construct(
        private NotificacaoRepositoryInterface $notificacaoRepository,
        private Manager $fractal,
        private AuditoriaService $auditoriaService
    ) {
        $this->fractal->setSerializer(new DataArraySerializer());
    }

    /**
     * Lista todas as notificações do usuário.
     *
     * @OA\Get(
     *     path="/api/notificacoes",
     *     summary="Lista todas as notificações",
     *     description="Retorna uma lista paginada de todas as notificações do usuário autenticado",
     *     tags={"Notificações"},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Número de itens por página",
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de notificações retornada com sucesso",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Notificacao")),
     *             @OA\Property(property="meta", ref="#/components/schemas/MetaPaginacao")
     *         )
     *     ),
     *     @OA\Response(response=500, description="Erro interno do servidor"),
     *     security={{"sanctum":{}}}
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function listar(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $usuarioId = Auth::id();
            
            $paginador = $this->notificacaoRepository->buscarPorUsuario($usuarioId, $perPage);
            
            $recurso = new Collection($paginador->items(), new NotificacaoTransformer(), 'notifications');
            $recurso->setPaginator(new IlluminatePaginatorAdapter($paginador));
            
            $dados = $this->fractal->createData($recurso)->toArray();
            
            $this->auditoriaService->registrarOperacaoBemSucedida(
                'Consulta de notificações',
                'notificacao',
                ['usuario_id' => $usuarioId],
                $request
            );

            return response()->json($dados);
        } catch (\Exception $e) {
            $this->auditoriaService->registrarErroOperacao(
                'Consulta de notificações',
                'notificacao',
                $e,
                $request
            );
            
            return response()->json(['mensagem' => $e->getMessage()], 500);
        }
    }

    /**
     * Lista as notificações não lidas do usuário.
     *
     * @OA\Get(
     *     path="/api/notificacoes/nao-lidas",
     *     summary="Lista notificações não lidas",
     *     description="Retorna uma lista paginada das notificações não lidas do usuário autenticado",
     *     tags={"Notificações"},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Número de itens por página",
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de notificações não lidas retornada com sucesso",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Notificacao")),
     *             @OA\Property(property="meta", ref="#/components/schemas/MetaPaginacao")
     *         )
     *     ),
     *     @OA\Response(response=500, description="Erro interno do servidor"),
     *     security={{"sanctum":{}}}
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function listarNaoLidas(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $usuarioId = Auth::id();
            
            $paginador = $this->notificacaoRepository->buscarNaoLidasPorUsuario($usuarioId, $perPage);
            
            $recurso = new Collection($paginador->items(), new NotificacaoTransformer(), 'notifications');
            $recurso->setPaginator(new IlluminatePaginatorAdapter($paginador));
            
            $dados = $this->fractal->createData($recurso)->toArray();
            
            $this->auditoriaService->registrarOperacaoBemSucedida(
                'Consulta de notificações não lidas',
                'notificacao',
                ['usuario_id' => $usuarioId],
                $request
            );

            return response()->json($dados);
        } catch (\Exception $e) {
            $this->auditoriaService->registrarErroOperacao(
                'Consulta de notificações não lidas',
                'notificacao',
                $e,
                $request
            );
            
            return response()->json(['mensagem' => $e->getMessage()], 500);
        }
    }

    /**
     * Marca uma notificação como lida.
     *
     * @OA\Post(
     *     path="/api/notificacoes/{id}/ler",
     *     summary="Marca notificação como lida",
     *     description="Marca uma notificação específica como lida",
     *     tags={"Notificações"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID da notificação",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notificação marcada como lida com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="mensagem", type="string", example="Notificação marcada como lida"),
     *             @OA\Property(property="success", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Acesso não autorizado"),
     *     @OA\Response(response=404, description="Notificação não encontrada"),
     *     @OA\Response(response=500, description="Erro interno do servidor"),
     *     security={{"sanctum":{}}}
     * )
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function marcarComoLida(int $id, Request $request): JsonResponse
    {
        try {
            $usuarioId = Auth::id();
            $notificacao = $this->notificacaoRepository->buscarPorId($id);
            
            if (!$notificacao) {
                return response()->json(['mensagem' => 'Notificação não encontrada'], 404);
            }
            
            if ($notificacao->usuario_id !== $usuarioId) {
                $this->auditoriaService->registrarTentativaAcessoNaoAutorizado(
                    'notificacao',
                    (string) $id,
                    $request
                );
                
                return response()->json(['mensagem' => 'Você não tem permissão para acessar esta notificação'], 403);
            }
            
            $resultado = $this->notificacaoRepository->marcarComoLida($id);
            
            $this->auditoriaService->registrarOperacaoBemSucedida(
                'Marcação de notificação como lida',
                'notificacao',
                ['notificacao_id' => $id],
                $request
            );

            return response()->json([
                'mensagem' => 'Notificação marcada como lida',
                'success' => $resultado
            ]);
        } catch (\Exception $e) {
            $this->auditoriaService->registrarErroOperacao(
                'Marcação de notificação como lida',
                'notificacao',
                $e,
                $request
            );
            
            return response()->json(['mensagem' => $e->getMessage()], 500);
        }
    }

    /**
     * Marca todas as notificações do usuário como lidas.
     *
     * @OA\Post(
     *     path="/api/notificacoes/ler-todas",
     *     summary="Marca todas as notificações como lidas",
     *     description="Marca todas as notificações do usuário autenticado como lidas",
     *     tags={"Notificações"},
     *     @OA\Response(
     *         response=200,
     *         description="Notificações marcadas como lidas com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="mensagem", type="string", example="Todas as notificações foram marcadas como lidas"),
     *             @OA\Property(property="success", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=500, description="Erro interno do servidor"),
     *     security={{"sanctum":{}}}
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function marcarTodasComoLidas(Request $request): JsonResponse
    {
        try {
            $usuarioId = Auth::id();
            
            $resultado = $this->notificacaoRepository->marcarTodasComoLidas($usuarioId);
            
            $this->auditoriaService->registrarOperacaoBemSucedida(
                'Marcação de todas notificações como lidas',
                'notificacao',
                ['usuario_id' => $usuarioId],
                $request
            );

            return response()->json([
                'mensagem' => 'Todas as notificações foram marcadas como lidas',
                'success' => $resultado
            ]);
        } catch (\Exception $e) {
            $this->auditoriaService->registrarErroOperacao(
                'Marcação de todas notificações como lidas',
                'notificacao',
                $e,
                $request
            );
            
            return response()->json(['mensagem' => $e->getMessage()], 500);
        }
    }

    /**
     * Retorna o número de notificações não lidas.
     *
     * @OA\Get(
     *     path="/api/notificacoes/nao-lidas/quantidade",
     *     summary="Conta notificações não lidas",
     *     description="Retorna o número total de notificações não lidas do usuário autenticado",
     *     tags={"Notificações"},
     *     @OA\Response(
     *         response=200,
     *         description="Quantidade de notificações não lidas retornada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="quantidade", type="integer", example=5)
     *         )
     *     ),
     *     @OA\Response(response=500, description="Erro interno do servidor"),
     *     security={{"sanctum":{}}}
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function contarNaoLidas(Request $request): JsonResponse
    {
        try {
            $usuarioId = Auth::id();
            
            $quantidade = $this->notificacaoRepository->contarNaoLidas($usuarioId);
            
            return response()->json([
                'quantidade' => $quantidade
            ]);
        } catch (\Exception $e) {
            $this->auditoriaService->registrarErroOperacao(
                'Contagem de notificações não lidas',
                'notificacao',
                $e,
                $request
            );
            
            return response()->json(['mensagem' => $e->getMessage()], 500);
        }
    }
}