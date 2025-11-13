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
        // Try multiple ways to get the token
        // Laravel normalizes headers, so try different variations
        $token = $request->headers->get('AdminToken')
              ?? $request->headers->get('admintoken')
              ?? $request->headers->get('ADMINTOKEN')
              ?? $request->header('AdminToken')
              ?? $request->header('admintoken')
              ?? $request->header('ADMINTOKEN')
              ?? $request->get('AdminToken')
              ?? $request->get('adminToken');

        if (!$token) {
            // Log for debugging - check all headers
            $allHeaders = [];
            foreach ($request->headers->all() as $key => $value) {
                $allHeaders[$key] = is_array($value) ? implode(', ', $value) : $value;
            }
            \Log::warning('AdminVerifyToken: No token found', [
                'all_headers' => $allHeaders,
                'request_method' => $request->method(),
                'request_path' => $request->path(),
            ]);
            return $this->sendJsonResponse(false, 'Unauthorized', [], 400);
        }

        try {
            $decrypted = Crypt::decryptString($token);
            $decryptedData = json_decode($decrypted, true);
            
            if (json_last_error() !== JSON_ERROR_NONE || !isset($decryptedData['user_id'])) {
                \Log::warning('AdminVerifyToken: Invalid token format', [
                    'json_error' => json_last_error_msg(),
                ]);
                return $this->sendJsonResponse(false, 'Unauthorized', [], 400);
            }
            
            $userId = $decryptedData['user_id'];
        } catch (DecryptException $e) {
            \Log::warning('AdminVerifyToken: Decryption failed', [
                'error' => $e->getMessage(),
            ]);
            return $this->sendJsonResponse(false, 'Unauthorized', [], 400);
        } catch (\Exception $e) {
            \Log::error('AdminVerifyToken: Unexpected error', [
                'error' => $e->getMessage(),
            ]);
            return $this->sendJsonResponse(false, 'Unauthorized', [], 400);
        }

        $admin = User::where('id', $userId)->where('is_registered', true)->where('is_admin', true)->first();

        if (!$admin) {
            \Log::warning('AdminVerifyToken: Admin user not found', [
                'user_id' => $userId,
                'user_exists' => User::where('id', $userId)->exists(),
            ]);
            return $this->sendJsonResponse(false, 'Unauthorized', [], 400);
        }

        Auth::guard('admin')->login($admin);

        return $next($request);
    }
}
