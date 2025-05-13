<?php

namespace App\Exceptions;

use App\Presentation\Http\Handlers\ApiExceptionHandler;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
    
    /**
     * Render an exception into an HTTP response.
     *
     * @param  Request  $request
     * @param  Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function render($request, Throwable $e)
    {
        // Para requisições AJAX ou que aceitem JSON, usamos o nosso ApiExceptionHandler
        if ($request->expectsJson() || $request->is('api/*')) {
            $requestId = $request->header('X-Request-ID', (string) Str::uuid());
            
            return app(ApiExceptionHandler::class)->handle($e, $requestId);
        }
        
        return parent::render($request, $e);
    }
}
