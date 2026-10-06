<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn ($request) => $request->is('admin') || $request->is('admin/*')
            ? route('admin.login')
            : route('login'));

        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'role' => \App\Http\Middleware\CheckRole::class,
            'midtrans.csp' => \App\Http\Middleware\MidtransCspMiddleware::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\MidtransCspMiddleware::class,
        ]);

        // Webhook Midtrans dikirim dari server mereka, tanpa token CSRF.
        $middleware->validateCsrfTokens(except: [
            'payment/callback',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
