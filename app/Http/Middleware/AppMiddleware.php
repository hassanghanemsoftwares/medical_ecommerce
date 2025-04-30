<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class AppMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $app_key = $request->header('App-key');
        if ($app_key == env("FRONTEND_APP_KEY")) {
            $lang = $request->header('Accept-Language', 'en');
            App::setLocale($lang);
            $response = $next($request);
            $response->headers->set('Content-Type', 'application/json; charset=utf-8');
            return $response;
        }
        return response()->json([
            'result' => false,
            'message' => 'Access denied',
        ]);
    }
}
