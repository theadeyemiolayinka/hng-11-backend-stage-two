<?php

use Illuminate\Http\Request;
use Illuminate\Foundation\Application;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            return $request->wantsJson()
                ? response()->json(['message' => $exception->getMessage()], 401)
                // ? response()->json([
                //     'status' => 'Bad Request',
                //     'message' => 'Client error',
                //     'statusCode' => 400
                // ], 400)
                : redirect()->guest($exception->redirectTo($request) ?? route('login'));
        });
    })->create();
