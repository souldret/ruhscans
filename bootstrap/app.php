<?php

use App\Exceptions\ReportToSentry;
use App\Http\Middleware\AssignRequestId;
use App\Http\Middleware\EnsureEmailIsVerifiedWhenRequired;
use App\Http\Middleware\SecurityHeaders;
use App\Listeners\AlertAdmin;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['adult', 'notice']);
        $middleware->web(append: [
            AssignRequestId::class,
            SecurityHeaders::class,
        ]);
        $middleware->api(prepend: [
            AssignRequestId::class,
        ]);
        $middleware->alias([
            'verified' => EnsureEmailIsVerifiedWhenRequired::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $message = collect($exception->errors())->flatten()->first() ?: 'Girdi geçersiz.';

            return response()->json(['message' => $message], 422);
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json(['message' => 'Oturum geçersiz.'], 401);
        });

        $exceptions->render(function (ThrottleRequestsException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json(['message' => 'Çok fazla deneme. Bir dakika bekle.'], 429);
        });

        $exceptions->render(function ($exception, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return null;
            }

            if (! $exception instanceof HttpExceptionInterface) {
                return null;
            }

            $status = $exception->getStatusCode();

            if (! in_array($status, [403, 404, 419, 429, 500], true) || ! view()->exists('errors.'.$status)) {
                return null;
            }

            return response()->view('errors.'.$status, [], $status);
        });

        $exceptions->report(function (Throwable $exception) {
            (new ReportToSentry)($exception);
        });
    })->create();

$app->booted(function () {
    app('events')->listen(Lockout::class, [AlertAdmin::class, 'lockout']);
    app('events')->listen(JobFailed::class, [AlertAdmin::class, 'jobFailed']);
});

return $app;
