<?php

namespace App\Infrastructure\Observability;

final class OtelContext
{
    private static ?string $traceId = null;
    private static ?string $spanId = null;
    private static ?string $parentSpanId = null;
    private static ?string $correlationId = null;

    public static function set(string $traceId, string $spanId, ?string $parentSpanId = null, ?string $correlationId = null): void
    {
        self::$traceId = $traceId;
        self::$spanId = $spanId;
        self::$parentSpanId = $parentSpanId;
        self::$correlationId = $correlationId ?: $traceId;
    }

    public static function clear(): void
    {
        self::$traceId = null;
        self::$spanId = null;
        self::$parentSpanId = null;
        self::$correlationId = null;
    }

    public static function traceId(): ?string
    {
        return self::$traceId;
    }

    public static function spanId(): ?string
    {
        return self::$spanId;
    }

    public static function parentSpanId(): ?string
    {
        return self::$parentSpanId;
    }

    public static function correlationId(): ?string
    {
        return self::$correlationId;
    }

    public static function traceparent(): ?string
    {
        if (self::$traceId === null || self::$spanId === null) {
            return null;
        }

        return sprintf('00-%s-%s-01', self::$traceId, self::$spanId);
    }

    public static function propagationHeaders(): array
    {
        $headers = [];
        if (self::traceparent() !== null) {
            $headers['traceparent'] = self::traceparent();
        }
        if (self::$correlationId !== null) {
            $headers['X-Correlation-ID'] = self::$correlationId;
        }

        return $headers;
    }

    public static function parseTraceparent(?string $traceparent): ?array
    {
        if ($traceparent === null) {
            return null;
        }

        if (!preg_match('/^[\da-f]{2}-([\da-f]{32})-([\da-f]{16})-[\da-f]{2}$/i', trim($traceparent), $matches)) {
            return null;
        }

        return [
            'traceId' => strtolower($matches[1]),
            'parentSpanId' => strtolower($matches[2]),
        ];
    }
}
