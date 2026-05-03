<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VendorMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->isVendor()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Vendor access required.',
            ], 403);
        }

        return $next($request);
    }
}
