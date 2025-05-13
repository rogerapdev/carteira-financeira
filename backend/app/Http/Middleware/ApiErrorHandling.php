<?php

namespace App\Http\Middleware;

use App\Presentation\Http\Handlers\ApiExceptionHandler;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class ApiErrorHandling
{
    /**
     * Manipula uma requisição.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Gera um ID único para a requisição se ainda não existir
        if (!$request->hasHeader('X-Request-ID')) {
            $requestId = (string) Str::uuid();
            $request->headers->set('X-Request-ID', $requestId);
        } else {
            $requestId = $request->header('X-Request-ID');
        }

        try {
            // Processa a requisição normalmente
            $response = $next($request);
            
            // Adiciona o ID da requisição ao cabeçalho da resposta
            if (!$response->headers->has('X-Request-ID')) {
                $response->header('X-Request-ID', $requestId);
            }
            
            return $response;
        } catch (Throwable $exception) {
            // Se ocorrer uma exceção, usa o handler para formatá-la
            $handler = app(ApiExceptionHandler::class);
            return $handler->handle($exception, $requestId);
        }
    }
} 