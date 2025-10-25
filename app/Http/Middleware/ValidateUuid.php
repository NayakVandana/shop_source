<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;

class ValidateUuid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get all route parameters
        $routeParams = $request->route()->parameters();
        
        foreach ($routeParams as $key => $value) {
            // Check if the parameter looks like a UUID
            if (in_array($key, ['id', 'user', 'product', 'category', 'order', 'discount'])) {
                if (!Str::isUuid($value)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid resource identifier',
                        'error' => 'The provided identifier is not a valid UUID format'
                    ], 400);
                }
            }
        }

        return $next($request);
    }
}
