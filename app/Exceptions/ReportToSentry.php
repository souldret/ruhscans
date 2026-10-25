<?php

namespace App\Exceptions;

use App\Services\SentryReporter;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ReportToSentry
{
    public function __invoke(Throwable $exception): void
    {
        if ($exception instanceof HttpExceptionInterface && $exception->getStatusCode() < 500) {
            return;
        }

        app(SentryReporter::class)->capture($exception);
    }
}
