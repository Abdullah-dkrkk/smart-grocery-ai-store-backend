<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NutritionistMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->isNutritionist()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Nutritionist access required.',
            ], 403);
        }

        return $next($request);
    }
}
