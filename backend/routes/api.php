<?php

use App\Presentation\Http\Controllers\API\ContaController;
use App\Presentation\Http\Controllers\API\AutenticacaoController;
use App\Presentation\Http\Controllers\API\TransacaoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas da API
|--------------------------------------------------------------------------
|
| Aqui é onde você pode registrar as rotas da API para sua aplicação.
| Estas rotas são carregadas pelo RouteServiceProvider e todas elas
| serão atribuídas ao grupo middleware "api". Crie algo incrível!
|
*/

// Rotas de autenticação (públicas) - Com proteção contra força bruta
Route::middleware(['throttle:auth'])->group(function () {
    Route::post('/cadastrar', [AutenticacaoController::class, 'cadastrar']);
    Route::post('/login', [AutenticacaoController::class, 'login']);
});

// Rotas autenticadas com validação de token avançada
Route::middleware(['auth:sanctum', 'auth.token'])->group(function () {
    // Autenticação
    Route::get('/perfil', [AutenticacaoController::class, 'perfil']);
    Route::post('/logout', [AutenticacaoController::class, 'logout']);
    
    // Rotas sensíveis (transações financeiras) com verificação de propriedade
    Route::middleware(['throttle:security'])->group(function () {
        // Conta - Operações financeiras
        Route::post('/contas/{publicId}/depositar', [ContaController::class, 'depositar']);
            
        Route::post('/contas/{publicId}/sacar', [ContaController::class, 'sacar']);
        
        // Transações - Operações financeiras
        Route::post('/transacoes/transferir', [TransacaoController::class, 'transferir']);
            
        Route::post('/transacoes/depositar', [TransacaoController::class, 'depositar']);
        
        Route::post('/transacoes/{publicId}/estornar', [TransacaoController::class, 'estornar']);
    });
    
    // Rotas de leitura (menos sensíveis) com verificação de propriedade
    Route::get('/contas/{publicId}', [ContaController::class, 'mostrar']);
        
    Route::get('/contas/{publicIdConta}/transacoes', [TransacaoController::class, 'listarPorConta']);
        
    Route::get('/transacoes/{publicId}', [TransacaoController::class, 'mostrar']);
});

// Rotas para notificações
Route::middleware(['auth:sanctum'])->prefix('notificacoes')->group(function () {
    Route::get('/', [App\Presentation\Http\Controllers\API\NotificacaoController::class, 'listar']);
    Route::get('/nao-lidas', [App\Presentation\Http\Controllers\API\NotificacaoController::class, 'listarNaoLidas']);
    Route::get('/contador', [App\Presentation\Http\Controllers\API\NotificacaoController::class, 'contarNaoLidas']);
    Route::put('/{id}/lida', [App\Presentation\Http\Controllers\API\NotificacaoController::class, 'marcarComoLida'])->where('id', '[0-9]+');
    Route::put('/marcar-todas-lidas', [App\Presentation\Http\Controllers\API\NotificacaoController::class, 'marcarTodasComoLidas']);
});
