// @ts-nocheck
import axios from 'axios';
import getApiConfig from '../../../utils/getApiConfig';
import handleApiResponse, { handleApiError } from '../../../utils/api';

/**
 * API service for recently viewed products
 * Only handles API calls, no state management
 */
class RecentlyViewedService {
    /**
     * Get recently viewed products
     */
    async getRecentlyViewed(data) {
        try {
            const response = await axios.post('/api/user/products/recently-viewed', 
                data || { limit: 8 },
                getApiConfig()
            );

            const result = handleApiResponse(response, 'Failed to load recently viewed products');
            // Ensure data is always an array for this endpoint
            if (result.success && !Array.isArray(result.data)) {
                result.data = [];
            } else if (!result.success) {
                result.data = [];
            }
            return result;
        } catch (err) {
            return handleApiError(err, 'Failed to load recently viewed products');
        }
    }

    /**
     * Remove a product from recently viewed
     */
    async removeProduct(data) {
        try {
            const response = await axios.post('/api/user/products/remove-recently-viewed', 
                data,
                getApiConfig()
            );

            return handleApiResponse(response, 'Failed to remove product');
        } catch (err) {
            return handleApiError(err, 'Failed to remove product');
        }
    }

    /**
     * Clear all recently viewed products
     */
    async clearAll() {
        try {
            const response = await axios.post('/api/user/products/clear-recently-viewed', 
                {},
                getApiConfig()
            );

            return handleApiResponse(response, 'Failed to clear products');
        } catch (err) {
            return handleApiError(err, 'Failed to clear products');
        }
    }

    /**
     * Track a product view
     */
    async trackView(data) {
        try {
            const response = await axios.post('/api/user/products/track-view', 
                data,
                getApiConfig()
            );

            return handleApiResponse(response, 'Failed to track product view');
        } catch (err) {
            // Silently fail for tracking - it's not critical
            return handleApiError(err, 'Failed to track product view');
        }
    }
}

// Export singleton instance
const recentlyViewedService = new RecentlyViewedService();

export default recentlyViewedService;
