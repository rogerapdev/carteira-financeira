<?php

namespace App\Presentation\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ApiResponse
{
    /**
     * Cria uma resposta de sucesso
     *
     * @param mixed $data Os dados da resposta
     * @param string $message A mensagem de sucesso
     * @param int $statusCode O código HTTP (padrão: 200)
     * @return JsonResponse
     */
    public static function success($data = null, string $message = 'Operação realizada com sucesso', int $statusCode = 200): JsonResponse
    {
        $requestId = request()->header('X-Request-ID', (string) Str::uuid());
        
        $response = [
            'success' => true,
            'message' => $message,
            'request_id' => $requestId,
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        return response()->json($response, $statusCode);
    }
    
    /**
     * Cria uma resposta de recurso criado
     *
     * @param mixed $data Os dados do recurso criado
     * @param string $message A mensagem de sucesso
     * @return JsonResponse
     */
    public static function created($data = null, string $message = 'Recurso criado com sucesso'): JsonResponse
    {
        return self::success($data, $message, 201);
    }
    
    /**
     * Cria uma resposta para uma requisição aceita (processamento assíncrono)
     *
     * @param array $data Informações adicionais sobre o processamento
     * @param string $message A mensagem de aceite
     * @return JsonResponse
     */
    public static function accepted($data = null, string $message = 'Requisição aceita para processamento'): JsonResponse
    {
        return self::success($data, $message, 202);
    }
    
    /**
     * Cria uma resposta para operação sem conteúdo
     *
     * @param string $message A mensagem de sucesso
     * @return JsonResponse
     */
    public static function noContent(string $message = 'Operação realizada com sucesso'): JsonResponse
    {
        return self::success(null, $message, 204);
    }
    
    /**
     * Cria uma resposta de erro
     *
     * @param string $message A mensagem de erro
     * @param string $errorCode O código de erro para identificação
     * @param mixed $details Detalhes adicionais do erro
     * @param int $statusCode O código HTTP (padrão: 400)
     * @return JsonResponse
     */
    public static function error(string $message, string $errorCode = 'bad_request', $details = null, int $statusCode = 400): JsonResponse
    {
        $requestId = request()->header('X-Request-ID', (string) Str::uuid());
        
        $response = [
            'success' => false,
            'error' => $errorCode,
            'message' => $message,
            'request_id' => $requestId,
        ];
        
        if ($details !== null) {
            $response['details'] = $details;
        }
        
        return response()->json($response, $statusCode);
    }
    
    /**
     * Cria uma resposta de erro de validação
     *
     * @param array $errors Os erros de validação
     * @param string $message A mensagem de erro
     * @return JsonResponse
     */
    public static function validationError(array $errors, string $message = 'Os dados fornecidos são inválidos'): JsonResponse
    {
        return self::error($message, 'validation_error', $errors, 422);
    }
    
    /**
     * Cria uma resposta de erro de autenticação
     *
     * @param string $message A mensagem de erro
     * @return JsonResponse
     */
    public static function unauthorized(string $message = 'Não autenticado. Faça login para continuar'): JsonResponse
    {
        return self::error($message, 'authentication_error', null, 401);
    }
    
    /**
     * Cria uma resposta de erro de autorização
     *
     * @param string $message A mensagem de erro
     * @return JsonResponse
     */
    public static function forbidden(string $message = 'Você não possui permissão para esta operação'): JsonResponse
    {
        return self::error($message, 'authorization_error', null, 403);
    }
    
    /**
     * Cria uma resposta de recurso não encontrado
     *
     * @param string $message A mensagem de erro
     * @return JsonResponse
     */
    public static function notFound(string $message = 'O recurso solicitado não foi encontrado'): JsonResponse
    {
        return self::error($message, 'not_found', null, 404);
    }
    
    /**
     * Cria uma resposta para erro de saldo insuficiente
     *
     * @param string $message A mensagem de erro
     * @param array|null $details Detalhes sobre o saldo
     * @return JsonResponse
     */
    public static function saldoInsuficiente(string $message = 'Saldo insuficiente para realizar esta operação', ?array $details = null): JsonResponse
    {
        return self::error($message, 'saldo_insuficiente', $details, 422);
    }
} 