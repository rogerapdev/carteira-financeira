<?php

namespace App\Providers;

use App\Application\Interfaces\ContaServiceInterface;
use App\Application\Interfaces\TransacaoServiceInterface;
use App\Application\Interfaces\UsuarioServiceInterface;
use App\Application\Services\AuditoriaService;
use App\Application\Services\ContaService;
use App\Application\Services\MonitoramentoExcecaoService;
use App\Application\Services\TransacaoService;
use App\Application\Services\UsuarioService;
use App\Domain\Interfaces\AuditoriaRepositoryInterface;
use App\Domain\Interfaces\ContaRepositoryInterface;
use App\Domain\Interfaces\TransacaoRepositoryInterface;
use App\Domain\Interfaces\UsuarioRepositoryInterface;
use App\Infrastructure\Persistence\EloquentContaRepository;
use App\Infrastructure\Persistence\EloquentTransacaoRepository;
use App\Infrastructure\Persistence\EloquentUsuarioRepository;
use App\Infrastructure\Repositories\AuditoriaRepository;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use League\Fractal\Manager;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Repositories
        $this->app->bind(UsuarioRepositoryInterface::class, EloquentUsuarioRepository::class);
        $this->app->bind(ContaRepositoryInterface::class, EloquentContaRepository::class);
        $this->app->bind(TransacaoRepositoryInterface::class, EloquentTransacaoRepository::class);
        $this->app->bind(AuditoriaRepositoryInterface::class, AuditoriaRepository::class);

        // Services
        $this->app->bind(UsuarioServiceInterface::class, UsuarioService::class);
        $this->app->bind(ContaServiceInterface::class, ContaService::class);
        $this->app->bind(TransacaoServiceInterface::class, TransacaoService::class);
        
        // Serviço de monitoramento de exceções
        $this->app->singleton(MonitoramentoExcecaoService::class);
        
        // Serviço de monitoramento de jobs
        $this->app->singleton(MonitoramentoJobsService::class);
        
        // Serviço de auditoria
        $this->app->singleton(AuditoriaService::class);

        // Fractal Manager
        $this->app->singleton(Manager::class, function () {
            return new Manager();
        });

        // Registrando interfaces e implementações para notificações
        $this->app->bind(
            \App\Domain\Interfaces\NotificacaoRepositoryInterface::class,
            \App\Infrastructure\Repositories\NotificacaoRepository::class
        );

        $this->app->bind(
            \App\Application\Interfaces\NotificacaoServiceInterface::class,
            \App\Application\Services\NotificacaoService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Verifica e copia os arquivos de configuração manualmente
        $this->copyConfigFileIfNotExists('logs.php');
        $this->copyConfigFileIfNotExists('security.php');
    }
    
    /**
     * Copia um arquivo de configuração para o diretório config se não existir
     *
     * @param string $configFile
     * @return void
     */
    protected function copyConfigFileIfNotExists(string $configFile): void
    {
        $configPath = config_path($configFile);
        
        if (!File::exists($configPath)) {
            $sourceFile = base_path("config/{$configFile}");
            
            // Se não existir no diretório base de config, cria-o
            if (!File::exists($sourceFile)) {
                // Cria o diretório se não existir
                if (!File::exists(base_path('config'))) {
                    File::makeDirectory(base_path('config'), 0755, true);
                }
                
                // Copia o arquivo do diretório app para config
                File::copy(app_path("../config/{$configFile}"), $sourceFile);
            }
            
            // Copia para o diretório de configuração do Laravel
            File::copy($sourceFile, $configPath);
        }
    }
}
