<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter as FacadesRateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimiting
{
    /**
     * O serviço RateLimiter
     *
     * @var RateLimiter
     */
    protected $limiter;

    /**
     * Cria uma nova instância do middleware.
     *
     * @param  RateLimiter  $limiter
     * @return void
     */
    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Processa a requisição.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @param  string  $limiterName
     * @return Response
     */
    public function handle(Request $request, Closure $next, string $limiterName = null): Response
    {
        // Define o limitador baseado no parâmetro ou usa o padrão
        $limiterName = $limiterName ?: 'api';
        
        // Determina a chave de limitação baseada no usuário autenticado ou IP
        $key = $this->resolveRequestSignature($request, $limiterName);
        
        // Obtém o limite baseado no tipo de rota
        $maxAttempts = $this->getMaxAttemptsForRoute($request, $limiterName);
        
        // Verifica se excedeu o limite
        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return $this->buildTooManyAttemptsResponse($key, $maxAttempts);
        }

        // Incrementa o contador de tentativas
        $this->limiter->hit($key, $this->getDecayMinutes($limiterName));

        $response = $next($request);

        // Adiciona headers de rate limit à resposta
        return $this->addHeaders(
            $response, $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts)
        );
    }

    /**
     * Resolve a assinatura para limitar a requisição.
     *
     * @param  Request  $request
     * @param  string  $limiterName
     * @return string
     */
    protected function resolveRequestSignature(Request $request, string $limiterName): string
    {
        // Prefixo para diferenciar limites diferentes
        $prefix = "rate_limit:{$limiterName}:";

        // Se o usuário estiver autenticado, usa o ID para limitações personalizadas
        if ($request->user()) {
            return $prefix . 'user:' . $request->user()->id . '|' . $request->ip();
        }
        
        // Se não estiver autenticado, usa apenas o IP
        return $prefix . 'ip:' . $request->ip();
    }

    /**
     * Obtém o número máximo de tentativas com base na rota.
     *
     * @param  Request  $request
     * @param  string  $limiterName
     * @return int
     */
    protected function getMaxAttemptsForRoute(Request $request, string $limiterName): int
    {
        // Rotas críticas (autenticação, transações financeiras) têm limites mais rigorosos
        if (Str::contains($request->path(), ['login', 'cadastrar', 'depositar', 'sacar', 'transferir', 'estornar'])) {
            return config("security.rate_limits.{$limiterName}.critical", 10);
        }
        
        // Operações de leitura têm limites mais altos
        if ($request->isMethod('GET')) {
            return config("security.rate_limits.{$limiterName}.read", 60);
        }
        
        // Outras operações usam o limite padrão
        return config("security.rate_limits.{$limiterName}.default", 30);
    }

    /**
     * Obtém o tempo de decaimento em minutos.
     *
     * @param  string  $limiterName
     * @return int
     */
    protected function getDecayMinutes(string $limiterName): int
    {
        return config("security.rate_limits.{$limiterName}.decay_minutes", 1);
    }

    /**
     * Cria a resposta para quando o limite foi excedido.
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @return Response
     */
    protected function buildTooManyAttemptsResponse(string $key, int $maxAttempts): Response
    {
        $retryAfter = $this->limiter->availableIn($key);

        return response()->json([
            'success' => false,
            'error' => 'rate_limit_exceeded',
            'message' => 'Limite de requisições excedido. Tente novamente mais tarde.',
            'details' => [
                'retry_after' => $retryAfter,
                'seconds' => $retryAfter,
            ],
            'request_id' => request()->header('X-Request-ID', (string) Str::uuid()),
        ], 429)->headers->add([
            'Retry-After' => $retryAfter,
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => 0,
            'X-RateLimit-Reset' => now()->addSeconds($retryAfter)->getTimestamp(),
        ]);
    }

    /**
     * Calcula o número de tentativas restantes.
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @return int
     */
    protected function calculateRemainingAttempts(string $key, int $maxAttempts): int
    {
        return $maxAttempts - $this->limiter->attempts($key) + 1;
    }

    /**
     * Adiciona cabeçalhos de rate limit à resposta.
     *
     * @param  Response  $response
     * @param  int  $maxAttempts
     * @param  int  $remainingAttempts
     * @return Response
     */
    protected function addHeaders(Response $response, int $maxAttempts, int $remainingAttempts): Response
    {
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
        ]);

        return $response;
    }
} 