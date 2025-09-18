<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $token = config('app.api_token', env('API_TOKEN'));
        $provided = $request->header('X-Api-Key');

        if (!$token || $provided !== $token) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        return $next($request);
    }
}
