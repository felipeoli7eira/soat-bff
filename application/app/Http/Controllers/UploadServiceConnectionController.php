<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UploadServiceConnectionController
{
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
}
