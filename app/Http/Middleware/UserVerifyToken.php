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
        // Skip user verification if AdminToken header is present (let admin.verify handle it)
        $adminToken = $request->headers->get('AdminToken')
                  ?? $request->headers->get('admintoken')
                  ?? $request->headers->get('ADMINTOKEN')
                  ?? $request->header('AdminToken')
                  ?? $request->header('admintoken')
                  ?? $request->header('ADMINTOKEN')
                  ?? $request->get('AdminToken')
                  ?? $request->get('adminToken');
        
        if ($adminToken) {
            // Admin token present, skip user verification and let admin.verify handle it
            return $next($request);
        }
        
        $token = $request->bearerToken() ?? $request->get('Authorization');

        if (!$token) {
            return $this->sendJsonResponse(false, 'Unauthorized');
        }

        $userToken = UserToken::where(function ($q) use ($token) {
            $q->where('web_access_token', $token)
              ->orWhere('app_access_token', $token);
        })->first();

        if (!$userToken) {
            return $this->sendJsonResponse(false, 'Unauthorized');
        }

        Auth::login($userToken->user);

        return $next($request);
    }
}
