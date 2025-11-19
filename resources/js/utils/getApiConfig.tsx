// @ts-nocheck
import getAuthToken from './getAuthToken';

/**
 * Get API configuration for axios requests
 * @param {Object} options - Configuration options
 * @param {string} options.token - Optional token to use
 * @param {string} options.tokenType - 'user' or 'admin' (default: 'user')
 * @returns {Object} - Axios configuration object with headers and withCredentials
 */
export default function getApiConfig(options = {}) {
    const { token: providedToken = null, tokenType = 'user' } = options;
    
    // Get token
    const token = getAuthToken(tokenType, providedToken);
    
    // Build headers
    const headers = { 'Content-Type': 'application/json' };
    
    if (token) {
        if (tokenType === 'admin') {
            headers['AdminToken'] = token;
        } else {
            headers['Authorization'] = `Bearer ${token}`;
        }
    }
    
    // Build config
    return {
        headers,
        withCredentials: true
    };
}

