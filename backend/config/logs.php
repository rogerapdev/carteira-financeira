<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configurações de Monitoramento de Exceções
    |--------------------------------------------------------------------------
    |
    | Aqui você pode configurar como o sistema deve tratar e categorizar
    | diferentes tipos de exceções para fins de monitoramento e alertas.
    |
    */
    
    'exceptions' => [
        // Categorias de exceções para agrupamento e monitoramento
        'categories' => [
            'auth' => [
                \Illuminate\Auth\AuthenticationException::class,
                \Illuminate\Auth\Access\AuthorizationException::class,
            ],
            'validation' => [
                \Illuminate\Validation\ValidationException::class,
                \InvalidArgumentException::class,
            ],
            'database' => [
                \Illuminate\Database\Eloquent\ModelNotFoundException::class,
                \Illuminate\Database\QueryException::class,
            ],
            'transacao' => [
                \App\Domain\Exceptions\SaldoInsuficienteException::class,
                \App\Domain\Exceptions\TransacaoException::class,
            ],
            'http' => [
                \Symfony\Component\HttpKernel\Exception\HttpException::class,
                \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
            ],
        ],
        
        // Níveis de severidade para tipos de exceções
        'severity_levels' => [
            'emergency' => [],
            'alert' => [],
            'critical' => [],
            'error' => [
                \Illuminate\Database\QueryException::class,
                \ErrorException::class,
                \TypeError::class,
            ],
            'warning' => [
                \App\Domain\Exceptions\SaldoInsuficienteException::class,
                \App\Domain\Exceptions\TransacaoException::class,
                \Illuminate\Auth\AuthenticationException::class,
                \Illuminate\Auth\Access\AuthorizationException::class,
            ],
            'notice' => [
                \Illuminate\Validation\ValidationException::class,
                \InvalidArgumentException::class,
                \Illuminate\Database\Eloquent\ModelNotFoundException::class,
                \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
            ],
            'info' => [],
            'debug' => [],
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Configurações de Alertas
    |--------------------------------------------------------------------------
    |
    | Aqui você pode configurar as regras para envio de alertas
    | baseados nos níveis de log e frequência de ocorrências.
    |
    */
    
    'alerts' => [
        'enabled' => env('EXCEPTION_ALERTS_ENABLED', true),
        
        // Canais de notificação suportados: 'email', 'slack'
        'channels' => ['email'],
        
        // Níveis de log que devem gerar alertas imediatos
        'immediate_levels' => ['emergency', 'alert', 'critical'],
        
        // Configuração para alertas de frequência (muitas ocorrências em curto período)
        'frequency' => [
            'enabled' => true,
            'error_threshold' => 10, // Número de erros
            'time_window' => 5,      // Em minutos
            'cooldown' => 30,        // Tempo mínimo entre alertas (minutos)
        ],
        
        // Destinatários dos alertas
        'recipients' => [
            'email' => [
                env('EXCEPTION_ALERT_EMAIL', 'admin@example.com'),
            ],
            'slack' => [
                'webhook' => env('EXCEPTION_ALERT_SLACK_WEBHOOK'),
                'channel' => env('EXCEPTION_ALERT_SLACK_CHANNEL', '#alerts'),
            ],
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Filtros para Rastreamento de Exceções
    |--------------------------------------------------------------------------
    |
    | Configure quais informações adicionais devem ser incluídas nos logs
    | para ajudar no diagnóstico de problemas.
    |
    */
    
    'tracing' => [
        // Incluir stack trace em ambientes específicos
        'include_trace' => [
            'local' => true,
            'development' => true,
            'staging' => true,
            'production' => false,
        ],
        
        // Dados de contexto a serem incluídos nos logs
        'context' => [
            'request' => [
                'url' => true,
                'method' => true,
                'ip' => true,
                'user_agent' => true,
                'referer' => true,
                'headers' => false, // Cuidado com dados sensíveis
                'input' => false,   // Cuidado com dados sensíveis
            ],
            'user' => [
                'id' => true,
                'email' => false,   // Cuidado com dados sensíveis
            ],
            'system' => [
                'memory_usage' => true,
                'execution_time' => true,
            ],
        ],
    ],
]; 