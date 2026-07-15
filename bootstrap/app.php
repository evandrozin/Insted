<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Atrás do proxy da Vercel: confia no X-Forwarded-* (inclui Proto=https),
        // para o Laravel gerar URLs/forms com https e detectar conexão segura.
        $middleware->trustProxies(at: '*');

        // Heartbeat de atividade dos acessos (sessões ativas / conexão perdida).
        $middleware->web(append: [
            \App\Http\Middleware\RegistrarAtividade::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
