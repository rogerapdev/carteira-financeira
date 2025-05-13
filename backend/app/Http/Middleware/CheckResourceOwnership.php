<?php

namespace App\Http\Middleware;

use App\Application\Services\AuditoriaService;
use App\Domain\Interfaces\ContaRepositoryInterface;
use App\Domain\Interfaces\TransacaoRepositoryInterface;
use App\Presentation\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckResourceOwnership
{
    /**
     * @var ContaRepositoryInterface
     */
    protected $contaRepository;

    /**
     * @var TransacaoRepositoryInterface
     */
    protected $transacaoRepository;

    /**
     * @var AuditoriaService
     */
    protected $auditoriaService;

    /**
     * Constructor.
     *
     * @param ContaRepositoryInterface $contaRepository
     * @param TransacaoRepositoryInterface $transacaoRepository
     * @param AuditoriaService $auditoriaService
     */
    public function __construct(
        ContaRepositoryInterface $contaRepository,
        TransacaoRepositoryInterface $transacaoRepository,
        AuditoriaService $auditoriaService
    ) {
        $this->contaRepository = $contaRepository;
        $this->transacaoRepository = $transacaoRepository;
        $this->auditoriaService = $auditoriaService;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string $resourceType O tipo de recurso a verificar ('conta', 'transacao', etc.)
     * @return Response
     */
    public function handle(Request $request, Closure $next, string $resourceType): Response
    {
        $userId = Auth::id();
        
        // Verifica o tipo de recurso e chama o método de verificação apropriado
        switch ($resourceType) {
            case 'conta':
                $publicId = $this->extrairPublicId($request, 'publicId', 'publicIdConta');
                
                if (!$publicId || !$this->verificarPropriedadeConta($request, $userId)) {
                    $this->auditoriaService->registrarTentativaAcessoNaoAutorizado(
                        'conta',
                        $publicId ?? 'desconhecido',
                        $request
                    );
                    return ApiResponse::forbidden('Você não tem permissão para acessar esta conta.');
                }
                break;
                
            case 'transacao':
                $publicId = $this->extrairPublicId($request, 'publicId');
                
                if (!$this->verificarPropriedadeTransacao($request, $userId)) {
                    $this->auditoriaService->registrarTentativaAcessoNaoAutorizado(
                        'transacao',
                        $publicId ?? 'desconhecido',
                        $request
                    );
                    return ApiResponse::forbidden('Você não tem permissão para acessar esta transação.');
                }
                break;
                
            default:
                // Para outros tipos de recursos, podemos adicionar mais verificações conforme necessário
                break;
        }
        
        return $next($request);
    }
    
    /**
     * Verifica se o usuário é proprietário da conta.
     *
     * @param Request $request
     * @param int $userId
     * @return bool
     */
    protected function verificarPropriedadeConta(Request $request, int $userId): bool
    {
        // Pega o publicId da conta da rota
        $publicId = $this->extrairPublicId($request, 'publicId', 'publicIdConta');
        
        if (!$publicId) {
            return false;
        }
        
        // Verifica se a conta pertence ao usuário
        $conta = $this->contaRepository->buscarPorPublicId($publicId);
        
        // Se não encontrou a conta ou ela não pertence ao usuário
        if (!$conta || $conta->usuario_id !== $userId) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Verifica se o usuário é proprietário da transação ou está envolvido nela.
     *
     * @param Request $request
     * @param int $userId
     * @return bool
     */
    protected function verificarPropriedadeTransacao(Request $request, int $userId): bool
    {
        // Para operações de leitura em uma transação específica
        if ($request->isMethod('GET')) {
            $publicId = $this->extrairPublicId($request, 'publicId');
            
            if (!$publicId) {
                return false;
            }
            
            // Busca a transação
            $transacao = $this->transacaoRepository->buscarPorPublicId($publicId);
            
            if (!$transacao) {
                return false;
            }
            
            // Verifica se alguma das contas envolvidas pertence ao usuário
            $contaOrigem = $transacao->detalheTransacao->conta_origem_id 
                ? $this->contaRepository->buscarPorId($transacao->detalheTransacao->conta_origem_id)
                : null;
                
            $contaDestino = $transacao->detalheTransacao->conta_destino_id 
                ? $this->contaRepository->buscarPorId($transacao->detalheTransacao->conta_destino_id)
                : null;
            
            // Se o usuário não é dono de nenhuma das contas envolvidas
            if ((!$contaOrigem || $contaOrigem->usuario_id !== $userId) && 
                (!$contaDestino || $contaDestino->usuario_id !== $userId)) {
                return false;
            }
            
            return true;
        }
        
        // Para operações de estorno
        if ($request->is('*/estornar')) {
            $publicId = $this->extrairPublicId($request, 'publicId');
            
            if (!$publicId) {
                return false;
            }
            
            // Busca a transação
            $transacao = $this->transacaoRepository->buscarPorPublicId($publicId);
            
            if (!$transacao) {
                return false;
            }
            
            // Apenas o dono da conta de origem pode estornar
            $contaOrigem = $transacao->detalheTransacao->conta_origem_id 
                ? $this->contaRepository->buscarPorId($transacao->detalheTransacao->conta_origem_id)
                : null;
                
            if (!$contaOrigem || $contaOrigem->usuario_id !== $userId) {
                return false;
            }
            
            return true;
        }
        
        // Para operações de transferência ou depósito
        if ($request->is('*/transferir') || $request->is('*/depositar')) {
            // Para transferência, verifica se a conta de origem pertence ao usuário
            if ($request->has('conta_origem_id')) {
                $contaOrigemId = $request->input('conta_origem_id');
                $conta = $this->contaRepository->buscarPorPublicId($contaOrigemId);
                
                if (!$conta || $conta->usuario_id !== $userId) {
                    return false;
                }
            }
            
            return true;
        }
        
        // Por padrão, não permitir
        return false;
    }
    
    /**
     * Extrai o public_id da requisição, seja de parâmetros de rota ou de query.
     *
     * @param Request $request
     * @param string ...$possibleParams Possíveis nomes de parâmetros
     * @return string|null
     */
    protected function extrairPublicId(Request $request, string ...$possibleParams): ?string
    {
        foreach ($possibleParams as $param) {
            if ($request->route($param)) {
                return $request->route($param);
            }
            
            if ($request->has($param)) {
                return $request->input($param);
            }
        }
        
        return null;
    }
} 