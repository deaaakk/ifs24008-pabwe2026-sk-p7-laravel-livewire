<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureStockOwnership;
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
        // Pengguna yang sudah login dan membuka / atau /login diarahkan sesuai role
        $middleware->redirectUsersTo(fn () => auth()->user()?->isAdmin() ? '/admin/users' : '/stocks');

        $middleware->alias([
            'role' => EnsureRole::class,
            'owns.stock' => EnsureStockOwnership::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();