// @ts-nocheck
import React, { useState, useEffect } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AppLayout from '../../../Layouts/AppLayout';
import Button from '../../../Components/ui/Button';
import { Heading, Text } from '../../../Components/ui/Typography';
import ProductCard from '../product/components/ProductCard';
import recentlyViewedService from './useRecentlyViewedStore';
import isUserLoggedIn from '../../utils/isUserLoggedIn';

export default function RecentlyViewed() {
    const { auth } = usePage().props;
    const user = auth.user;
    const [products, setProducts] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [removing, setRemoving] = useState(null);
    const [clearing, setClearing] = useState(false);

    const loadRecentlyViewed = async () => {
        // Check if user is logged in before loading
        if (!user || !isUserLoggedIn()) {
            setProducts([]);
            setLoading(false);
            return;
        }

        setLoading(true);
        setError(null);
        try {
            const result = await recentlyViewedService.getRecentlyViewed({ limit: 50 });
            if (result.success) {
                setProducts(result.data || []);
            } else {
                setError(result.message);
                setProducts([]);
            }
        } catch (err) {
            setError('Failed to load recently viewed products');
            console.debug('Error loading recently viewed:', err);
            setProducts([]);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        loadRecentlyViewed();
    }, [user]);

    const handleRemove = async (productId) => {
        if (!confirm('Are you sure you want to remove this product from your recently viewed list?')) {
            return;
        }

        setRemoving(productId);
        try {
            const result = await recentlyViewedService.removeProduct({ product_id: productId });
            if (result.success) {
                // Reload the list
                loadRecentlyViewed();
            } else {
                alert(result.message || 'Failed to remove product');
            }
        } catch (err) {
            alert(err.response?.data?.message || 'Failed to remove product');
            console.error('Error removing product:', err);
        } finally {
            setRemoving(null);
        }
    };

    const handleClearAll = async () => {
        if (!confirm('Are you sure you want to clear all recently viewed products? This action cannot be undone.')) {
            return;
        }

        setClearing(true);
        try {
            const result = await recentlyViewedService.clearAll();
            if (result.success) {
                // Reload the list (will be empty)
                loadRecentlyViewed();
            } else {
                alert(result.message || 'Failed to clear recently viewed products');
            }
        } catch (err) {
            alert(err.response?.data?.message || 'Failed to clear recently viewed products');
            console.error('Error clearing products:', err);
        } finally {
            setClearing(false);
        }
    };

    if (loading) {
        return (
            <AppLayout>
                <Head title="Recently Viewed" />
                <div className="min-h-screen bg-gray-50 flex items-center justify-center px-4">
                    <div className="text-center">
                        <div className="text-indigo-600 text-lg sm:text-xl md:text-2xl">Loading recently viewed products...</div>
                    </div>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout>
            <Head title="Recently Viewed Products" />
            <div className="min-h-screen bg-gray-50">
                <div className="max-w-7xl mx-auto py-6 px-4 sm:py-8 sm:px-6 lg:py-12 lg:px-8">
                    {/* Header */}
                    <div className="mb-6 sm:mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <Heading level={1} className="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900">
                                Recently Viewed Products
                            </Heading>
                            <Text className="mt-2 text-sm sm:text-base text-gray-600">
                                Products you've recently viewed
                            </Text>
                        </div>
                        {products.length > 0 && (
                            <Button
                                variant="danger"
                                onClick={handleClearAll}
                                disabled={clearing}
                                size="sm"
                            >
                                {clearing ? 'Clearing...' : 'Clear All'}
                            </Button>
                        )}
                    </div>

                    {error && (
                        <div className="mb-6 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-md">
                            {error}
                        </div>
                    )}

                    {/* Products Grid */}
                    {products.length > 0 ? (
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 md:gap-6 lg:grid-cols-3 xl:grid-cols-4">
                            {products.map((product) => (
                                <ProductCard
                                    key={product.uuid || product.id}
                                    product={product}
                                    variant="default"
                                    showRemoveButton={true}
                                    onRemove={handleRemove}
                                    removing={removing}
                                    showDescription={true}
                                />
                            ))}
                        </div>
                    ) : (
                        <div className="text-center py-12 sm:py-16">
                            <div className="max-w-md mx-auto">
                                <svg 
                                    className="mx-auto h-12 w-12 sm:h-16 sm:w-16 text-gray-400 mb-4" 
                                    fill="none" 
                                    stroke="currentColor" 
                                    viewBox="0 0 24 24"
                                >
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <Heading level={2} className="text-xl sm:text-2xl font-bold text-gray-900 mb-2">
                                    No Recently Viewed Products
                                </Heading>
                                <Text className="text-sm sm:text-base text-gray-600 mb-6">
                                    Start browsing products and they'll appear here
                                </Text>
                                <Link href="/products">
                                    <Button>
                                        Browse Products
                                    </Button>
                                </Link>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}

