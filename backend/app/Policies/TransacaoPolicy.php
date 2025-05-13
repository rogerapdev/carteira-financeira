<?php

namespace App\Policies;

use App\Application\Services\AuditoriaService;
use App\Domain\Entities\Transacao;
use App\Domain\Entities\Usuario;
use App\Domain\Interfaces\ContaRepositoryInterface;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Http\Request;

class TransacaoPolicy
{
    use HandlesAuthorization;
    
    /**
     * @var AuditoriaService
     */
    protected $auditoriaService;
    
    /**
     * @var Request
     */
    protected $request;
    
    /**
     * @var ContaRepositoryInterface
     */
    protected $contaRepository;
    
    /**
     * Construtor.
     * 
     * @param AuditoriaService $auditoriaService
     * @param Request $request
     * @param ContaRepositoryInterface $contaRepository
     */
    public function __construct(
        AuditoriaService $auditoriaService,
        Request $request,
        ContaRepositoryInterface $contaRepository
    ) {
        $this->auditoriaService = $auditoriaService;
        $this->request = $request;
        $this->contaRepository = $contaRepository;
    }
    
    /**
     * Determine se o usuário pode ver a transação.
     *
     * @param Usuario $usuario
     * @param Transacao $transacao
     * @return bool
     */
    public function view(Usuario $usuario, Transacao $transacao)
    {
        // Verifica se o usuário está envolvido na transação
        $envolvido = $this->usuarioEnvolvidoTransacao($usuario, $transacao);
        
        if (!$envolvido) {
            $this->auditoriaService->registrarTentativaAcessoNaoAutorizado(
                'transacao',
                $transacao->public_id,
                $this->request
            );
        }
        
        return $envolvido;
    }
    
    /**
     * Determine se o usuário pode estornar a transação.
     *
     * @param Usuario $usuario
     * @param Transacao $transacao
     * @return bool
     */
    public function estornar(Usuario $usuario, Transacao $transacao)
    {
        // Apenas o usuário que originou a transação pode estorná-la
        $autorizado = $this->usuarioOriginouTransacao($usuario, $transacao);
        
        if (!$autorizado) {
            $this->auditoriaService->registrarTentativaAcessoNaoAutorizado(
                'transacao',
                $transacao->public_id,
                $this->request
            );
        }
        
        return $autorizado;
    }
    
    /**
     * Determine se o usuário pode transferir da conta especificada.
     *
     * @param Usuario $usuario
     * @param string $contaOrigemId Public ID da conta de origem
     * @return bool
     */
    public function transferir(Usuario $usuario, string $contaOrigemId)
    {
        // Verifica se a conta de origem pertence ao usuário
        $conta = $this->contaRepository->buscarPorPublicId($contaOrigemId);
        
        $autorizado = $conta && $conta->usuario_id === $usuario->id;
        
        if (!$autorizado) {
            $this->auditoriaService->registrarTentativaAcessoNaoAutorizado(
                'conta',
                $contaOrigemId,
                $this->request
            );
        }
        
        return $autorizado;
    }
    
    /**
     * Determine se o usuário pode depositar na conta especificada.
     *
     * @param Usuario $usuario
     * @param string $contaDestinoId Public ID da conta de destino
     * @return bool
     */
    public function depositar(Usuario $usuario, string $contaDestinoId)
    {
        // Qualquer usuário autenticado pode depositar em qualquer conta
        // Mas vamos registrar a operação
        $this->auditoriaService->registrarOperacaoBemSucedida(
            'Depósito',
            'conta',
            ['conta_destino_id' => $contaDestinoId],
            $this->request
        );
        
        return true;
    }
    
    /**
     * Verifica se o usuário está envolvido na transação (origem ou destino).
     *
     * @param Usuario $usuario
     * @param Transacao $transacao
     * @return bool
     */
    protected function usuarioEnvolvidoTransacao(Usuario $usuario, Transacao $transacao): bool
    {
        // Busca as contas envolvidas na transação
        $contaOrigem = $transacao->detalheTransacao->conta_origem_id
            ? $this->contaRepository->buscarPorId($transacao->detalheTransacao->conta_origem_id)
            : null;
            
        $contaDestino = $transacao->detalheTransacao->conta_destino_id
            ? $this->contaRepository->buscarPorId($transacao->detalheTransacao->conta_destino_id)
            : null;
        
        // Verifica se o usuário é dono de alguma das contas envolvidas
        return ($contaOrigem && $contaOrigem->usuario_id === $usuario->id) ||
               ($contaDestino && $contaDestino->usuario_id === $usuario->id);
    }
    
    /**
     * Verifica se o usuário originou a transação (é dono da conta de origem).
     *
     * @param Usuario $usuario
     * @param Transacao $transacao
     * @return bool
     */
    protected function usuarioOriginouTransacao(Usuario $usuario, Transacao $transacao): bool
    {
        // Busca a conta de origem da transação
        $contaOrigem = $transacao->detalheTransacao->conta_origem_id
            ? $this->contaRepository->buscarPorId($transacao->detalheTransacao->conta_origem_id)
            : null;
        
        // Verifica se o usuário é dono da conta de origem
        return $contaOrigem && $contaOrigem->usuario_id === $usuario->id;
    }
} 