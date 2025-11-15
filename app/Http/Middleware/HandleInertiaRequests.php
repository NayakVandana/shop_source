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
        
        // Get user permissions if user exists and is admin
        $permissions = [];
        if ($user && ($user->is_admin || $user->role === 'admin' || $user->role === 'super_admin')) {
            $permissions = \App\Http\Controllers\admin\PermissionController::getUserPermissionKeys($user);
        }
        
        return [
            ...parent::share($request),
            
            // Share authenticated user data
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'uuid' => $user->uuid,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'is_admin' => $user->is_admin ?? false,
                    'permissions' => $permissions,
                ] : null,
            ],
        ];
    }

    /**
     * Authenticate user from token
     * Uses the same reference pattern as UserVerifyToken middleware
     */
    protected function authenticateFromToken(Request $request)
    {
        // Check for admin impersonation (skip normal token verification)
        $impersonateUserId = $request->header('X-Impersonate-User') ?? $request->get('impersonate_user_id');
        if ($impersonateUserId) {
            // Admin impersonation - let AdminImpersonation middleware handle it
            return null;
        }

        // Check for AdminToken header or cookie (let AdminVerifyToken middleware handle it)
        $adminToken = $request->header('AdminToken')
                  ?? $request->headers->get('AdminToken')
                  ?? $request->headers->get('admintoken')
                  ?? $request->headers->get('ADMINTOKEN');
        
        // Also check for admin_token cookie
        if (!$adminToken) {
            $allCookies = $request->cookies->all();
            $adminToken = $allCookies['admin_token'] ?? null;
        }
        
        // Also check Cookie header for admin_token
        if (!$adminToken) {
            $cookieHeader = $request->header('Cookie', '');
            if (preg_match('/admin_token=([^;]+)/', $cookieHeader, $matches)) {
                $adminToken = urldecode($matches[1]);
            }
        }
        
        if ($adminToken) {
            // Admin token present - try to authenticate as admin
            try {
                $decrypted = \Illuminate\Support\Facades\Crypt::decryptString($adminToken);
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
            }
        }

        // Normal token verification - following reference pattern
        // Check bearer token, Authorization header, or cookie (for Inertia)
        // Do NOT use URL query parameters - use localStorage/cookies only
        $token = $request->bearerToken() 
              ?? $request->get('Authorization');
        
        // Try to get token from cookie - check multiple ways
        if (!$token) {
            // Method 1: Standard Laravel cookie helper
            $token = $request->cookie('auth_token');
        }
        
        if (!$token) {
            // Method 2: Direct access from cookies array
            $allCookies = $request->cookies->all();
            $token = $allCookies['auth_token'] ?? null;
        }
        
        if (!$token) {
            // Method 3: Check cookie header directly
            $cookieHeader = $request->header('Cookie', '');
            if (preg_match('/auth_token=([^;]+)/', $cookieHeader, $matches)) {
                $token = urldecode($matches[1]);
            }
        }

        // Debug: Log token status (only in development)
        if (app()->environment(['local', 'development'])) {
            \Log::debug('HandleInertiaRequests: Token check', [
                'has_token' => !empty($token),
                'token_preview' => $token ? substr($token, 0, 20) . '...' : null,
                'bearerToken' => $request->bearerToken() ? 'present' : 'missing',
                'Authorization' => $request->get('Authorization') ? 'present' : 'missing',
                'cookie_method1' => $request->cookie('auth_token') ? 'found' : 'missing',
                'cookie_method2' => isset($request->cookies->all()['auth_token']) ? 'found' : 'missing',
                'cookie_header' => $request->header('Cookie') ? 'present' : 'missing',
                'all_cookies' => array_keys($request->cookies->all()),
            ]);
        }

        if (!$token) {
            return null;
        }

        // Use the same UserToken lookup pattern as reference
        $userToken = \App\Models\UserToken::where(function ($q) use ($token) {
            $q->where('web_access_token', $token)
              ->orWhere('app_access_token', $token);
        })->first();

        // Debug: Log if token not found in database
        if (!$userToken && app()->environment(['local', 'development'])) {
            \Log::debug('HandleInertiaRequests: Token not found in UserToken table', [
                'token_preview' => substr($token, 0, 20) . '...',
                'web_token_exists' => \App\Models\UserToken::where('web_access_token', $token)->exists(),
                'app_token_exists' => \App\Models\UserToken::where('app_access_token', $token)->exists(),
            ]);
        }

        if (!$userToken) {
            return null;
        }

        \Illuminate\Support\Facades\Auth::login($userToken->user);
        
        // Debug: Log successful authentication
        if (app()->environment(['local', 'development'])) {
            \Log::debug('HandleInertiaRequests: User authenticated', [
                'user_id' => $userToken->user->id,
                'user_name' => $userToken->user->name,
                'user_email' => $userToken->user->email,
            ]);
        }
        
        return $userToken->user;
    }
}
