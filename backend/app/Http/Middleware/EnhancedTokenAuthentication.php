<?php

namespace App\Http\Middleware;

use App\Presentation\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnhancedTokenAuthentication
{
    /**
     * Realiza validação avançada de tokens de API.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verifica se o usuário está autenticado via sanctum
        if (!Auth::guard('sanctum')->check()) {
            throw new AuthenticationException('Não autenticado.');
        }
        
        $user = Auth::guard('sanctum')->user();
        $token = $user->currentAccessToken();
        
        // Verifica se o token está expirado
        if ($this->isTokenExpired($token)) {
            return $this->handleExpiredToken($request, $user);
        }
        
        // Verifica configurações avançadas de segurança
        if (config('security.api.require_signature', false)) {
            $this->validateRequestSignature($request);
        }
        
        // Verifica IP de origem (segurança adicional)
        $this->validateTokenIpAddress($request, $token);
        
        // dd($token);
        // Atualiza o último uso do token
        // $token->forceFill(['last_used_at' => now()])->save();
        
        return $next($request);
    }
    
    /**
     * Verifica se o token expirou
     *
     * @param  mixed  $token
     * @return bool
     */
    protected function isTokenExpired($token): bool
    {
        if (!property_exists($token, 'expires_at') || !$token->expires_at) {
            return false;
        }
        
        return Carbon::parse($token->expires_at)->isPast();
    }
    
    /**
     * Manipula token expirado, realizando refresh se habilitado
     *
     * @param  Request  $request
     * @param  mixed  $user
     * @return Response
     */
    protected function handleExpiredToken(Request $request, $user): Response
    {
        // Se refresh automático estiver habilitado
        if (config('security.tokens.enable_refresh', false)) {
            // Verifica se existe um refresh token válido
            $refreshToken = $request->cookie(config('security.tokens.refresh_cookie'));
            
            if ($refreshToken && $this->validateRefreshToken($refreshToken, $user)) {
                // Revoga o token expirado
                $user->currentAccessToken()->delete();
                
                // Cria um novo token
                $newToken = $user->createToken('api_token', ['*'], now()->addMinutes(
                    config('security.tokens.expiration')
                ));
                
                // Cria um novo refresh token (Se necessário)
                // Na implementação real, você criaria um refresh token persistente
                
                return ApiResponse::error(
                    'Token expirado e renovado automaticamente',
                    'token_refreshed',
                    ['token' => $newToken->plainTextToken],
                    401
                );
            }
        }
        
        return ApiResponse::unauthorized('Token de acesso expirado');
    }
    
    /**
     * Valida o refresh token (implementação básica)
     *
     * @param  string  $refreshToken
     * @param  mixed  $user
     * @return bool
     */
    protected function validateRefreshToken(string $refreshToken, $user): bool
    {
        // Em uma implementação real, você verificaria o token em um banco de dados
        // e validaria sua expiração, revogação, etc.
        
        // Este é apenas um placeholder
        return true;
    }
    
    /**
     * Valida a assinatura da requisição
     *
     * @param  Request  $request
     * @return void
     * @throws AuthenticationException
     */
    protected function validateRequestSignature(Request $request): void
    {
        // Obtém os cabeçalhos de assinatura
        $timestamp = $request->header('X-Request-Timestamp');
        $signature = $request->header('X-Request-Signature');
        
        // Verifica se os cabeçalhos estão presentes
        if (!$timestamp || !$signature) {
            throw new AuthenticationException('Cabeçalhos de assinatura ausentes');
        }
        
        // Verifica se o timestamp está dentro da tolerância
        $tolerance = config('security.api.timestamp_tolerance', 60);
        if (abs(time() - (int)$timestamp) > $tolerance) {
            throw new AuthenticationException('Timestamp da requisição expirado');
        }
        
        // Em uma implementação real, você validaria a assinatura aqui
        // Usando HMAC ou outro algoritmo de assinatura
    }
    
    /**
     * Valida o endereço IP do token
     *
     * @param  Request  $request
     * @param  mixed  $token
     * @return void
     * @throws AuthenticationException
     */
    protected function validateTokenIpAddress(Request $request, $token): void
    {
        // Esta é uma verificação opcional que pode ser implementada
        // Se o token armazenar o IP de criação, você pode verificar
        // se o IP atual corresponde ao original
        
        // Se implementado, você poderia adicionar isso à migration do token:
        // $table->string('ip_address')->nullable();
        
        // E então fazer a validação:
        // if ($token->ip_address && $token->ip_address !== $request->ip()) {
        //     throw new AuthenticationException('Token usado de um IP não autorizado');
        // }
    }
}