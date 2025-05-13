<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Limite mais restritivo para operações financeiras e sensíveis
        RateLimiter::for('security', function (Request $request) {
            return Limit::perMinute(
                config('security.rate_limits.api.critical', 10)
            )->by($request->user()?->id ?: $request->ip());
        });

        // Limite específico para tentativas de login
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinutes(
                config('security.rate_limits.auth.decay_minutes', 10),
                config('security.rate_limits.auth.max_attempts', 5)
            )->by($request->ip());
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            // Carrega as rotas do Swagger
            // Route::group([], base_path('routes/swagger.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
