<?php

namespace App\Presentation\Http\Handlers;

use App\Application\Services\MonitoramentoExcecaoService;
use App\Domain\Exceptions\SaldoInsuficienteException;
use App\Domain\Exceptions\TransacaoException;
use App\Presentation\Http\Responses\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ApiExceptionHandler
{
    /**
     * Serviço de monitoramento de exceções
     *
     * @var MonitoramentoExcecaoService
     */
    private MonitoramentoExcecaoService $monitoramentoService;

    /**
     * Construtor
     *
     * @param MonitoramentoExcecaoService $monitoramentoService
     */
    public function __construct(MonitoramentoExcecaoService $monitoramentoService)
    {
        $this->monitoramentoService = $monitoramentoService;
    }

    /**
     * Formata exceções e retorna uma resposta JSON padronizada
     *
     * @param Throwable $exception
     * @param string $requestId Identificador único da requisição
     * @return JsonResponse
     */
    public function handle(Throwable $exception, string $requestId): JsonResponse
    {
        // Registra a exceção no serviço de monitoramento
        $this->monitoramentoService->registrarExcecao($exception, $requestId, [
            'uri' => request()->getUri(),
            'method' => request()->getMethod(),
            'user_id' => auth()->id() ?? 'guest',
            'ip' => request()->ip(),
        ]);
        
        if ($exception instanceof ValidationException) {
            return $this->handleValidationException($exception);
        }
        
        if ($exception instanceof AuthenticationException) {
            return $this->handleAuthenticationException($exception);
        }
        
        if ($exception instanceof AuthorizationException) {
            return $this->handleAuthorizationException($exception);
        }
        
        if ($exception instanceof ModelNotFoundException || $exception instanceof NotFoundHttpException) {
            return $this->handleNotFoundException($exception);
        }
        
        if ($exception instanceof SaldoInsuficienteException) {
            return $this->handleSaldoInsuficienteException($exception);
        }
        
        if ($exception instanceof TransacaoException) {
            return $this->handleTransacaoException($exception);
        }
        
        if ($exception instanceof InvalidArgumentException) {
            return $this->handleInvalidArgumentException($exception);
        }
        
        if ($exception instanceof HttpException) {
            return $this->handleHttpException($exception);
        }
        
        // Exceções genéricas ou não tratadas
        return $this->handleGenericException($exception);
    }
    
    /**
     * Trata exceções de validação
     */
    private function handleValidationException(ValidationException $exception): JsonResponse
    {
        return ApiResponse::validationError($exception->errors());
    }
    
    /**
     * Trata exceções de autenticação
     */
    private function handleAuthenticationException(AuthenticationException $exception): JsonResponse
    {
        return ApiResponse::unauthorized();
    }
    
    /**
     * Trata exceções de autorização
     */
    private function handleAuthorizationException(AuthorizationException $exception): JsonResponse
    {
        return ApiResponse::forbidden();
    }
    
    /**
     * Trata exceções de recurso não encontrado
     */
    private function handleNotFoundException(Throwable $exception): JsonResponse
    {
        $message = 'O recurso solicitado não foi encontrado.';
        
        if ($exception instanceof ModelNotFoundException) {
            $modelNames = array_map(function ($model) {
                return class_basename($model);
            }, $exception->getModel());
            
            $modelName = is_array($modelNames) ? implode(', ', $modelNames) : $modelNames;
            $message = "O recurso do tipo '{$modelName}' não foi encontrado.";
        }
        
        return ApiResponse::notFound($message);
    }
    
    /**
     * Trata exceções de saldo insuficiente
     */
    private function handleSaldoInsuficienteException(SaldoInsuficienteException $exception): JsonResponse
    {
        return ApiResponse::saldoInsuficiente($exception->getMessage());
    }
    
    /**
     * Trata exceções relacionadas a transações
     */
    private function handleTransacaoException(TransacaoException $exception): JsonResponse
    {
        return ApiResponse::error($exception->getMessage(), 'transacao_error', null, 422);
    }
    
    /**
     * Trata exceções de argumento inválido
     */
    private function handleInvalidArgumentException(InvalidArgumentException $exception): JsonResponse
    {
        return ApiResponse::error($exception->getMessage(), 'invalid_argument', null, 422);
    }
    
    /**
     * Trata exceções HTTP
     */
    private function handleHttpException(HttpException $exception): JsonResponse
    {
        return ApiResponse::error(
            $exception->getMessage() ?: 'Ocorreu um erro de HTTP.',
            'http_error',
            null,
            $exception->getStatusCode()
        );
    }
    
    /**
     * Trata exceções genéricas ou não tratadas
     */
    private function handleGenericException(Throwable $exception): JsonResponse
    {
        $message = 'Ocorreu um erro interno no servidor.';
        $details = null;
        
        // Em ambiente não-produtivo, incluímos mais detalhes
        if (config('app.env') !== 'production') {
            $details = [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ];
        }
        
        return ApiResponse::error($message, 'server_error', $details, 500);
    }
} 