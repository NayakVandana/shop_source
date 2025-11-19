// @ts-nocheck
import React, { useState, useEffect } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import axios from 'axios';
import UserLayout from '../../../Layouts/UserLayout';
import GuestLayout from '../../../Layouts/GuestLayout';
import Button from '../../../Components/ui/Button';

export default function ProductDetail() {
    const { auth } = usePage().props;
    const user = auth.user;
    const [product, setProduct] = useState(null);
    const [loading, setLoading] = useState(true);
    const [quantity, setQuantity] = useState(1);
    const [currentImage, setCurrentImage] = useState(null);
    const [addingToCart, setAddingToCart] = useState(false);
    const [cartMessage, setCartMessage] = useState('');
    const [selectedSize, setSelectedSize] = useState(null);
    const [selectedColor, setSelectedColor] = useState(null);

    // Get available sizes for selected color
    const getAvailableSizesForColor = React.useCallback((color) => {
        if (!product || !product.variations || !Array.isArray(product.variations)) {
            return product?.sizes || [];
        }
        
        if (!color) {
            // If no color selected, return all unique sizes from variations or product sizes
            const sizesFromVariations = [...new Set(product.variations.map(v => v.size).filter(Boolean))];
            return sizesFromVariations.length > 0 ? sizesFromVariations : (product.sizes || []);
        }
        
        // Filter variations by color and get unique sizes
        const colorVariations = product.variations.filter(v => v.color === color);
        const availableSizes = [...new Set(colorVariations.map(v => v.size).filter(Boolean))];
        
        // If no sizes found for this color, return all product sizes as fallback
        return availableSizes.length > 0 ? availableSizes : (product.sizes || []);
    }, [product]);

    // Get available sizes based on selected color
    const availableSizes = React.useMemo(() => {
        return getAvailableSizesForColor(selectedColor);
    }, [selectedColor, getAvailableSizesForColor]);

    // Check stock availability for selected variation
    const checkStockAvailability = React.useCallback(() => {
        if (!product) return false;
        
        // If product has variations, check specific variation stock
        if (product.variations && Array.isArray(product.variations) && product.variations.length > 0) {
            if (selectedSize && selectedColor) {
                // Check specific size-color combination
                const variation = product.variations.find(
                    v => v.size === selectedSize && v.color === selectedColor
                );
                if (variation) {
                    return variation.stock_quantity > 0 && variation.in_stock;
                }
                return false;
            } else if (selectedColor) {
                // Check if any size for this color has stock
                const colorVariations = product.variations.filter(v => v.color === selectedColor);
                return colorVariations.some(v => v.stock_quantity > 0 && v.in_stock);
            } else if (selectedSize) {
                // Check if any color for this size has stock
                const sizeVariations = product.variations.filter(v => v.size === selectedSize);
                return sizeVariations.some(v => v.stock_quantity > 0 && v.in_stock);
            } else {
                // Check if any variation has stock
                return product.variations.some(v => v.stock_quantity > 0 && v.in_stock);
            }
        }
        
        // For products without variations, check general stock
        return product.stock_quantity > 0 && product.in_stock;
    }, [product, selectedSize, selectedColor]);

    // Get stock quantity for selected variation
    const getStockQuantity = React.useCallback(() => {
        if (!product) return 0;
        
        // If product has variations, get specific variation stock
        if (product.variations && Array.isArray(product.variations) && product.variations.length > 0) {
            if (selectedSize && selectedColor) {
                const variation = product.variations.find(
                    v => v.size === selectedSize && v.color === selectedColor
                );
                return variation ? variation.stock_quantity : 0;
            }
        }
        
        // For products without variations, return general stock
        return product.stock_quantity || 0;
    }, [product, selectedSize, selectedColor]);

    const isInStock = React.useMemo(() => checkStockAvailability(), [checkStockAvailability]);
    const stockQuantity = React.useMemo(() => getStockQuantity(), [getStockQuantity]);

    // Reset selected size if it's not available for the selected color
    useEffect(() => {
        if (selectedSize && selectedColor && !availableSizes.includes(selectedSize)) {
            setSelectedSize(null);
        }
    }, [selectedColor, availableSizes, selectedSize]);

    // Reset quantity if it exceeds available stock when stock changes
    useEffect(() => {
        if (stockQuantity > 0 && quantity > stockQuantity) {
            setQuantity(stockQuantity);
        } else if (stockQuantity === 0 && quantity > 0) {
            setQuantity(1); // Reset to 1 when out of stock (will be disabled anyway)
        }
    }, [stockQuantity]);

    // Remove token from URL immediately - use localStorage/cookies only
    useEffect(() => {
        try {
            const url = new URL(window.location.href);
            if (url.searchParams.has('token')) {
                // Extract token and save to localStorage if not already there
                const token = url.searchParams.get('token');
                if (token && !localStorage.getItem('auth_token')) {
                    localStorage.setItem('auth_token', token);
                }
                // Remove token from URL immediately (but keep uuid)
                url.searchParams.delete('token');
                window.history.replaceState({}, '', url.toString());
            }
        } catch (_) {}
    }, []);

    useEffect(() => {
        // Extract UUID from query parameter
        const urlParams = new URLSearchParams(window.location.search);
        const uuid = urlParams.get('uuid');

        if (uuid) {
            axios.post('/api/user/products/show', { id: uuid })
                .then(response => {
                    if (response.data.status && response.data.data) {
                        const productData = response.data.data;
                        setProduct(productData);
                    } else {
                        console.error('Product not found or invalid response:', response.data);
                    }
                    setLoading(false);
                })
                .catch(error => {
                    console.error('Error fetching product:', error);
                    setLoading(false);
                });
        } else {
            setLoading(false);
        }
    }, []);

    // Update image when product loads or color changes
    useEffect(() => {
        if (product && product.media) {
            let images = product.media.filter(m => m.type === 'image');
            
            // Filter images by selected color if color is selected
            if (selectedColor) {
                const colorImages = images.filter(img => img.color === selectedColor);
                if (colorImages.length > 0) {
                    images = colorImages;
                } else {
                    // If no color-specific images, show general images (without color)
                    images = images.filter(img => !img.color || img.color === null);
                }
            } else {
                // If no color selected, show general images (without color) or all images
                const generalImages = images.filter(img => !img.color || img.color === null);
                if (generalImages.length > 0) {
                    images = generalImages;
                }
            }
            
            const primaryImage = images.find(img => img.is_primary);
            setCurrentImage(primaryImage ? primaryImage.url : (images[0]?.url || product.primary_image_url || product.image));
        } else if (product) {
            setCurrentImage(product.primary_image_url || product.image);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [product, selectedColor]);

    const handleAddToCart = () => {
        if (!product) return;
        
        setAddingToCart(true);
        setCartMessage('');

        // Get token from localStorage/cookies only (not URL)
        const token = localStorage.getItem('auth_token') || '';

        // Use UUID if available, otherwise use numeric ID
        const productId = product.uuid || product.id;

        const cartData = {
            product_id: productId,
            quantity: quantity
        };

        // Add size and color if selected
        if (selectedSize) {
            cartData.size = selectedSize;
        }
        if (selectedColor) {
            cartData.color = selectedColor;
        }

        axios.post('/api/user/cart/add', cartData, {
            headers: token ? {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            } : {
                'Content-Type': 'application/json'
            },
            withCredentials: true
        })
        .then(response => {
            if (response.data.status || response.data.success) {
                setCartMessage('Product added to cart successfully!');
                // Notify header to refresh cart count
                localStorage.setItem('cart_updated', Date.now().toString());
                setTimeout(() => setCartMessage(''), 3000);
            } else {
                setCartMessage(response.data.message || 'Failed to add product to cart');
            }
            setAddingToCart(false);
        })
        .catch(error => {
            console.error('Error adding to cart:', error);
            setCartMessage(error.response?.data?.message || 'Failed to add product to cart');
            setAddingToCart(false);
        });
    };

    const handleBuyNow = () => {
        if (!product) return;
        
        // Check if user is logged in
        if (!user) {
            const urlParams = new URLSearchParams(window.location.search);
            const uuid = urlParams.get('uuid');
            const redirectUrl = uuid ? `/product?uuid=${uuid}` : '/product';
            window.location.href = `/login?redirect=${encodeURIComponent(redirectUrl)}`;
            return;
        }
        
        setAddingToCart(true);
        setCartMessage('');

        // Get token from localStorage/cookies only (not URL)
        const token = localStorage.getItem('auth_token') || '';

        // Use UUID if available, otherwise use numeric ID
        const productId = product.uuid || product.id;

        const cartData = {
            product_id: productId,
            quantity: quantity
        };

        // Add size and color if selected
        if (selectedSize) {
            cartData.size = selectedSize;
        }
        if (selectedColor) {
            cartData.color = selectedColor;
        }

        axios.post('/api/user/cart/add', cartData, {
            headers: token ? {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            } : {
                'Content-Type': 'application/json'
            },
            withCredentials: true
        })
        .then(response => {
            setAddingToCart(false);
            if (response.data.status || response.data.success) {
                // Notify header to refresh cart count
                localStorage.setItem('cart_updated', Date.now().toString());
                // Redirect to cart page without token in URL
                window.location.href = '/cart';
            } else {
                setCartMessage(response.data.message || 'Failed to add product to cart');
            }
        })
        .catch(error => {
            console.error('Error adding to cart:', error);
            setCartMessage(error.response?.data?.message || 'Failed to add product to cart');
            setAddingToCart(false);
        });
    };

    if (loading) {
        const loadingContent = (
            <div className="min-h-screen bg-gray-50 flex items-center justify-center px-4">
                <div className="text-center">
                    <div className="text-indigo-600 text-lg sm:text-xl md:text-2xl">Loading product...</div>
                </div>
            </div>
        );

        return (
            <>
                <Head title="Loading..." />
                {user ? (
                    <UserLayout>
                        {loadingContent}
                    </UserLayout>
                ) : (
                    <GuestLayout>
                        {loadingContent}
                    </GuestLayout>
                )}
            </>
        );
    }

    if (!product) {
        const notFoundContent = (
            <div className="min-h-screen bg-gray-50 flex items-center justify-center px-4">
                <div className="text-center">
                    <div className="text-xl sm:text-2xl font-bold text-gray-900 mb-4">Product Not Found</div>
                    <Link
                        href="/products"
                        className="inline-flex items-center text-sm sm:text-base text-indigo-600 hover:text-indigo-500 font-medium touch-manipulation"
                    >
                        <span className="mr-1">←</span> Back to Products
                    </Link>
                </div>
            </div>
        );

        return (
            <>
                <Head title="Product Not Found" />
                {user ? (
                    <UserLayout>
                        {notFoundContent}
                    </UserLayout>
                ) : (
                    <GuestLayout>
                        {notFoundContent}
                    </GuestLayout>
                )}
            </>
        );
    }

    const productContent = (
        <div className="min-h-screen bg-gray-50">
            <div className="max-w-7xl mx-auto py-6 px-4 sm:py-8 sm:px-6 lg:py-12 lg:px-8">
                <div className="mb-4 sm:mb-6 md:mb-8">
                    <Link
                        href="/products"
                        className="inline-flex items-center text-sm sm:text-base text-indigo-600 hover:text-indigo-500 font-medium touch-manipulation"
                    >
                        <span className="mr-1">←</span> Back to Products
                    </Link>
                </div>

                <div className="bg-white rounded-lg shadow-lg overflow-hidden">
                    <div className="flex flex-col md:flex-row">
                        <div className="w-full md:w-1/2 relative">
                            <div className="aspect-w-16 aspect-h-9 bg-gray-200">
                                {currentImage ? (
                                    <img
                                        src={currentImage}
                                        alt={product.name}
                                        className="w-full h-64 sm:h-80 md:h-96 object-cover transition-opacity duration-300"
                                        onError={(e) => {
                                            e.target.src = '/images/placeholder.svg';
                                        }}
                                    />
                                ) : (
                                    <div className="w-full h-64 sm:h-80 md:h-96 bg-gray-200 flex items-center justify-center">
                                        <span className="text-gray-400 text-base sm:text-lg md:text-xl">No Image Available</span>
                                    </div>
                                )}
                            </div>
                            {/* Image Gallery */}
                            {product.media && product.media.filter(m => m.type === 'image').length > 1 && (
                                <div className="mt-4 flex gap-2 overflow-x-auto pb-2">
                                    {(() => {
                                        let images = product.media.filter(m => m.type === 'image');
                                        
                                        // Filter images by selected color if color is selected
                                        if (selectedColor) {
                                            const colorImages = images.filter(img => img.color === selectedColor);
                                            if (colorImages.length > 0) {
                                                images = colorImages;
                                            } else {
                                                // If no color-specific images, show general images (without color)
                                                images = images.filter(img => !img.color || img.color === null);
                                            }
                                        } else {
                                            // If no color selected, show general images (without color) or all images
                                            const generalImages = images.filter(img => !img.color || img.color === null);
                                            if (generalImages.length > 0) {
                                                images = generalImages;
                                            }
                                        }
                                        
                                        return images.slice(0, 6).map((img, index) => (
                                            <button
                                                key={img.id || index}
                                                type="button"
                                                onClick={() => setCurrentImage(img.url)}
                                                className={`flex-shrink-0 w-16 h-16 border-2 rounded-md overflow-hidden ${
                                                    currentImage === img.url 
                                                        ? 'border-indigo-600' 
                                                        : 'border-gray-300 hover:border-indigo-400'
                                                }`}
                                            >
                                                <img
                                                    src={img.url}
                                                    alt={`${product.name} - Image ${index + 1}`}
                                                    className="w-full h-full object-cover"
                                                    onError={(e) => {
                                                        e.target.src = '/images/placeholder.svg';
                                                    }}
                                                />
                                            </button>
                                        ));
                                    })()}
                                </div>
                            )}
                            
                            {/* Video Gallery - Color-specific videos */}
                            {product.media && product.media.filter(m => m.type === 'video').length > 0 && (
                                <div className="mt-4">
                                    <h3 className="text-sm font-medium text-gray-700 mb-2">Videos</h3>
                                    <div className="grid grid-cols-1 gap-2">
                                        {(() => {
                                            let videos = product.media.filter(m => m.type === 'video');
                                            
                                            // Filter videos by selected color if color is selected
                                            if (selectedColor) {
                                                const colorVideos = videos.filter(vid => vid.color === selectedColor);
                                                if (colorVideos.length > 0) {
                                                    videos = colorVideos;
                                                } else {
                                                    // If no color-specific videos, show general videos (without color)
                                                    videos = videos.filter(vid => !vid.color || vid.color === null);
                                                }
                                            } else {
                                                // If no color selected, show general videos (without color) or all videos
                                                const generalVideos = videos.filter(vid => !vid.color || vid.color === null);
                                                if (generalVideos.length > 0) {
                                                    videos = generalVideos;
                                                }
                                            }
                                            
                                            return videos.map((vid, index) => (
                                                <video
                                                    key={vid.id || index}
                                                    src={vid.url}
                                                    controls
                                                    className="w-full h-48 sm:h-64 object-cover rounded-md"
                                                >
                                                    Your browser does not support the video tag.
                                                </video>
                                            ));
                                        })()}
                                    </div>
                                </div>
                            )}
                            {product.discount_info && (
                                <div className="absolute top-4 right-4 bg-red-600 text-white px-3 py-2 rounded-lg text-sm sm:text-base font-bold shadow-lg">
                                    {product.discount_info.display_text}
                                </div>
                            )}
                        </div>
                        <div className="w-full md:w-1/2 p-4 sm:p-6 md:p-8">
                            <h1 className="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900 mb-3 sm:mb-4">
                                {product.name}
                            </h1>
                            
                            <div className="mb-4 sm:mb-6">
                                {product.discount_info ? (
                                    <div className="flex flex-col">
                                        <span className="text-lg sm:text-xl text-gray-400 line-through">
                                            ${product.discount_info.original_price}
                                        </span>
                                        <span className="text-3xl sm:text-4xl md:text-5xl font-bold text-red-600">
                                            ${product.discount_info.final_price}
                                        </span>
                                        <span className="text-sm sm:text-base text-green-600 font-medium mt-1">
                                            Save ${product.discount_info.discount_amount.toFixed(2)} ({product.discount_info.discount_percentage}% OFF)
                                        </span>
                                    </div>
                                ) : (
                                    <div className="flex flex-col">
                                        {product.sale_price ? (
                                            <>
                                                <span className="text-lg sm:text-xl text-gray-400 line-through">
                                                    ${product.price}
                                                </span>
                                                <span className="text-3xl sm:text-4xl md:text-5xl font-bold text-red-600">
                                                    ${product.sale_price}
                                                </span>
                                            </>
                                        ) : (
                                            <span className="text-3xl sm:text-4xl md:text-5xl font-bold text-indigo-600">
                                                ${product.price}
                                            </span>
                                        )}
                                    </div>
                                )}
                            </div>

                            <div className="mb-6 sm:mb-8">
                                <h2 className="text-base sm:text-lg font-semibold text-gray-900 mb-2 sm:mb-3">Description</h2>
                                <p className="text-sm sm:text-base text-gray-600 leading-relaxed">
                                    {product.description}
                                </p>
                            </div>


                            {/* Size Selection - Show only sizes available for selected color */}
                            {availableSizes && Array.isArray(availableSizes) && availableSizes.length > 0 && (
                                <div className="mb-4">
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Size {selectedSize ? `: ${selectedSize}` : ''}
                                        {selectedColor && (
                                            <span className="text-xs text-gray-500 ml-2">
                                                (Available for {selectedColor})
                                            </span>
                                        )}
                                    </label>
                                    <div className="flex flex-wrap gap-2">
                                        {availableSizes.map((size) => (
                                            <button
                                                key={size}
                                                type="button"
                                                onClick={() => setSelectedSize(size)}
                                                className={`px-4 py-2 rounded-md text-sm font-medium transition-colors ${
                                                    selectedSize === size
                                                        ? 'bg-indigo-600 text-white hover:bg-indigo-700'
                                                        : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                                                }`}
                                            >
                                                {size}
                                            </button>
                                        ))}
                                    </div>
                                    {selectedColor && availableSizes.length === 0 && (
                                        <p className="text-sm text-gray-500 mt-2">
                                            No sizes available for {selectedColor}
                                        </p>
                                    )}
                                </div>
                            )}

                            {/* Color Selection */}
                            {product.colors && Array.isArray(product.colors) && product.colors.length > 0 && (
                                <div className="mb-4">
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Color {selectedColor ? `: ${selectedColor}` : ''}
                                    </label>
                                    <div className="flex flex-wrap gap-2">
                                        {product.colors.map((color) => {
                                            const sizesForColor = getAvailableSizesForColor(color);
                                            return (
                                                <button
                                                    key={color}
                                                    type="button"
                                                    onClick={() => {
                                                        setSelectedColor(color);
                                                        // Reset size when color changes
                                                        setSelectedSize(null);
                                                    }}
                                                    className={`px-4 py-2 rounded-md text-sm font-medium transition-colors ${
                                                        selectedColor === color
                                                            ? 'bg-indigo-600 text-white hover:bg-indigo-700'
                                                            : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                                                    }`}
                                                    title={sizesForColor.length > 0 ? `Available sizes: ${sizesForColor.join(', ')}` : 'No sizes available'}
                                                >
                                                    {color}
                                                </button>
                                            );
                                        })}
                                    </div>
                                </div>
                            )}

                            <div className="mb-4">
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Quantity
                                    {stockQuantity > 0 && (
                                        <span className="text-xs text-gray-500 ml-2">
                                            (Max: {stockQuantity})
                                        </span>
                                    )}
                                </label>
                                <div className="flex items-center gap-2">
                                    <button
                                        type="button"
                                        onClick={() => setQuantity(Math.max(1, quantity - 1))}
                                        disabled={quantity <= 1 || !isInStock}
                                        className="px-3 py-2 border border-gray-300 rounded-md bg-white text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        -
                                    </button>
                                    <input
                                        type="number"
                                        min="1"
                                        max={stockQuantity > 0 ? stockQuantity : undefined}
                                        value={quantity}
                                        onChange={(e) => {
                                            const newQty = parseInt(e.target.value) || 1;
                                            const maxQty = stockQuantity > 0 ? stockQuantity : 9999;
                                            setQuantity(Math.max(1, Math.min(newQty, maxQty)));
                                        }}
                                        disabled={!isInStock}
                                        className="w-20 px-3 py-2 border border-gray-300 rounded-md text-center disabled:opacity-50 disabled:cursor-not-allowed"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => {
                                            const maxQty = stockQuantity > 0 ? stockQuantity : 9999;
                                            setQuantity(Math.min(maxQty, quantity + 1));
                                        }}
                                        disabled={!isInStock || (stockQuantity > 0 && quantity >= stockQuantity)}
                                        className="px-3 py-2 border border-gray-300 rounded-md bg-white text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        +
                                    </button>
                                </div>
                                {stockQuantity > 0 && quantity > stockQuantity && (
                                    <p className="text-sm text-red-600 mt-1">
                                        Maximum available quantity is {stockQuantity}
                                    </p>
                                )}
                            </div>

                            {cartMessage && (
                                <div className={`mb-4 p-3 rounded-md ${cartMessage.includes('success') ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'}`}>
                                    {cartMessage}
                                </div>
                            )}

                            {/* Stock Status - Only show when out of stock */}
                            {stockQuantity === 0 && (
                                <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-md">
                                    <p className="text-sm text-red-800 font-semibold">
                                        Out of Stock
                                    </p>
                                </div>
                            )}

                            <div className="flex flex-col sm:flex-row gap-3 sm:gap-4">
                                <Button 
                                    className="flex-1 sm:flex-none"
                                    onClick={handleAddToCart}
                                    disabled={addingToCart || !isInStock || (availableSizes && availableSizes.length > 0 && !selectedSize)}
                                >
                                    {addingToCart ? 'Adding...' : isInStock ? 'Add to Cart' : 'Out of Stock'}
                                </Button>
                                <Button 
                                    variant="outline"
                                    className="flex-1 sm:flex-none"
                                    onClick={handleBuyNow}
                                    disabled={addingToCart || !isInStock || (availableSizes && availableSizes.length > 0 && !selectedSize)}
                                >
                                    {addingToCart ? 'Adding...' : isInStock ? 'Buy Now' : 'Out of Stock'}
                                </Button>
                            </div>
                            {(availableSizes && availableSizes.length > 0 && !selectedSize) && (
                                <p className="text-sm text-red-600 mt-2">Please select a size</p>
                            )}
                            {selectedColor && availableSizes.length === 0 && (
                                <p className="text-sm text-orange-600 mt-2">No sizes available for {selectedColor}. Please select a different color.</p>
                            )}
                            {!isInStock && (selectedSize || selectedColor || (!product.variations || product.variations.length === 0)) && (
                                <p className="text-sm text-red-600 mt-2">
                                    {selectedSize && selectedColor 
                                        ? `This ${selectedSize} size in ${selectedColor} color is currently out of stock.`
                                        : selectedColor
                                        ? `This ${selectedColor} color is currently out of stock.`
                                        : selectedSize
                                        ? `This ${selectedSize} size is currently out of stock.`
                                        : 'This product is currently out of stock.'}
                                </p>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );

    return (
        <>
            <Head title={product.name} />
            {user ? (
                <UserLayout>
                    {productContent}
                </UserLayout>
            ) : (
                <GuestLayout>
                    {productContent}
                </GuestLayout>
            )}
        </>
    );
}

