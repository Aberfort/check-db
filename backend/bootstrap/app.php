<?php

use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Behind a PaaS edge proxy (Railway/Render) that terminates TLS —
        // trust its X-Forwarded-* headers so Laravel knows requests are HTTPS.
        $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PORT |
            Request::HEADER_X_FORWARDED_PROTO);

        $middleware->api(append: [SetLocale::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // API clients get a predictable error shape instead of an HTML page.
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($e instanceof ValidationException) {
                return response()->json([
                    'error' => ['code' => 'validation_failed', 'message' => $e->getMessage()],
                    'errors' => $e->errors(),
                ], 422);
            }

            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

            return response()->json([
                'error' => [
                    'code' => $status === 404 ? 'not_found' : 'server_error',
                    'message' => $status === 500 && ! config('app.debug')
                        ? 'Something went wrong while processing the request.'
                        : $e->getMessage(),
                ],
            ], $status);
        });
    })->create();
