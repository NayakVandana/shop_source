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
    const [selectedSize, setSelectedSize] = useState(null);
    const [selectedColor, setSelectedColor] = useState(null);
    const [currentImage, setCurrentImage] = useState(null);
    const [addingToCart, setAddingToCart] = useState(false);
    const [cartMessage, setCartMessage] = useState('');

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
                        // Debug: Log sizes to console
                        if (productData.sizes) {
                            console.log('Product sizes loaded:', productData.sizes);
                        } else {
                            console.log('Product has no sizes property');
                        }
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

    // Auto-select first available size when product loads
    useEffect(() => {
        if (product && product.sizes && product.sizes.length > 0) {
            // If no size is selected, try to auto-select first available size
            if (!selectedSize) {
                const firstAvailableSize = product.sizes.find(
                    (sizeItem) => sizeItem.is_active && sizeItem.stock_quantity > 0
                );
                if (firstAvailableSize) {
                    setSelectedSize(firstAvailableSize.size);
                }
            }
            // If a size is selected, verify it's still available
            else {
                const currentSize = product.sizes.find(s => s.size === selectedSize);
                if (!currentSize || !currentSize.is_active || currentSize.stock_quantity === 0) {
                    // Current size is no longer available, find a new one
                    const firstAvailableSize = product.sizes.find(
                        (sizeItem) => sizeItem.is_active && sizeItem.stock_quantity > 0
                    );
                    if (firstAvailableSize) {
                        setSelectedSize(firstAvailableSize.size);
                    } else {
                        // No available sizes, clear selection
                        setSelectedSize(null);
                    }
                }
            }
        } else {
            // Product has no sizes, clear selection
            setSelectedSize(null);
        }

        // Auto-select first available color when product loads
        if (product && product.colors && product.colors.length > 0) {
            // If no color is selected, try to auto-select first available color
            if (!selectedColor) {
                const firstAvailableColor = product.colors.find(
                    (colorItem) => colorItem.is_active && colorItem.stock_quantity > 0
                );
                if (firstAvailableColor) {
                    setSelectedColor(firstAvailableColor.color);
                }
            }
            // If a color is selected, verify it's still available
            else {
                const currentColor = product.colors.find(c => c.color === selectedColor);
                if (!currentColor || !currentColor.is_active || currentColor.stock_quantity === 0) {
                    // Current color is no longer available, find a new one
                    const firstAvailableColor = product.colors.find(
                        (colorItem) => colorItem.is_active && colorItem.stock_quantity > 0
                    );
                    if (firstAvailableColor) {
                        setSelectedColor(firstAvailableColor.color);
                    } else {
                        // No available colors, clear selection
                        setSelectedColor(null);
                    }
                }
            }
        } else {
            // Product has no colors, clear selection
            setSelectedColor(null);
        }

        // Update image based on selected color
        if (product && product.media) {
            const images = product.media.filter(m => m.type === 'image');
            if (selectedColor && images.length > 0) {
                // Try to find image for selected color
                const colorImage = images.find(img => img.color && img.color.toLowerCase() === selectedColor.toLowerCase());
                if (colorImage) {
                    setCurrentImage(colorImage.url);
                } else {
                    // Fallback to primary image or first image
                    const primaryImage = images.find(img => img.is_primary);
                    setCurrentImage(primaryImage ? primaryImage.url : (images[0]?.url || product.primary_image_url || product.image));
                }
            } else {
                // No color selected or no images, use primary/default
                const primaryImage = images.find(img => img.is_primary);
                setCurrentImage(primaryImage ? primaryImage.url : (images[0]?.url || product.primary_image_url || product.image));
            }
        } else if (product) {
            setCurrentImage(product.primary_image_url || product.image);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [product, selectedColor]);

    const handleAddToCart = () => {
        if (!product) return;
        
        // Check if product has sizes
        const hasSizes = product.sizes && Array.isArray(product.sizes) && product.sizes.length > 0;
        
        // If product has sizes, size is REQUIRED
        if (hasSizes) {
            if (!selectedSize || selectedSize === null || selectedSize === '') {
                setCartMessage('Please select a size before adding to cart');
                setAddingToCart(false);
                return;
            }
            
            // Verify the selected size exists in the product sizes
            const sizeExists = product.sizes.some(s => s.size === selectedSize);
            if (!sizeExists) {
                setCartMessage('Selected size is not available. Please select a different size.');
                setAddingToCart(false);
                return;
            }
        }

        // Check if product has colors
        const hasColors = product.colors && Array.isArray(product.colors) && product.colors.length > 0;
        
        // If product has colors, color is REQUIRED
        if (hasColors) {
            if (!selectedColor || selectedColor === null || selectedColor === '') {
                setCartMessage('Please select a color before adding to cart');
                setAddingToCart(false);
                return;
            }
            
            // Verify the selected color exists in the product colors
            const colorExists = product.colors.some(c => c.color === selectedColor);
            if (!colorExists) {
                setCartMessage('Selected color is not available. Please select a different color.');
                setAddingToCart(false);
                return;
            }
        }
        
        setAddingToCart(true);
        setCartMessage('');

        // Get token from localStorage/cookies only (not URL)
        const token = localStorage.getItem('auth_token') || '';

        // Use UUID if available, otherwise use numeric ID
        const productId = product.uuid || product.id;

        // Final check: never send null size if product has sizes
        const sizeToSend = hasSizes ? (selectedSize || null) : null;
        const colorToSend = hasColors ? (selectedColor || null) : null;
        
        if (hasSizes && !sizeToSend) {
            setCartMessage('Please select a size before adding to cart');
            setAddingToCart(false);
            return;
        }

        if (hasColors && !colorToSend) {
            setCartMessage('Please select a color before adding to cart');
            setAddingToCart(false);
            return;
        }

        axios.post('/api/user/cart/add', {
            product_id: productId,
            quantity: quantity,
            size: sizeToSend,
            color: colorToSend
        }, {
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
        
        // Check if product has sizes and size is required
        if (product.sizes && product.sizes.length > 0 && !selectedSize) {
            setCartMessage('Please select a size');
            return;
        }

        // Check if product has colors and color is required
        if (product.colors && product.colors.length > 0 && !selectedColor) {
            setCartMessage('Please select a color');
            return;
        }
        
        // Check if user is logged in
        if (!user) {
            const urlParams = new URLSearchParams(window.location.search);
            const uuid = urlParams.get('uuid');
            const redirectUrl = uuid ? `/product?uuid=${uuid}` : '/product';
            window.location.href = `/login?redirect=${encodeURIComponent(redirectUrl)}`;
            return;
        }
        
        // Check if product has sizes
        const hasSizes = product.sizes && Array.isArray(product.sizes) && product.sizes.length > 0;
        
        // If product has sizes, size is REQUIRED
        if (hasSizes) {
            if (!selectedSize || selectedSize === null || selectedSize === '') {
                setCartMessage('Please select a size before buying');
                setAddingToCart(false);
                return;
            }
            
            // Verify the selected size exists in the product sizes
            const sizeExists = product.sizes.some(s => s.size === selectedSize);
            if (!sizeExists) {
                setCartMessage('Selected size is not available. Please select a different size.');
                setAddingToCart(false);
                return;
            }
        }

        // Check if product has colors
        const hasColors = product.colors && Array.isArray(product.colors) && product.colors.length > 0;
        
        // If product has colors, color is REQUIRED
        if (hasColors) {
            if (!selectedColor || selectedColor === null || selectedColor === '') {
                setCartMessage('Please select a color before buying');
                setAddingToCart(false);
                return;
            }
            
            // Verify the selected color exists in the product colors
            const colorExists = product.colors.some(c => c.color === selectedColor);
            if (!colorExists) {
                setCartMessage('Selected color is not available. Please select a different color.');
                setAddingToCart(false);
                return;
            }
        }
        
        setAddingToCart(true);
        setCartMessage('');

        // Get token from localStorage/cookies only (not URL)
        const token = localStorage.getItem('auth_token') || '';

        // Use UUID if available, otherwise use numeric ID
        const productId = product.uuid || product.id;

        // Final check: never send null size if product has sizes
        const sizeToSend = hasSizes ? (selectedSize || null) : null;
        const colorToSend = hasColors ? (selectedColor || null) : null;
        
        if (hasSizes && !sizeToSend) {
            setCartMessage('Please select a size before buying');
            setAddingToCart(false);
            return;
        }

        if (hasColors && !colorToSend) {
            setCartMessage('Please select a color before buying');
            setAddingToCart(false);
            return;
        }

        axios.post('/api/user/cart/add', {
            product_id: productId,
            quantity: quantity,
            size: sizeToSend,
            color: colorToSend
        }, {
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
                            {/* Image Gallery - Show all images for selected color or all images */}
                            {product.media && product.media.filter(m => m.type === 'image').length > 1 && (
                                <div className="mt-4 flex gap-2 overflow-x-auto pb-2">
                                    {(() => {
                                        const images = product.media.filter(m => m.type === 'image');
                                        // Filter images by selected color if color is selected
                                        const filteredImages = selectedColor 
                                            ? images.filter(img => !img.color || img.color.toLowerCase() === selectedColor.toLowerCase())
                                            : images;
                                        // If no color-specific images, show all images
                                        const displayImages = filteredImages.length > 0 ? filteredImages : images;
                                        
                                        return displayImages.slice(0, 6).map((img, index) => (
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

                            {/* Size Selection */}
                            {product.sizes && Array.isArray(product.sizes) && product.sizes.length > 0 && (
                                <div className="mb-4">
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Size {!selectedSize && <span className="text-red-500">*</span>}
                                    </label>
                                    <div className="flex flex-wrap gap-2">
                                        {product.sizes.map((sizeItem) => {
                                            const isInStock = sizeItem.is_active && sizeItem.stock_quantity > 0;
                                            const isSelected = selectedSize === sizeItem.size;
                                            
                                            return (
                                                <button
                                                    key={sizeItem.id || sizeItem.size}
                                                    type="button"
                                                    onClick={() => {
                                                        if (isInStock) {
                                                            setSelectedSize(sizeItem.size);
                                                            setCartMessage('');
                                                        }
                                                    }}
                                                    disabled={!isInStock}
                                                    className={`
                                                        px-4 py-2 border-2 rounded-md text-sm font-medium transition-all
                                                        ${isSelected 
                                                            ? 'border-indigo-600 bg-indigo-600 text-white' 
                                                            : isInStock
                                                            ? 'border-gray-300 bg-white text-gray-700 hover:border-indigo-500 hover:bg-indigo-50'
                                                            : 'border-gray-200 bg-gray-100 text-gray-400 cursor-not-allowed opacity-50'
                                                        }
                                                    `}
                                                    title={!isInStock ? 'Out of stock' : `Stock: ${sizeItem.stock_quantity}`}
                                                >
                                                    {sizeItem.size}
                                                    {!isInStock && <span className="ml-1 text-xs">(Out of Stock)</span>}
                                                </button>
                                            );
                                        })}
                                    </div>
                                    {selectedSize && (
                                        <p className="mt-2 text-sm text-gray-600">
                                            Selected: <span className="font-semibold">{selectedSize}</span>
                                        </p>
                                    )}
                                    {!selectedSize && product.sizes.every(size => !size.is_active || size.stock_quantity === 0) && (
                                        <p className="mt-2 text-sm text-red-600">
                                            All sizes are currently out of stock
                                        </p>
                                    )}
                                </div>
                            )}

                            {/* Color Selection */}
                            {product.colors && Array.isArray(product.colors) && product.colors.length > 0 && (
                                <div className="mb-4">
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Color {!selectedColor && <span className="text-red-500">*</span>}
                                    </label>
                                    <div className="flex flex-wrap gap-2">
                                        {product.colors.map((colorItem) => {
                                            const isInStock = colorItem.is_active && colorItem.stock_quantity > 0;
                                            const isSelected = selectedColor === colorItem.color;
                                            
                                            return (
                                                <button
                                                    key={colorItem.id || colorItem.color}
                                                    type="button"
                                                    onClick={() => {
                                                        if (isInStock) {
                                                            setSelectedColor(colorItem.color);
                                                            setCartMessage('');
                                                            
                                                            // Update image when color is selected
                                                            if (product && product.media) {
                                                                const images = product.media.filter(m => m.type === 'image');
                                                                const colorImage = images.find(img => 
                                                                    img.color && img.color.toLowerCase() === colorItem.color.toLowerCase()
                                                                );
                                                                if (colorImage) {
                                                                    setCurrentImage(colorImage.url);
                                                                } else {
                                                                    // Fallback to primary image or first image
                                                                    const primaryImage = images.find(img => img.is_primary);
                                                                    setCurrentImage(primaryImage ? primaryImage.url : (images[0]?.url || product.primary_image_url || product.image));
                                                                }
                                                            }
                                                        }
                                                    }}
                                                    disabled={!isInStock}
                                                    className={`
                                                        px-4 py-2 border-2 rounded-md text-sm font-medium transition-all relative
                                                        ${isSelected 
                                                            ? 'border-indigo-600 bg-indigo-600 text-white' 
                                                            : isInStock
                                                            ? 'border-gray-300 bg-white text-gray-700 hover:border-indigo-500 hover:bg-indigo-50'
                                                            : 'border-gray-200 bg-gray-100 text-gray-400 cursor-not-allowed opacity-50'
                                                        }
                                                    `}
                                                    title={!isInStock ? 'Out of stock' : `Stock: ${colorItem.stock_quantity}`}
                                                >
                                                    {colorItem.color_code && (
                                                        <span 
                                                            className="inline-block w-4 h-4 rounded-full mr-2 border border-gray-300"
                                                            style={{ backgroundColor: colorItem.color_code }}
                                                        />
                                                    )}
                                                    {colorItem.color}
                                                    {!isInStock && <span className="ml-1 text-xs">(Out of Stock)</span>}
                                                </button>
                                            );
                                        })}
                                    </div>
                                    {selectedColor && (
                                        <p className="mt-2 text-sm text-gray-600">
                                            Selected: <span className="font-semibold">{selectedColor}</span>
                                        </p>
                                    )}
                                    {!selectedColor && product.colors.every(color => !color.is_active || color.stock_quantity === 0) && (
                                        <p className="mt-2 text-sm text-red-600">
                                            All colors are currently out of stock
                                        </p>
                                    )}
                                </div>
                            )}

                            <div className="mb-4">
                                <label className="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
                                <div className="flex items-center gap-2">
                                    <button
                                        type="button"
                                        onClick={() => setQuantity(Math.max(1, quantity - 1))}
                                        className="px-3 py-2 border border-gray-300 rounded-md bg-white text-gray-700 hover:bg-gray-50"
                                    >
                                        -
                                    </button>
                                    <input
                                        type="number"
                                        min="1"
                                        value={quantity}
                                        onChange={(e) => setQuantity(Math.max(1, parseInt(e.target.value) || 1))}
                                        className="w-20 px-3 py-2 border border-gray-300 rounded-md text-center"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setQuantity(quantity + 1)}
                                        className="px-3 py-2 border border-gray-300 rounded-md bg-white text-gray-700 hover:bg-gray-50"
                                    >
                                        +
                                    </button>
                                </div>
                            </div>

                            {cartMessage && (
                                <div className={`mb-4 p-3 rounded-md ${cartMessage.includes('success') ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'}`}>
                                    {cartMessage}
                                </div>
                            )}

                            <div className="flex flex-col sm:flex-row gap-3 sm:gap-4">
                                <Button 
                                    className="flex-1 sm:flex-none"
                                    onClick={handleAddToCart}
                                    disabled={
                                        addingToCart || 
                                        !product.in_stock || 
                                        (product.sizes && Array.isArray(product.sizes) && product.sizes.length > 0 && (!selectedSize || selectedSize === null || selectedSize === '')) ||
                                        (product.colors && Array.isArray(product.colors) && product.colors.length > 0 && (!selectedColor || selectedColor === null || selectedColor === ''))
                                    }
                                >
                                    {addingToCart ? 'Adding...' : 'Add to Cart'}
                                </Button>
                                <Button 
                                    variant="outline"
                                    className="flex-1 sm:flex-none"
                                    onClick={handleBuyNow}
                                    disabled={
                                        addingToCart || 
                                        !product.in_stock || 
                                        (product.sizes && Array.isArray(product.sizes) && product.sizes.length > 0 && (!selectedSize || selectedSize === null || selectedSize === '')) ||
                                        (product.colors && Array.isArray(product.colors) && product.colors.length > 0 && (!selectedColor || selectedColor === null || selectedColor === ''))
                                    }
                                >
                                    {addingToCart ? 'Adding...' : 'Buy Now'}
                                </Button>
                            </div>
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

