<?php

namespace App\Logging;

use App\Support\SecretRedactor;
use Illuminate\Log\Logger;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\FormattableHandlerInterface;
use Monolog\Handler\ProcessableHandlerInterface;
use Monolog\LogRecord;

class ConfigureJsonLogging
{
    public function __invoke(Logger $logger): void
    {
        foreach ($logger->getHandlers() as $handler) {
            if (! $handler instanceof FormattableHandlerInterface || ! $handler instanceof ProcessableHandlerInterface) {
                continue;
            }

            $handler->setFormatter(new JsonFormatter);
            $handler->pushProcessor(function (LogRecord $record): LogRecord {
                $requestId = request()->attributes->get('request_id');

                return $record->with(
                    context: SecretRedactor::redact($record->context),
                    extra: array_merge($record->extra, [
                        'request_id' => is_string($requestId) ? $requestId : null,
                    ]),
                );
            });
        }
    }
}
