<?php

use Illuminate\Support\Facades\Route;

Route::get("ping", function () {
    return response()->json([
        "err" => false,
        "msg" => "pong",
        "service" => env("APP_NAME", "APP_ENV não definida"),
    ]);
});

Route::get("ping/upload", [
    \App\Http\Controllers\UploadServiceConnectionController::class,
    "ping",
]);

Route::get("/status/{uuid}", [
    \App\Http\Controllers\ReportServiceConnectionController::class,
    "getReportStatus",
]);

Route::get("/report/{uuid}", [
    \App\Http\Controllers\ReportServiceConnectionController::class,
    "getReport",
]);

Route::post("/upload", [
    \App\Http\Controllers\UploadServiceConnectionController::class,
    "upload",
]);

Route::fallback(
    fn() => response()->json([
        "err" => true,
        "msg" => "Recurso não encontrado",
    ]),
);
