<?php

namespace App\Policies;

use App\Application\Services\AuditoriaService;
use App\Domain\Entities\Conta;
use App\Domain\Entities\Usuario;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Http\Request;

class ContaPolicy
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
     * Construtor.
     * 
     * @param AuditoriaService $auditoriaService
     * @param Request $request
     */
    public function __construct(AuditoriaService $auditoriaService, Request $request)
    {
        $this->auditoriaService = $auditoriaService;
        $this->request = $request;
    }
    
    /**
     * Determine se o usuário pode ver a conta.
     *
     * @param Usuario $usuario
     * @param Conta $conta
     * @return bool
     */
    public function view(Usuario $usuario, Conta $conta)
    {
        $autorizado = $usuario->id === $conta->usuario_id;
        
        if (!$autorizado) {
            $this->auditoriaService->registrarTentativaAcessoNaoAutorizado(
                'conta',
                $conta->public_id,
                $this->request
            );
        }
        
        return $autorizado;
    }
    
    /**
     * Determine se o usuário pode atualizar a conta.
     *
     * @param Usuario $usuario
     * @param Conta $conta
     * @return bool
     */
    public function update(Usuario $usuario, Conta $conta)
    {
        $autorizado = $usuario->id === $conta->usuario_id;
        
        if (!$autorizado) {
            $this->auditoriaService->registrarTentativaAcessoNaoAutorizado(
                'conta',
                $conta->public_id,
                $this->request
            );
        }
        
        return $autorizado;
    }
    
    /**
     * Determine se o usuário pode realizar depósitos na conta.
     *
     * @param Usuario $usuario
     * @param Conta $conta
     * @return bool
     */
    public function depositar(Usuario $usuario, Conta $conta)
    {
        $autorizado = $usuario->id === $conta->usuario_id;
        
        if (!$autorizado) {
            $this->auditoriaService->registrarTentativaAcessoNaoAutorizado(
                'conta',
                $conta->public_id,
                $this->request
            );
        }
        
        return $autorizado;
    }
    
    /**
     * Determine se o usuário pode realizar saques na conta.
     *
     * @param Usuario $usuario
     * @param Conta $conta
     * @return bool
     */
    public function sacar(Usuario $usuario, Conta $conta)
    {
        $autorizado = $usuario->id === $conta->usuario_id;
        
        if (!$autorizado) {
            $this->auditoriaService->registrarTentativaAcessoNaoAutorizado(
                'conta',
                $conta->public_id,
                $this->request
            );
        }
        
        return $autorizado;
    }
    
    /**
     * Determine se o usuário pode ver o extrato da conta.
     *
     * @param Usuario $usuario
     * @param Conta $conta
     * @return bool
     */
    public function viewExtrato(Usuario $usuario, Conta $conta)
    {
        $autorizado = $usuario->id === $conta->usuario_id;
        
        if (!$autorizado) {
            $this->auditoriaService->registrarTentativaAcessoNaoAutorizado(
                'conta',
                $conta->public_id,
                $this->request
            );
        }
        
        return $autorizado;
    }
} 