<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configurações para limitação de taxa de requisições (throttling)
    | para proteger contra ataques de force brute e DDoS.
    |
    */
    'rate_limits' => [
        'api' => [
            // Operações críticas (login, transações financeiras)
            'critical' => env('RATE_LIMIT_CRITICAL', 10),
            
            // Operações de escrita (PUT, POST, DELETE)
            'default' => env('RATE_LIMIT_DEFAULT', 30),
            
            // Operações de leitura (GET)
            'read' => env('RATE_LIMIT_READ', 60),
            
            // Tempo de decaimento em minutos
            'decay_minutes' => env('RATE_LIMIT_DECAY_MINUTES', 1),
        ],
        
        // Limite específico para tentativas de login
        'auth' => [
            'max_attempts' => env('LOGIN_MAX_ATTEMPTS', 5),
            'decay_minutes' => env('LOGIN_DECAY_MINUTES', 10),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | CORS - Cross-Origin Resource Sharing
    |--------------------------------------------------------------------------
    |
    | Configurações de CORS para permitir que seu frontend
    | acesse a API com segurança.
    |
    */
    'cors' => [
        'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'localhost,localhost:8080,localhost:3000')),
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'X-Requested-With', 'Authorization', 'X-Request-ID'],
        'exposed_headers' => ['X-RateLimit-Limit', 'X-RateLimit-Remaining', 'X-Request-ID'],
        'max_age' => 86400, // 24 horas
        'supports_credentials' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | API Tokens (Sanctum)
    |--------------------------------------------------------------------------
    |
    | Configurações para autenticação via token usando Laravel Sanctum.
    |
    */
    'tokens' => [
        // Tempo de expiração do token em minutos
        'expiration' => env('TOKEN_EXPIRATION', 60 * 24), // 24 horas por padrão
        
        // Nome do prefixo do token para integração com scanner de segredos
        'prefix' => env('TOKEN_PREFIX', 'cf_'),
        
        // Nome para cookies de refresh token
        'refresh_cookie' => 'refresh_token',
        
        // Habilitar refresh token automático
        'enable_refresh' => env('TOKEN_ENABLE_REFRESH', true),
        
        // Tempo de vida do refresh token em dias
        'refresh_ttl' => env('TOKEN_REFRESH_TTL', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Segurança da API
    |--------------------------------------------------------------------------
    |
    | Configurações gerais para segurança da API.
    |
    */
    'api' => [
        // Habilitar validação de assinatura para requisições críticas
        'require_signature' => env('API_REQUIRE_SIGNATURE', false),
        
        // Tempo máximo em segundos para aceitar uma requisição após o timestamp
        'timestamp_tolerance' => env('API_TIMESTAMP_TOLERANCE', 60),
        
        // Implementar detecção de API scraping
        'detect_scraping' => env('API_DETECT_SCRAPING', true),
        
        // Bloquear temporariamente IPs suspeitos
        'block_suspicious_ips' => env('API_BLOCK_SUSPICIOUS', true),
        
        // Cabeçalhos de segurança
        'security_headers' => [
            'X-Content-Type-Options' => 'nosniff',
            'X-XSS-Protection' => '1; mode=block',
            'X-Frame-Options' => 'DENY',
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
            'Referrer-Policy' => 'no-referrer-when-downgrade',
        ],
    ],
]; 