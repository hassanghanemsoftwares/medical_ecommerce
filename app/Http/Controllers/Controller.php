<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Exception;

abstract class Controller
{
    //

    protected function errorResponse(string $message, Exception $e): JsonResponse
    {
        return response()->json([
            'result' => false,
            'message' => $message,
            'error' => config('app.debug') ? $e->getMessage() : __('messages.general_error'),
        ]);
    }
}
