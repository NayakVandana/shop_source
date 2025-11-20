// @ts-nocheck
/**
 * Session Manager - Handles session_id storage and retrieval
 * Stores session_id in localStorage for long-term persistence
 * Works for both web and mobile apps
 */

const SESSION_ID_KEY = 'session_id';

/**
 * Get session ID from localStorage
 * @returns {string|null} - The session ID or null if not found
 */
export function getSessionId() {
    try {
        if (window?.localStorage) {
            return localStorage.getItem(SESSION_ID_KEY);
        }
    } catch (e) {
        // localStorage not available or access denied
        console.debug('localStorage not available for session_id');
    }
    return null;
}

/**
 * Set session ID in localStorage
 * @param {string} sessionId - The session ID to store
 * @returns {boolean} - True if successful, false otherwise
 */
export function setSessionId(sessionId) {
    try {
        if (window?.localStorage) {
            localStorage.setItem(SESSION_ID_KEY, sessionId);
            return true;
        }
    } catch (e) {
        // localStorage not available or access denied
        console.debug('Failed to set session_id in localStorage:', e);
    }
    return false;
}

/**
 * Get session ID from cookie (fallback for web browsers)
 * @returns {string|null} - The session ID or null if not found
 */
export function getSessionIdFromCookie() {
    try {
        if (document?.cookie) {
            const cookie = document.cookie
                .split(';')
                .find(c => c.trim().startsWith(`${SESSION_ID_KEY}=`));
            
            if (cookie) {
                return cookie.split('=')[1]?.trim() || null;
            }
        }
    } catch (e) {
        // Cookie access not available
        console.debug('Cookie access not available for session_id');
    }
    return null;
}

/**
 * Get session ID from localStorage or cookie (fallback)
 * Works for both web and mobile - mobile uses localStorage, web uses localStorage with cookie fallback
 * @returns {string|null} - The session ID or null if not found
 */
export function getSessionIdFromStorage() {
    // Try localStorage first (works for both web and mobile)
    let sessionId = getSessionId();
    
    // If not in localStorage, try cookie (web browsers only)
    if (!sessionId) {
        sessionId = getSessionIdFromCookie();
        
        // If found in cookie, sync to localStorage
        if (sessionId) {
            setSessionId(sessionId);
        }
    }
    
    return sessionId;
}

