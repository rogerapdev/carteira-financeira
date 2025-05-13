<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Adiciona cabeçalhos de segurança HTTP às respostas.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Adiciona cabeçalhos de segurança da configuração
        $headers = config('security.api.security_headers', []);
        
        foreach ($headers as $header => $value) {
            $response->headers->set($header, $value);
        }
        
        // Definir Content-Security-Policy específica para documentação Swagger
        if ($request->is('api/documentation*') || $request->is('docs/*')) {
            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:; frame-ancestors 'none'"
            );
        }
        // Definir Content-Security-Policy para API
        if ($request->is('api/*') && !$response->headers->has('Content-Security-Policy')) {
            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'none'; frame-ancestors 'none'"
            );
        }
        
        return $response;
    }
} 