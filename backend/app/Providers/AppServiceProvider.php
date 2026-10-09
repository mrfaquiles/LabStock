<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Limite de requisições da API: 120 por minuto por usuário (ou por IP, sem login).
        // Evita que um script sobrecarregue o sistema ou tente adivinhar dados em massa.
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        // Regra de senha dos usuários: mínimo de 8 caracteres, com letras e números
        Password::defaults(fn () => Password::min(8)->letters()->numbers());
    }
}
