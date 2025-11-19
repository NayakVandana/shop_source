// @ts-nocheck
import getAuthToken from './getAuthToken';

/**
 * Check if user is logged in
 * @param {string} tokenType - 'user' or 'admin' (default: 'user')
 * @returns {boolean} - True if user is logged in
 */
export default function isUserLoggedIn(tokenType = 'user') {
    const token = getAuthToken(tokenType);
    return !!token;
}

