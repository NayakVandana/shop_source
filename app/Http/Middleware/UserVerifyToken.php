<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Controller;
use App\Models\UserToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserVerifyToken extends Controller
{
    public function handle(Request $request, Closure $next)
    {
        // Check if this is an admin impersonation request
        $impersonateUserId = $request->header('X-Impersonate-User') ?? $request->get('impersonate_user_id');
        
        if ($impersonateUserId) {
            // Skip normal token verification for admin impersonation
            // The AdminImpersonation middleware will handle this
            return $next($request);
        }

        // Skip user verification if AdminToken header is present (let admin.verify handle it)
        $adminToken = $request->headers->get('AdminToken')
                  ?? $request->headers->get('admintoken')
                  ?? $request->headers->get('ADMINTOKEN')
                  ?? $request->header('AdminToken')
                  ?? $request->header('admintoken')
                  ?? $request->header('ADMINTOKEN');
        
        if ($adminToken) {
            // Admin token present, skip user verification and let admin.verify handle it
            return $next($request);
        }

        // Normal token verification - following reference pattern
        // Do NOT use URL query parameters - use localStorage/cookies only
        $token = $request->bearerToken() ?? $request->get('Authorization');

        if (!$token) {
            return $this->sendJsonResponse(false, 'Unauthorized');
        }

        // Use the same UserToken lookup pattern as reference
        $userToken = UserToken::where(function ($q) use ($token) {
            $q->where('web_access_token', $token);
            $q->orWhere('app_access_token', $token);
        })->first();

        if (!$userToken) {
            return $this->sendJsonResponse(false, 'Unauthorized');
        }

        Auth::login($userToken->user);

        return $next($request);
    }
}
