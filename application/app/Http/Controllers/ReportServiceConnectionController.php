<?php

namespace App\Http\Controllers;

use App\Infrastructure\Observability\OtelContext;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ReportServiceConnectionController
{
    public bool $reportServiceUrlEnvIsDefined = false;
    public string $reportServiceUrl = "";
    public int $reportServiceTimeoutSeconds = 5000;


    public function __construct()
    {
        $this->reportServiceUrlEnvIsDefined = !is_null(env("REPORT_SERVICE_BASE_URL"));

        if ($this->reportServiceUrlEnvIsDefined) {
            $this->reportServiceUrl = env("REPORT_SERVICE_BASE_URL");
        }

        $this->reportServiceTimeoutSeconds = max(
            1,
            (int) env("REPORT_SERVICE_TIMEOUT_SECONDS", 10)
        );
    }

    public function getReportStatus(Request $request)
    {
        try {
            if ($this->reportServiceUrlEnvIsDefined === false) {
                return response()->json([
                    "err"     => true,
                    "message" => "Configurações do projeto não finalizadas. Favor, define a variável de ambiente REPORT_SERVICE_BASE_URL e tente novamente."
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $ping = Http::withHeaders(OtelContext::propagationHeaders())
                ->timeout($this->reportServiceTimeoutSeconds)
                ->head($this->reportServiceUrl);

            if ($ping->successful() === false) {
                return response()->json([
                    "err"     => true,
                    "message" => "O serviço de report não está respondendo. Favor, verifique o ambiente e tente novamente."
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $response = Http::withHeaders(OtelContext::propagationHeaders())
                ->timeout($this->reportServiceTimeoutSeconds)
                ->get("{$this->reportServiceUrl}/api/status/{$request->route('uuid')}");

            if ($response->successful() === false) {
                return response()->json([
                    "err"     => true,
                    "message" => "Erro ao consultar o relatório. Favor, verifique o ambiente e tente novamente."
                ], Response::HTTP_BAD_REQUEST);
            }
        } catch (ConnectionException $err) {
            return response()->json([
                "err"     => true,
                "message" => "Erro ao consultar o serviço de report. Favor, verifique o ambiente e tente novamente.",
                "error"   => $err->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (Throwable $err) {
            return response()->json([
                "err"     => true,
                "message" => "Erro ao consultar o serviço de report. Favor, verifique o ambiente e tente novamente.",
                "error"   => $err->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json(
            [
                "err" => false,
                "msg" => "Consulta realizada com sucesso.",
                "data" => $response->json(),
            ],
            Response::HTTP_OK,
        );
    }

    public function getReport(Request $request)
    {
        try {
            if ($this->reportServiceUrlEnvIsDefined === false) {
                return response()->json([
                    "err"     => true,
                    "message" => "Configurações do projeto não finalizadas. Favor, define a variável de ambiente REPORT_SERVICE_BASE_URL e tente novamente."
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $ping = Http::withHeaders(OtelContext::propagationHeaders())
                ->timeout($this->reportServiceTimeoutSeconds)
                ->head($this->reportServiceUrl);

            if ($ping->successful() === false) {
                return response()->json([
                    "err"     => true,
                    "message" => "O serviço de report não está respondendo. Favor, verifique o ambiente e tente novamente."
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $response = Http::withHeaders(OtelContext::propagationHeaders())
                ->timeout($this->reportServiceTimeoutSeconds)
                ->get("{$this->reportServiceUrl}/api/report/{$request->route('uuid')}");

            if ($response->successful() === false) {
                return response()->json([
                    "err"     => true,
                    "message" => "Erro ao consultar o relatório. Favor, verifique o ambiente e tente novamente."
                ], Response::HTTP_BAD_REQUEST);
            }
        } catch (ConnectionException $err) {
            return response()->json([
                "err"     => true,
                "message" => "Erro ao consultar o serviço de report. Favor, verifique o ambiente e tente novamente.",
                "error"   => $err->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (Throwable $err) {
            return response()->json([
                "err"     => true,
                "message" => "Erro ao consultar o serviço de report. Favor, verifique o ambiente e tente novamente.",
                "error"   => $err->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }

        $contentType = $response->header('Content-Type') ?? 'application/json';

        if (str_contains($contentType, 'application/pdf')) {
            return response($response->body())
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="report.pdf"') // força o download
                ->header('Content-Length', strlen($response->body()));
        }

        return response()->json(
            [
                "err" => false,
                "msg" => "Falha no download do relatório. Retornando resposta JSON como fallback.",
                "data" => $response->json(),
            ],
            Response::HTTP_INTERNAL_SERVER_ERROR,
        );
    }

    public function ping()
    {
        try {
            if ($this->reportServiceUrlEnvIsDefined === false) {
                return response()->json([
                    "err"     => true,
                    "message" => "Configurações do projeto não finalizadas. Favor, define a variável de ambiente REPORT_SERVICE_BASE_URL e tente novamente."
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $ping = Http::withHeaders(OtelContext::propagationHeaders())
                ->timeout($this->reportServiceTimeoutSeconds)
                ->head($this->reportServiceUrl);

            if ($ping->successful() === false) {
                return response()->json([
                    "err"     => true,
                    "message" => "O serviço de report não está respondendo. Favor, verifique o ambiente e tente novamente."
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $request = Http::withHeaders(OtelContext::propagationHeaders())
                ->timeout($this->reportServiceTimeoutSeconds)
                ->get("{$this->reportServiceUrl}/api/ping");

            if ($request->successful() === false) {
                return response()->json([
                    "err"     => true,
                    "message" => "Erro ao consultar o endpoint de ping do serviço de report. Favor, verifique o ambiente e tente novamente."
                ], Response::HTTP_BAD_REQUEST);
            }
        } catch (ConnectionException $err) {
            return response()->json([
                "err"     => true,
                "message" => "Erro ao consultar o serviço de report. Favor, verifique o ambiente e tente novamente.",
                "error"   => $err->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (Throwable $err) {
            return response()->json([
                "err"     => true,
                "message" => "Erro ao consultar o serviço de report. Favor, verifique o ambiente e tente novamente.",
                "error"   => $err->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json(
            [
                "err" => false,
                "msg" => "Ping realizado com sucesso.",
                "data" => $request->json(),
            ],
            Response::HTTP_OK
        );
    }
}
