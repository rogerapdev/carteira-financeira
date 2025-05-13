<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Domain\Entities\Conta;
use App\Domain\Entities\Transacao;
use App\Policies\ContaPolicy;
use App\Policies\TransacaoPolicy;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Conta::class => ContaPolicy::class,
        Transacao::class => TransacaoPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Define gates adicionais para operações específicas
        Gate::define('acessar-conta', function ($usuario, $publicId) {
            $conta = app(\App\Domain\Interfaces\ContaRepositoryInterface::class)
                ->buscarPorPublicId($publicId);
                
            if (!$conta) {
                return false;
            }
            
            return app(ContaPolicy::class)->view($usuario, $conta);
        });
        
        Gate::define('acessar-transacao', function ($usuario, $publicId) {
            $transacao = app(\App\Domain\Interfaces\TransacaoRepositoryInterface::class)
                ->buscarPorPublicId($publicId);
                
            if (!$transacao) {
                return false;
            }
            
            return app(TransacaoPolicy::class)->view($usuario, $transacao);
        });
        
        Gate::define('transferir-da-conta', function ($usuario, $contaOrigemId) {
            return app(TransacaoPolicy::class)->transferir($usuario, $contaOrigemId);
        });
        
        Gate::define('estornar-transacao', function ($usuario, $publicId) {
            $transacao = app(\App\Domain\Interfaces\TransacaoRepositoryInterface::class)
                ->buscarPorPublicId($publicId);
                
            if (!$transacao) {
                return false;
            }
            
            return app(TransacaoPolicy::class)->estornar($usuario, $transacao);
        });
    }
}
