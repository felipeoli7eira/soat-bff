<?php

namespace App\Http\Middleware;

use App\Infrastructure\Observability\OtelContext;
use App\Infrastructure\Observability\OtelExporter;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class OpenTelemetryMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $incomingContext = OtelContext::parseTraceparent($request->headers->get('traceparent'));
        $traceId = $incomingContext['traceId'] ?? bin2hex(random_bytes(16));
        $spanId = bin2hex(random_bytes(8));
        $parentSpanId = $incomingContext['parentSpanId'] ?? null;
        $correlationId = $request->headers->get('X-Correlation-ID') ?: $request->headers->get('X-Request-ID') ?: $traceId;
        $startNano = OtelExporter::nowNano();
        $statusCode = 500;

        OtelContext::set($traceId, $spanId, $parentSpanId, $correlationId);

        try {
            $response = $next($request);
            $statusCode = $response->getStatusCode();
            $response->headers->set('traceparent', OtelContext::traceparent());
            $response->headers->set('X-Correlation-ID', $correlationId);
            return $response;
        } catch (Throwable $exception) {
            OtelExporter::exportLog($exception->getMessage(), 'error', ['exception.type' => $exception::class]);
            throw $exception;
        } finally {
            $route = optional($request->route())->uri() ?: $request->path();
            $endNano = OtelExporter::nowNano();
            OtelExporter::exportRequest($traceId, $spanId, $request->method(), '/' . ltrim($route, '/'), $statusCode, $startNano, $endNano);
            OtelExporter::exportLog('http.request.completed', $statusCode >= 500 ? 'error' : 'info', [
                'http.request.method' => $request->method(),
                'http.route' => '/' . ltrim($route, '/'),
                'http.response.status_code' => $statusCode,
                'correlation_id' => $correlationId,
            ]);
            OtelContext::clear();
        }
    }
}
