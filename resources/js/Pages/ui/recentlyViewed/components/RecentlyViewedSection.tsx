// @ts-nocheck
import React, { useState, useEffect } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { Heading, Text } from '../../../../Components/ui/Typography';
import ProductCard from '../../product/components/ProductCard';
import recentlyViewedService from '../useRecentlyViewedStore';
import isUserLoggedIn from '../../../../utils/isUserLoggedIn';

export default function RecentlyViewedSection({
    limit = 8,
    showViewAll = true,
    variant = 'compact',
    showRemoveButton = false,
    onRemove,
    removing,
    showDescription = false,
    gridCols
}) {
    const { auth } = usePage().props;
    const user = auth.user;
    const [products, setProducts] = useState([]);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        const loadRecentlyViewed = async () => {
            // Check if user is logged in before loading
            if (!user || !isUserLoggedIn()) {
                setProducts([]);
                return;
            }

            setLoading(true);
            try {
                const result = await recentlyViewedService.getRecentlyViewed({ limit });
                if (result.success) {
                    setProducts(result.data || []);
                } else {
                    setProducts([]);
                }
            } catch (err) {
                console.debug('Error loading recently viewed:', err);
                setProducts([]);
            } finally {
                setLoading(false);
            }
        };

        loadRecentlyViewed();
    }, [limit, user]);
    
    if (products.length === 0) {
        return null;
    }

    const isCompact = variant === 'compact';
    const defaultGridCols = isCompact 
        ? 'grid-cols-2 sm:grid-cols-3 sm:gap-4 md:gap-5 lg:grid-cols-4 xl:grid-cols-8'
        : 'grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 md:gap-6 lg:grid-cols-3 xl:grid-cols-4';

    return (
        <div className={`${isCompact ? 'mb-8 sm:mb-12' : ''}`}>
            <div className="flex items-center justify-between mb-4 sm:mb-6">
                <div>
                    <Heading level={2} className={`${isCompact ? 'text-xl sm:text-2xl md:text-3xl' : 'text-2xl sm:text-3xl md:text-4xl'} font-bold text-gray-900`}>
                        Recently Viewed
                    </Heading>
                    <Text className="mt-1 text-sm sm:text-base text-gray-600">
                        Products you've recently viewed
                    </Text>
                </div>
                {showViewAll && (
                    <Link
                        href="/recently-viewed"
                        className="text-indigo-600 hover:text-indigo-700 text-sm sm:text-base font-medium"
                    >
                        View All →
                    </Link>
                )}
            </div>
            <div className={`grid ${gridCols || defaultGridCols} gap-3`}>
                {products.map((product) => (
                    <ProductCard
                        key={product.uuid || product.id}
                        product={product}
                        variant={variant}
                        showRemoveButton={showRemoveButton}
                        onRemove={onRemove}
                        removing={removing}
                        showDescription={showDescription}
                    />
                ))}
            </div>
        </div>
    );
}

