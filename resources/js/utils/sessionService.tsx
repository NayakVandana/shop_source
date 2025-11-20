// @ts-nocheck
/**
 * Session Service - Centralized session management for web and mobile apps
 * Handles session_id storage, retrieval, and synchronization
 */

import { getSessionId, setSessionId, getSessionIdFromCookie, getSessionIdFromStorage } from './sessionManager';

class SessionService {
    private static instance: SessionService | null = null;
    private isInitialized = false;

    /**
     * Get singleton instance
     */
    static getInstance(): SessionService {
        if (!SessionService.instance) {
            SessionService.instance = new SessionService();
        }
        return SessionService.instance;
    }

    /**
     * Initialize session service
     * Should be called on app startup
     */
    initialize() {
        if (this.isInitialized) {
            return;
        }

        // Sync session_id from cookie to localStorage on startup
        this.syncFromCookie();

        // Setup axios interceptors
        this.setupAxiosInterceptors();

        this.isInitialized = true;
    }

    /**
     * Sync session_id from cookie to localStorage
     */
    syncFromCookie() {
        try {
            const storedSessionId = getSessionId();
            
            // If not in localStorage, try to get from cookie and sync
            if (!storedSessionId) {
                const cookieSessionId = getSessionIdFromCookie();
                if (cookieSessionId) {
                    setSessionId(cookieSessionId);
                }
            }
        } catch (e) {
            console.debug('Failed to sync session_id from cookie:', e);
        }
    }

    /**
     * Setup axios response interceptor to sync session_id
     */
    setupAxiosInterceptors() {
        if (!window?.axios) {
            return;
        }

        // Response interceptor to sync session_id from cookie to localStorage
        window.axios.interceptors.response.use(
            (response) => {
                this.handleResponseSessionSync(response);
                return response;
            },
            (error) => {
                // Also try to sync on error responses
                if (error.response) {
                    this.handleResponseSessionSync(error.response);
                }
                return Promise.reject(error);
            }
        );
    }

    /**
     * Handle session sync from response
     * Priority: response.data.session_id > Set-Cookie header > cookie
     */
    private handleResponseSessionSync(response: any) {
        try {
            // Method 1: Check response data for session_id (highest priority - works for mobile and web)
            if (response.data) {
                // Check if session_id is in data object directly
                if (response.data.session_id) {
                    setSessionId(response.data.session_id);
                    return; // Found in data, no need to check other sources
                }
                
                // Check if session_id is nested in data.data (for API responses)
                if (response.data.data && response.data.data.session_id) {
                    setSessionId(response.data.data.session_id);
                    return;
                }
            }

            // Method 2: Check Set-Cookie header (for web browsers)
            const setCookieHeader = response.headers?.['set-cookie'];
            if (setCookieHeader) {
                const sessionCookie = Array.isArray(setCookieHeader)
                    ? setCookieHeader.find(c => c.includes('session_id='))
                    : (setCookieHeader?.includes('session_id=') ? setCookieHeader : null);
                
                if (sessionCookie) {
                    // Extract session_id value from cookie string
                    const match = sessionCookie.match(/session_id=([^;]+)/);
                    if (match && match[1]) {
                        setSessionId(match[1]);
                        return;
                    }
                }
            }

            // Method 3: Sync from cookie if localStorage is empty (fallback)
            const storedSessionId = getSessionId();
            if (!storedSessionId) {
                const cookieSessionId = getSessionIdFromCookie();
                if (cookieSessionId) {
                    setSessionId(cookieSessionId);
                }
            }
        } catch (e) {
            console.debug('Failed to sync session_id from response:', e);
        }
    }

    /**
     * Get session ID for API requests
     * Returns session_id from localStorage (or cookie as fallback)
     */
    getSessionIdForRequest(): string | null {
        return getSessionIdFromStorage();
    }

    /**
     * Preserve session ID during logout
     * Saves session_id before clearing other auth data
     */
    preserveSessionIdOnLogout(): string | null {
        try {
            // Get session_id before any cleanup
            const sessionId = getSessionIdFromStorage();
            
            // Ensure it's saved in localStorage
            if (sessionId) {
                setSessionId(sessionId);
            }
            
            return sessionId;
        } catch (e) {
            console.debug('Failed to preserve session_id:', e);
            return null;
        }
    }
}

// Export singleton instance
export default SessionService.getInstance();


