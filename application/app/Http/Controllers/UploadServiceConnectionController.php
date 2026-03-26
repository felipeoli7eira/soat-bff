<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class UploadServiceConnectionController
{
    public bool $uploadServiceUrlEnvIsDefined = false;
    public string $uploadServiceUrl = "";

    public function __construct()
    {
        $this->uploadServiceUrlEnvIsDefined = !is_null(env("UPLOAD_SERVICE_BASE_URL"));

        if ($this->uploadServiceUrlEnvIsDefined) {
            $this->uploadServiceUrl = env("UPLOAD_SERVICE_BASE_URL");
        }
    }

    public function upload(Request $request)
    {
        return response()->json(
            [
                "err" => false,
                "msg" => "...",
            ],
            Response::HTTP_OK,
        );
    }

    public function ping()
    {
        try {
            if ($this->uploadServiceUrlEnvIsDefined === false) {
                return response()->json([
                    "err"     => true,
                    "message" => "Configurações do projeto não finalizadas. Favor, define a variável de ambiente UPLOAD_SERVICE_BASE_URL e tente novamente."
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $ping = Http::timeout(2)->head($this->uploadServiceUrl);

            if ($ping->successful() === false) {
                return response()->json([
                    "err"     => true,
                    "message" => "O serviço de upload não está respondendo. Favor, verifique o ambiente e tente novamente."
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $request = Http::timeout(2)->get("{$this->uploadServiceUrl}/api/ping");

            if ($request->successful() === false) {
                return response()->json([
                    "err"     => true,
                    "message" => "Erro ao consultar o endpoint de ping do serviço de upload. Favor, verifique o ambiente e tente novamente."
                ], Response::HTTP_BAD_REQUEST);
            }
        } catch (ConnectionException $err) {
            return response()->json([
                "err"     => true,
                "message" => "Erro ao consultar o serviço de upload. Favor, verifique o ambiente e tente novamente.",
                "error"   => $err->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (Throwable $err) {
            return response()->json([
                "err"     => true,
                "message" => "Erro ao consultar o serviço de upload. Favor, verifique o ambiente e tente novamente.",
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
