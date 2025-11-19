// @ts-nocheck
/**
 * Common API response handler
 * Standardizes API response format across the application
 */

/**
 * Handle API response and return standardized format
 * @param {Object} response - Axios response object
 * @param {string} defaultErrorMessage - Default error message if response doesn't have one
 * @returns {Object} - Standardized response object { success, data, message, error }
 */
export default function handleApiResponse(response, defaultErrorMessage = 'Request failed') {
    if (response.data && (response.data.status || response.data.success)) {
        return {
            success: true,
            data: response.data.data || null,
            message: response.data.message || 'Request successful'
        };
    } else {
        return {
            success: false,
            data: null,
            message: response.data?.message || defaultErrorMessage
        };
    }
}

/**
 * Handle API error and return standardized format
 * @param {Object} error - Error object from axios catch
 * @param {string} defaultErrorMessage - Default error message
 * @returns {Object} - Standardized error response object { success, data, message, error }
 */
export function handleApiError(error, defaultErrorMessage = 'Request failed') {
    return {
        success: false,
        data: null,
        message: error.response?.data?.message || defaultErrorMessage,
        error: error
    };
}

