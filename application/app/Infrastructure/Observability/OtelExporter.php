<?php

namespace App\Infrastructure\Observability;

final class OtelExporter
{
    private const SCOPE_NAME = 'soat.laravel';

    public static function exportRequest(string $traceId, string $spanId, string $method, string $route, int $statusCode, int $startNano, int $endNano): void
    {
        $attributes = self::attributes([
            'http.request.method' => $method,
            'http.route' => $route,
            'http.response.status_code' => $statusCode,
            'correlation_id' => OtelContext::correlationId(),
        ]);

        $span = [
            'traceId' => $traceId,
            'spanId' => $spanId,
            'name' => $method . ' ' . $route,
            'kind' => 2,
            'startTimeUnixNano' => (string) $startNano,
            'endTimeUnixNano' => (string) $endNano,
            'attributes' => $attributes,
            'status' => ['code' => $statusCode >= 500 ? 2 : 1],
        ];

        if (OtelContext::parentSpanId() !== null) {
            $span['parentSpanId'] = OtelContext::parentSpanId();
        }

        self::post('/v1/traces', [
            'resourceSpans' => [[
                'resource' => self::resource(),
                'scopeSpans' => [[
                    'scope' => ['name' => self::SCOPE_NAME],
                    'spans' => [$span],
                ]],
            ]],
        ]);

        self::post('/v1/metrics', [
            'resourceMetrics' => [[
                'resource' => self::resource(),
                'scopeMetrics' => [[
                    'scope' => ['name' => self::SCOPE_NAME],
                    'metrics' => [
                        [
                            'name' => 'http.server.request.duration',
                            'unit' => 'ms',
                            'gauge' => ['dataPoints' => [[
                                'timeUnixNano' => (string) $endNano,
                                'asDouble' => ($endNano - $startNano) / 1000000,
                                'attributes' => $attributes,
                            ]]],
                        ],
                        [
                            'name' => 'http.server.request.count',
                            'unit' => '1',
                            'sum' => [
                                'aggregationTemporality' => 2,
                                'isMonotonic' => true,
                                'dataPoints' => [[
                                    'timeUnixNano' => (string) $endNano,
                                    'asInt' => '1',
                                    'attributes' => $attributes,
                                ]],
                            ],
                        ],
                    ],
                ]],
            ]],
        ]);
    }

    public static function exportLog(string $message, string $level, array $context = []): void
    {
        $timeNano = self::nowNano();
        if (OtelContext::correlationId() !== null && !array_key_exists('correlation_id', $context)) {
            $context['correlation_id'] = OtelContext::correlationId();
        }
        $record = [
            'timeUnixNano' => (string) $timeNano,
            'severityText' => strtoupper($level),
            'severityNumber' => self::severityNumber($level),
            'body' => ['stringValue' => $message],
            'attributes' => self::attributes($context),
        ];

        if (OtelContext::traceId() !== null && OtelContext::spanId() !== null) {
            $record['traceId'] = OtelContext::traceId();
            $record['spanId'] = OtelContext::spanId();
        }

        self::post('/v1/logs', [
            'resourceLogs' => [[
                'resource' => self::resource(),
                'scopeLogs' => [[
                    'scope' => ['name' => self::SCOPE_NAME],
                    'logRecords' => [$record],
                ]],
            ]],
        ]);
    }

    public static function nowNano(): int
    {
        return (int) floor(microtime(true) * 1000000000);
    }

    private static function resource(): array
    {
        $attributes = ['service.name' => getenv('OTEL_SERVICE_NAME') ?: 'laravel'];
        foreach (explode(',', getenv('OTEL_RESOURCE_ATTRIBUTES') ?: '') as $pair) {
            if (!str_contains($pair, '=')) {
                continue;
            }
            [$key, $value] = array_map('trim', explode('=', $pair, 2));
            if ($key !== '') {
                $attributes[$key] = $value;
            }
        }

        return ['attributes' => self::attributes($attributes)];
    }

    private static function attributes(array $values): array
    {
        $attributes = [];
        foreach ($values as $key => $value) {
            if (is_bool($value)) {
                $attributes[] = ['key' => (string) $key, 'value' => ['boolValue' => $value]];
            } elseif (is_int($value)) {
                $attributes[] = ['key' => (string) $key, 'value' => ['intValue' => (string) $value]];
            } elseif (is_float($value)) {
                $attributes[] = ['key' => (string) $key, 'value' => ['doubleValue' => $value]];
            } elseif (is_scalar($value) || $value === null) {
                $attributes[] = ['key' => (string) $key, 'value' => ['stringValue' => (string) $value]];
            }
        }

        return $attributes;
    }

    private static function post(string $path, array $payload): void
    {
        $endpoint = rtrim((string) getenv('OTEL_EXPORTER_OTLP_ENDPOINT'), '/');
        if ($endpoint === '') {
            return;
        }

        $body = json_encode($payload);
        if ($body === false) {
            return;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $body,
                'timeout' => 1,
                'ignore_errors' => true,
            ],
        ]);

        @file_get_contents($endpoint . $path, false, $context);
    }

    private static function severityNumber(string $level): int
    {
        return match (strtolower($level)) {
            'debug' => 5,
            'notice' => 9,
            'warning' => 13,
            'error' => 17,
            'critical', 'alert', 'emergency' => 21,
            default => 9,
        };
    }
}
