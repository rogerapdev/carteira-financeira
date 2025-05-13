<?php

namespace App\Application\Services;

use Illuminate\Support\Facades\Log;
use Throwable;

class MonitoramentoExcecaoService
{
    /**
     * Registra uma exceção e a categoriza por tipo
     *
     * @param Throwable $exception Exceção capturada
     * @param string $requestId Identificador da requisição
     * @param array $contexto Informações adicionais para o log
     * @return void
     */
    public function registrarExcecao(Throwable $exception, string $requestId, array $contexto = []): void
    {
        $dadosBasicos = [
            'request_id' => $requestId,
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'code' => $exception->getCode(),
            'timestamp' => now()->toIso8601String(),
        ];
        
        // Adiciona dados de contexto baseados na configuração
        $contextoFiltrado = $this->filtrarContexto($contexto);
        
        // Mescla com o contexto fornecido
        $dadosCompletos = array_merge($dadosBasicos, $contextoFiltrado);
        
        // Determina a severidade baseada no tipo da exceção e código
        $severidade = $this->determinarSeveridade($exception);
        
        // Categoriza a exceção
        $categoria = $this->categorizarExcecao($exception);
        $dadosCompletos['categoria'] = $categoria;
        
        // Adiciona o stack trace se configurado para o ambiente atual
        if ($this->deveIncluirStackTrace()) {
            $dadosCompletos['trace'] = $exception->getTraceAsString();
        }
        
        // Registra o log com o nível de severidade adequado
        $this->registrarLog($severidade, $exception->getMessage(), $dadosCompletos);
        
        // Verifica se deve enviar alertas
        if (config('logs.alerts.enabled', false)) {
            $this->verificarAlerta($exception, $severidade, $categoria, $dadosCompletos);
        }
        
        // Registra métricas para monitoramento (se estiver no ambiente de produção)
        if (config('app.env') === 'production') {
            $this->registrarMetrica($categoria, $severidade);
        }
    }
    
    /**
     * Determina a severidade da exceção para fins de log
     *
     * @param Throwable $exception
     * @return string
     */
    private function determinarSeveridade(Throwable $exception): string
    {
        $exceptionClass = get_class($exception);
        
        // Obtém mapeamento de níveis de severidade da configuração
        $levels = config('logs.exceptions.severity_levels', []);
        
        // Procura a exceção em cada nível configurado
        foreach ($levels as $level => $exceptions) {
            foreach ($exceptions as $exceptionType) {
                if ($exception instanceof $exceptionType) {
                    return $level;
                }
            }
        }
        
        // Exceções HTTP com código 4xx são notice
        if ($exception instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
            $statusCode = $exception->getStatusCode();
            if ($statusCode >= 400 && $statusCode < 500) {
                return 'notice';
            }
        }
        
        // Outras exceções são consideradas erro
        return 'error';
    }
    
    /**
     * Categoriza a exceção para fins de monitoramento e agrupamento
     *
     * @param Throwable $exception
     * @return string
     */
    private function categorizarExcecao(Throwable $exception): string
    {
        // Obtém categorias da configuração
        $categories = config('logs.exceptions.categories', []);
        
        // Procura a exceção em cada categoria configurada
        foreach ($categories as $categoryName => $exceptions) {
            foreach ($exceptions as $exceptionType) {
                if ($exception instanceof $exceptionType) {
                    return $categoryName;
                }
            }
        }
        
        // Se não encontrou categoria específica, usa a genérica
        return 'sistema';
    }
    
    /**
     * Verifica se deve incluir stack trace baseado na configuração do ambiente
     *
     * @return bool
     */
    private function deveIncluirStackTrace(): bool
    {
        $env = config('app.env');
        return config("logs.tracing.include_trace.{$env}", false);
    }
    
    /**
     * Filtra o contexto fornecido baseado nas configurações
     *
     * @param array $contexto
     * @return array
     */
    private function filtrarContexto(array $contexto): array
    {
        $contextoFiltrado = [];
        $configContext = config('logs.tracing.context', []);
        
        // Filtra dados de requisição
        if (isset($contexto['uri']) && isset($configContext['request']['url']) && $configContext['request']['url']) {
            $contextoFiltrado['url'] = $contexto['uri'];
        }
        
        if (isset($contexto['method']) && isset($configContext['request']['method']) && $configContext['request']['method']) {
            $contextoFiltrado['method'] = $contexto['method'];
        }
        
        if (isset($contexto['ip']) && isset($configContext['request']['ip']) && $configContext['request']['ip']) {
            $contextoFiltrado['ip'] = $contexto['ip'];
        }
        
        if (isset($contexto['user_id']) && isset($configContext['user']['id']) && $configContext['user']['id']) {
            $contextoFiltrado['user_id'] = $contexto['user_id'];
        }
        
        // Adiciona informações de sistema se configurado
        if (isset($configContext['system']['memory_usage']) && $configContext['system']['memory_usage']) {
            $contextoFiltrado['memory_usage'] = memory_get_usage(true);
        }
        
        return $contextoFiltrado;
    }
    
    /**
     * Verifica se deve enviar um alerta para esta exceção
     *
     * @param Throwable $exception
     * @param string $severidade
     * @param string $categoria
     * @param array $contexto
     * @return void
     */
    private function verificarAlerta(Throwable $exception, string $severidade, string $categoria, array $contexto): void
    {
        // Verifica se é um nível que deve gerar alerta imediato
        $immediateLevels = config('logs.alerts.immediate_levels', []);
        
        if (in_array($severidade, $immediateLevels)) {
            // Placeholder: Em um sistema real, enviaria o alerta via e-mail, slack, etc.
            Log::alert("ALERTA: Exceção de alta severidade: " . get_class($exception), [
                'severidade' => $severidade,
                'categoria' => $categoria,
                'mensagem' => $exception->getMessage(),
                'request_id' => $contexto['request_id'] ?? 'unknown',
            ]);
        }
    }
    
    /**
     * Registra o log com o nível de severidade adequado
     *
     * @param string $severidade
     * @param string $mensagem
     * @param array $contexto
     * @return void
     */
    private function registrarLog(string $severidade, string $mensagem, array $contexto): void
    {
        switch ($severidade) {
            case 'debug':
                Log::debug($mensagem, $contexto);
                break;
                
            case 'info':
                Log::info($mensagem, $contexto);
                break;
                
            case 'notice':
                Log::notice($mensagem, $contexto);
                break;
                
            case 'warning':
                Log::warning($mensagem, $contexto);
                break;
                
            case 'error':
                Log::error($mensagem, $contexto);
                break;
                
            case 'critical':
                Log::critical($mensagem, $contexto);
                break;
                
            case 'alert':
                Log::alert($mensagem, $contexto);
                break;
                
            case 'emergency':
                Log::emergency($mensagem, $contexto);
                break;
                
            default:
                Log::error($mensagem, $contexto);
        }
    }
    
    /**
     * Registra métrica para monitoramento
     * 
     * Em produção, isso poderia integrar com um sistema de monitoramento como Prometheus
     *
     * @param string $categoria
     * @param string $severidade
     * @return void
     */
    private function registrarMetrica(string $categoria, string $severidade): void
    {
        // Apenas um placeholder - em produção, implementaria integração com Prometheus, New Relic, etc.
        Log::debug("Exceção registrada para monitoramento", [
            'categoria' => $categoria,
            'severidade' => $severidade,
            'timestamp' => time(),
        ]);
    }
} 