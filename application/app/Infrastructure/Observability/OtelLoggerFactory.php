<?php

namespace App\Infrastructure\Observability;

use Monolog\Logger;

final class OtelLoggerFactory
{
    public function __invoke(array $config): Logger
    {
        return new Logger('otel', [new OtelLogHandler($config['level'] ?? 'debug')]);
    }
}
