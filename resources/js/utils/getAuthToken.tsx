// @ts-nocheck
/**
 * Get authentication token from localStorage or cookies
 * @param {string} tokenType - 'user' or 'admin' (default: 'user')
 * @param {string} providedToken - Optional token to use instead of retrieving
 * @returns {string|null} - The token or null if not found
 */
export default function getAuthToken(tokenType = 'user', providedToken = null) {
    // If token is provided, use it
    if (providedToken) {
        return providedToken;
    }

    const storageKey = tokenType === 'admin' ? 'admin_token' : 'auth_token';
    
    // Try localStorage first
    try {
        const token = localStorage.getItem(storageKey);
        if (token) {
            return token;
        }
    } catch (e) {
        // localStorage not available
    }

    // Then try cookie (for persistent authentication)
    try {
        const cookieName = tokenType === 'admin' ? 'admin_token' : 'auth_token';
        const cookieToken = document.cookie
            .split(';')
            .find(c => c.trim().startsWith(`${cookieName}=`));
        
        if (cookieToken) {
            return cookieToken.split('=')[1]?.trim() || null;
        }
    } catch (e) {
        // Cookie access not available
    }

    return null;
}

