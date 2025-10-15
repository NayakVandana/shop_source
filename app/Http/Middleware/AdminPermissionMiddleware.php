<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminPermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $permission = null): Response
    {
        $user = $request->user();

        // Check if user is authenticated
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
                'data' => null
            ], 401);
        }

        // Check if user is admin
        if (!$user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Admin privileges required.',
                'data' => null
            ], 403);
        }

        // If permission is specified, check it
        if ($permission) {
            if (!$user->hasPermission($permission)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Insufficient permissions.',
                    'data' => [
                        'required_permission' => $permission,
                        'user_permissions' => $user->getPermissions()
                    ]
                ], 403);
            }
        }

        return $next($request);
    }
}