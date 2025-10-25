<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Controller;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class AdminVerifyToken extends Controller
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('AdminToken') ?? $request->get('AdminToken');

        if (!$token) {
            return $this->sendJsonResponse(false, 'Unauthorized', null, 400);
        }

        try {
            $decrypted = Crypt::decryptString($token);
            $decrypted = (object) json_decode($decrypted);
            $userId = $decrypted->user_id;
        } catch (DecryptException $e) {
            return $this->sendJsonResponse(false, 'Unauthorized', null, 400);
        }

        $admin = User::where('id', $userId)->where('is_registered', true)->where('is_admin', true)->first();

        if (!$admin) {
            return $this->sendJsonResponse(false, 'Unauthorized', null, 400);
        }

        Auth::guard('admin')->login($admin);

        return $next($request);
    }
}
