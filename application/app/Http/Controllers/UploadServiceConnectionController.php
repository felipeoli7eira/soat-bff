<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\File;
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
        try {
            if ($this->uploadServiceUrlEnvIsDefined === false) {
                return response()->json([
                    "err"     => true,
                    "message" => "Configurações do projeto não finalizadas. Favor, define a variável de ambiente UPLOAD_SERVICE_BASE_URL e tente novamente."
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // validacoes basicas sem regra de negocio

            $validated = Validator::make(
                $request->only(["diagram"]),
                [
                    "diagram" => [
                        "required",
                        "file",
                        File::types(["jpg", "jpeg", "png", "pdf"])->max("2mb"), // Size is in kilobytes, so 2048 KB for 2MB
                    ],
                ],
                [
                    "diagram.required" => "É obrigatório um arquivo (imagem ou pdf) de diagrama para análise.",
                    "diagram.file"     => "Arquivo inválido.",
                    "diagram.max"      => "Arquivo muito grande.",
                    "diagram.types"    => "Arquivo inválido. Envie um arquivo JPG, JPEG, PNG ou PDF.",
                ],
                [],
            );

            if ($validated->fails()) {
                return response()->json([
                    "err"      => true,
                    "message"  => "Os arquivos não foram enviados corretamente de acordo com as regras definidas. Favor, verifique e tente novamente.",
                    "data"     => $validated->errors()->first(),
                ], Response::HTTP_BAD_REQUEST);
            }


            $file = $request->file("diagram");

            $contents = fopen($file->getRealPath(), "r");
            $clientOriginalName = $file->getClientOriginalName();

            $request = Http::withHeaders(["Accept" => "application/json"])
                ->timeout(2)
                ->attach(
                    "diagram",
                    $contents,
                    $clientOriginalName
                )->post("{$this->uploadServiceUrl}/api/upload");

            if ($request->successful() === false) {
                return response()->json([
                    "err"      => true,
                    "message"  => "Erro ao fazer upload dos arquivos. Favor, verifique o ambiente e tente novamente.",
                    "data" => $request->json(),
                    "status"   => $request->status(),
                ], $request->status());
            }
        } catch (ConnectionException $err) {
            return response()->json([
                "err"     => true,
                "message" => "Erro ao fazer upload dos arquivos. Favor, verifique o ambiente e tente novamente.",
                "error"   => $err->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (Throwable $err) {
            return response()->json([
                "err"     => true,
                "message" => "Erro ao fazer upload dos arquivos. Favor, verifique o ambiente e tente novamente.",
                "error"   => $err->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }

        return response()->json(
            [
                "err"    => false,
                "msg"    => "Upload realizado com sucesso.",
                "data"   => $request->json(),
                "status" => $request->status(),
            ],
            Response::HTTP_CREATED
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
