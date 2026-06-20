<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Honor X-Forwarded-* so the app generates correct https:// URLs
        // behind Herd's / the host's TLS terminator (otherwise assets can be
        // blocked as mixed content).
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Apple's Sign in with Apple returns via a cross-site form POST
        // (response_mode=form_post) that can't carry our CSRF token; the
        // OAuth code exchange in SocialAuthController guarantees integrity.
        $middleware->validateCsrfTokens(except: [
            'auth/apple/callback',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // Render recoverable HTTP errors through Inertia so visitors see an
        // on-brand page. 500/503 are intentionally left to the standalone
        // Blade fallbacks (errors/{500,503}.blade.php) since Inertia/Vite may
        // be the thing that broke. Skipped in local/testing to keep the rich
        // stack traces, and for JSON/API consumers.
        $exceptions->respond(function (Response $response, \Throwable $exception, Request $request) {
            if (app()->environment(['local', 'testing'])) {
                return $response;
            }

            if ($request->expectsJson() || $request->is('api/*')) {
                return $response;
            }

            $status = $response->getStatusCode();

            if (in_array($status, [403, 404, 419, 429], true)) {
                return Inertia::render("Errors/{$status}")
                    ->toResponse($request)
                    ->setStatusCode($status);
            }

            return $response;
        });
    })->create();
