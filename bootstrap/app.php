<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Перед API один шлюз dockhost, он сам перезаписывает X-Forwarded-For адресом клиента. Без доверия ему все клиенты выглядели бы как один IP и делили бы один лимит частоты.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'static.token'     => \App\Http\Middleware\StaticTokenMiddleware::class,
            'aggregator.token' => \App\Http\Middleware\AggregatorTokenMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
