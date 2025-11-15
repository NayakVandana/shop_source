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
                        setProduct(response.data.data);
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

    const handleAddToCart = () => {
        if (!product) return;
        
        setAddingToCart(true);
        setCartMessage('');

        // Get token from localStorage/cookies only (not URL)
        const token = localStorage.getItem('auth_token') || '';

        axios.post('/api/user/cart/add', {
            product_id: product.id,
            quantity: quantity
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

        axios.post('/api/user/cart/add', {
            product_id: product.id,
            quantity: quantity
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
                                {product.primary_image_url || product.image ? (
                                    <img
                                        src={product.primary_image_url || product.image}
                                        alt={product.name}
                                        className="w-full h-64 sm:h-80 md:h-96 object-cover"
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
                                    disabled={addingToCart || !product.in_stock}
                                >
                                    {addingToCart ? 'Adding...' : 'Add to Cart'}
                                </Button>
                                <Button 
                                    variant="outline"
                                    className="flex-1 sm:flex-none"
                                    onClick={handleBuyNow}
                                    disabled={addingToCart || !product.in_stock}
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

