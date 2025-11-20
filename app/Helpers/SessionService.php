<?php

namespace App\Helpers;

use App\Models\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * SessionService - Manages sessions for both guest and logged-in users
 * 
 * Guest Users:
 * - session_id stored in localStorage (web) or sent via X-Session-ID header (mobile)
 * - user_id = null in sessions table
 * - Session persists across browser sessions
 * 
 * Logged-in Users:
 * - Same session_id mechanism as guests
 * - user_id set in sessions table when user logs in
 * - Session associated with user account
 * - On logout: session_id preserved, user_id cleared (for cart persistence)
 */
class SessionService
{
    /**
     * Get or create session ID - works for both web and mobile
     * Handles both guest and logged-in users
     * Priority: X-Session-ID header (mobile/web) > Cookie (web) > Laravel session > Generate new
     */
    public static function getOrCreateSessionId(Request $request): string
    {
        $user = $request->user();
        
        // Get existing session ID or generate new one
        $sessionId = self::getSessionId($request) ?: self::generateSessionId();
        
        // Save/update session in database (handles both guest and logged-in users)
        self::saveSession($sessionId, $user, $request);
        
        return $sessionId;
    }
    
    /**
     * Get session ID from request (without creating)
     * Works for both web and mobile apps
     */
    public static function getSessionId(Request $request): ?string
    {
        // 1. Check header (from localStorage - works for web and mobile)
        if ($sessionId = $request->header('X-Session-ID')) {
            return $sessionId;
        }
        
        // 2. Check cookie (web browsers)
        if ($sessionId = $request->cookie('session_id')) {
            return $sessionId;
        }
        
        // 3. Check Laravel session (web)
        try {
            if ($request->hasSession()) {
                return $request->session()->getId();
            }
        } catch (\Exception $e) {
            // Session not available
        }
        
        return null;
    }
    
    /**
     * Generate new session ID
     */
    protected static function generateSessionId(): string
    {
        return 'session_' . Str::random(40) . '_' . time();
    }
    
    /**
     * Save session to database
     * Handles both guest users (user_id = null) and logged-in users
     */
    protected static function saveSession(string $sessionId, $user, Request $request): void
    {
        Session::updateOrCreate(
            ['session_id' => $sessionId],
            [
                'user_id' => $user?->id, // null for guests, user ID for logged-in users
                'device_type' => self::getDeviceType($request),
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip(),
                'last_activity' => now(),
            ]
        );
    }
    
    /**
     * Associate session with user (on login)
     * Updates existing guest session to user session
     */
    public static function associateSessionWithUser(string $sessionId, $user, Request $request): void
    {
        if (!$user) {
            return;
        }
        
        // Update session to associate with user
        Session::where('session_id', $sessionId)->update([
            'user_id' => $user->id,
            'last_activity' => now(),
        ]);
        
        // Also update any other sessions for this user to keep them active
        Session::where('user_id', $user->id)
            ->where('session_id', '!=', $sessionId)
            ->update(['last_activity' => now()]);
    }
    
    /**
     * Disassociate session from user (on logout)
     * Preserves session_id but clears user_id for guest cart persistence
     */
    public static function disassociateSessionFromUser(string $sessionId): void
    {
        Session::where('session_id', $sessionId)->update([
            'user_id' => null,
            'last_activity' => now(),
        ]);
    }
    
    /**
     * Detect device type - simple check for web/mobile/tablet
     */
    protected static function getDeviceType(Request $request): string
    {
        $ua = strtolower($request->userAgent() ?? '');
        
        if (str_contains($ua, 'tablet') || str_contains($ua, 'ipad')) {
            return 'tablet';
        }
        
        if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) {
            return 'mobile';
        }
        
        return 'web';
    }
    
    /**
     * Set session cookie in response (for web browsers)
     * Works for both web and mobile - mobile gets session_id from response data
     */
    public static function setSessionCookie($response, string $sessionId)
    {
        return $response->cookie('session_id', $sessionId, 60 * 24 * 30, '/', null, false, false);
    }
    
    /**
     * Clean up old guest sessions
     */
    public static function cleanupOldSessions(int $daysOld = 90): int
    {
        return Session::where('last_activity', '<', now()->subDays($daysOld))
            ->whereNull('user_id')
            ->delete();
    }
}

