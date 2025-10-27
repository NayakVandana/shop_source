<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        // Check for token-based authentication first
        $user = $this->authenticateFromToken($request);
        
        // If no user from token, check session
        if (!$user) {
            $user = $request->user();
        }
        
        return [
            ...parent::share($request),
            
            // Share authenticated user data
            'auth' => [
                'user' => $user ? [
                    'uuid' => $user->uuid,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'is_admin' => $user->is_admin ?? false,
                ] : null,
            ],
        ];
    }

    /**
     * Authenticate user from token
     */
    protected function authenticateFromToken(Request $request)
    {
        // Check for token in Authorization header, query param, cookie, or AdminToken header
        $token = $request->bearerToken() 
              ?? $request->get('token') 
              ?? $request->cookie('auth_token')
              ?? $request->cookie('admin_token')
              ?? $request->header('AdminToken');

        if (!$token) {
            return null;
        }

        // First, try regular user token
        $userToken = \App\Models\UserToken::where(function ($q) use ($token) {
            $q->where('web_access_token', $token)
              ->orWhere('app_access_token', $token);
        })->first();

        if ($userToken) {
            \Illuminate\Support\Facades\Auth::login($userToken->user);
            return $userToken->user;
        }

        // If not found, check for encrypted admin token
        try {
            $decrypted = \Illuminate\Support\Facades\Crypt::decryptString($token);
            $decrypted = (object) json_decode($decrypted);
            $userId = $decrypted->user_id;
            
            $admin = \App\Models\User::where('id', $userId)
                                   ->where('is_admin', true)
                                   ->where('is_registered', true)
                                   ->first();
            
            if ($admin) {
                \Illuminate\Support\Facades\Auth::login($admin);
                return $admin;
            }
        } catch (\Exception $e) {
            // Token not encrypted or invalid
            return null;
        }

        return null;
    }
}
