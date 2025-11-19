// @ts-nocheck
import React from 'react';
import { Link } from '@inertiajs/react';
import Card from '../../../../Components/ui/Card';
import { Heading, Text } from '../../../../Components/ui/Typography';

export default function ProductCard({
    product,
    variant = 'default',
    showRemoveButton = false,
    onRemove,
    removing,
    showDescription = false
}) {
    const isCompact = variant === 'compact';
    const productId = product.uuid || product.id;

    const handleRemove = (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (onRemove && productId) {
            onRemove(productId);
        }
    };

    return (
        <Card className="overflow-hidden transition-shadow hover:shadow-lg flex flex-col h-full relative">
            {/* Remove Button */}
            {showRemoveButton && onRemove && (
                <button
                    onClick={handleRemove}
                    disabled={removing === productId}
                    className="absolute top-2 right-2 z-10 bg-white rounded-full p-2 shadow-md hover:bg-red-50 transition-colors disabled:opacity-50"
                    title="Remove from recently viewed"
                >
                    <svg 
                        className="w-4 h-4 text-gray-600 hover:text-red-600" 
                        fill="none" 
                        stroke="currentColor" 
                        viewBox="0 0 24 24"
                    >
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            )}

            <Link 
                href={`/product?uuid=${productId}`} 
                className={`aspect-w-16 aspect-h-9 bg-gray-200 flex-shrink-0 relative cursor-pointer`}
            >
                {product.primary_image_url || product.image ? (
                    <img 
                        src={product.primary_image_url || product.image} 
                        alt={product.name} 
                        className={`w-full ${isCompact ? 'h-32 sm:h-40' : 'h-40 sm:h-48'} object-cover transition-transform hover:scale-105`}
                        loading="lazy"
                        onError={(e) => {
                            e.target.src = '/images/placeholder.svg';
                        }}
                    />
                ) : (
                    <div className={`w-full ${isCompact ? 'h-32 sm:h-40' : 'h-40 sm:h-48'} bg-gray-200 flex items-center justify-center`}>
                        <span className={`text-gray-400 ${isCompact ? 'text-xs' : 'text-sm sm:text-base'}`}>No Image</span>
                    </div>
                )}
                {product.discount_info && (
                    <div className={`absolute ${isCompact ? 'top-1 right-1 px-1.5 py-0.5' : 'top-2 left-2 px-2 py-1'} bg-red-600 text-white rounded text-xs font-bold`}>
                        {product.discount_info.display_text}
                    </div>
                )}
            </Link>
            
            <div className={`${isCompact ? 'p-2 sm:p-3' : 'p-4 sm:p-5 md:p-6'} flex flex-col flex-1`}>
                <Link href={`/product?uuid=${productId}`}>
                    <Heading 
                        level={isCompact ? 4 : 3} 
                        className={`${isCompact ? 'mb-1 text-xs sm:text-sm md:text-base' : 'mb-2 text-lg sm:text-xl md:text-2xl'} line-clamp-2 hover:text-indigo-600 transition-colors cursor-pointer`}
                    >
                        {product.name}
                    </Heading>
                </Link>
                
                {showDescription && (product.description || product.short_description) && (
                    <Text 
                        size="sm" 
                        className={`${isCompact ? 'mb-2' : 'mb-4'} line-clamp-2 text-xs sm:text-sm flex-grow`}
                    >
                        {product.description || product.short_description}
                    </Text>
                )}
                
                <div className={`flex flex-col ${isCompact ? '' : 'gap-3 sm:gap-4'} mt-auto`}>
                    <div className="flex flex-col">
                        {product.discount_info ? (
                            <>
                                <span className={`${isCompact ? 'text-xs' : 'text-xs'} text-gray-400 line-through`}>
                                    ${product.discount_info.original_price}
                                </span>
                                <span className={`${isCompact ? 'text-sm sm:text-base' : 'text-xl sm:text-2xl'} font-bold text-red-600 whitespace-nowrap`}>
                                    ${product.discount_info.final_price}
                                </span>
                            </>
                        ) : (
                            <>
                                {product.sale_price ? (
                                    <>
                                        <span className={`${isCompact ? 'text-xs' : 'text-xs'} text-gray-400 line-through`}>
                                            ${product.price}
                                        </span>
                                        <span className={`${isCompact ? 'text-sm sm:text-base' : 'text-xl sm:text-2xl'} font-bold text-red-600 whitespace-nowrap`}>
                                            ${product.sale_price}
                                        </span>
                                    </>
                                ) : (
                                    <span className={`${isCompact ? 'text-sm sm:text-base' : 'text-xl sm:text-2xl'} font-bold text-indigo-600 whitespace-nowrap`}>
                                        ${product.price}
                                    </span>
                                )}
                            </>
                        )}
                    </div>
                </div>
            </div>
        </Card>
    );
}

