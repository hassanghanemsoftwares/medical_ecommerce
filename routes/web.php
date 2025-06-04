<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::fallback(function (Request $request) {
    return response()->json([
        'message' => 'The route ' . $request->path() . ' could not be found.'
    ], 404);
});