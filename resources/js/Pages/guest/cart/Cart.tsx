// @ts-nocheck
import React, { useState, useEffect } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import axios from 'axios';
import UserLayout from '../../../Layouts/UserLayout';
import GuestLayout from '../../../Layouts/GuestLayout';
import Button from '../../../Components/ui/Button';
import { Heading, Text } from '../../../Components/ui/Typography';
import Card from '../../../Components/ui/Card';

export default function Cart() {
    const { auth } = usePage().props;
    const user = auth.user;
    const [cart, setCart] = useState(null);
    const [loading, setLoading] = useState(true);
    const [updating, setUpdating] = useState({});
    const [error, setError] = useState(null);

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
                // Remove token from URL immediately
                url.searchParams.delete('token');
                window.history.replaceState({}, '', url.toString());
            }
        } catch (_) {}
    }, []);

    // API config helper - works for both web and mobile
    const getApiConfig = (options = {}) => {
        const { token: providedToken = null, tokenType = 'user' } = options;
        
        // Detect if running in web browser or mobile app
        const isWebBrowser = typeof window !== 'undefined' && typeof document !== 'undefined';
        
        // Get token from localStorage/cookies only (not URL)
        let token = providedToken;
        if (!token && isWebBrowser) {
            // Try localStorage first
            const storageKey = tokenType === 'admin' ? 'admin_token' : 'auth_token';
            try {
                token = localStorage.getItem(storageKey) || null;
            } catch (e) {
                token = null;
            }
            
            // Then try cookie (for persistent authentication)
            if (!token) {
                try {
                    const cookieToken = document.cookie
                        .split(';')
                        .find(c => {
                            const cookieName = tokenType === 'admin' ? 'admin_token' : 'auth_token';
                            return c.trim().startsWith(`${cookieName}=`);
                        });
                    if (cookieToken) {
                        token = cookieToken.split('=')[1]?.trim() || null;
                    }
                } catch (e) {
                    token = null;
                }
            }
        }
        
        // Build headers
        const headers = { 'Content-Type': 'application/json' };
        if (token) {
            if (tokenType === 'admin') {
                headers['AdminToken'] = token;
            } else {
                headers['Authorization'] = `Bearer ${token}`;
            }
        }
        
        // Build config - withCredentials only for web browsers
        return {
            headers,
            ...(isWebBrowser ? { withCredentials: true } : {})
        };
    };

    useEffect(() => {
        loadCart();

        // Listen for cart updates from other tabs/windows
        const handleStorageChange = (e) => {
            if (e.key === 'cart_updated') {
                loadCart();
            }
        };

        window.addEventListener('storage', handleStorageChange);

        return () => {
            window.removeEventListener('storage', handleStorageChange);
        };
    }, []);

    const loadCart = () => {
        setLoading(true);
        setError(null);

        axios.post('/api/user/cart/index', {}, getApiConfig())
        .then(response => {
            if (response.data.status || response.data.success) {
                const cartData = response.data.data?.cart || response.data.data;
                // Ensure items is always an array
                if (cartData) {
                    if (!Array.isArray(cartData.items)) {
                        cartData.items = cartData.items || [];
                    }
                    setCart(cartData);
                } else {
                    // If no cart data, set empty cart
                    setCart({ items: [] });
                }
                // Notify other tabs/windows
                localStorage.setItem('cart_updated', Date.now().toString());
            } else {
                setError(response.data.message || 'Failed to load cart');
                setCart({ items: [] }); // Set empty cart on error
            }
            setLoading(false);
        })
        .catch(error => {
            console.error('Error loading cart:', error);
            setError(error.response?.data?.message || 'Failed to load cart');
            setCart({ items: [] }); // Set empty cart on error
            setLoading(false);
        });
    };

    const handleUpdateQuantity = (cartItemId, newQuantity) => {
        if (newQuantity < 1) {
            handleRemoveItem(cartItemId);
            return;
        }

        setUpdating(prev => ({ ...prev, [cartItemId]: true }));

        axios.post('/api/user/cart/update', {
            cart_item_id: cartItemId,
            quantity: newQuantity
        }, getApiConfig())
        .then(response => {
            if (response.data.status || response.data.success) {
                loadCart();
            } else {
                alert(response.data.message || 'Failed to update cart');
            }
            setUpdating(prev => {
                const newState = { ...prev };
                delete newState[cartItemId];
                return newState;
            });
        })
        .catch(error => {
            console.error('Error updating cart:', error);
            alert(error.response?.data?.message || 'Failed to update cart');
            setUpdating(prev => {
                const newState = { ...prev };
                delete newState[cartItemId];
                return newState;
            });
        });
    };

    const handleRemoveItem = (cartItemId) => {
        if (!confirm('Are you sure you want to remove this item from your cart?')) {
            return;
        }

        setUpdating(prev => ({ ...prev, [cartItemId]: true }));

        axios.post('/api/user/cart/remove', {
            cart_item_id: cartItemId
        }, getApiConfig())
        .then(response => {
            if (response.data.status || response.data.success) {
                loadCart();
            } else {
                alert(response.data.message || 'Failed to remove item');
            }
            setUpdating(prev => {
                const newState = { ...prev };
                delete newState[cartItemId];
                return newState;
            });
        })
        .catch(error => {
            console.error('Error removing item:', error);
            alert(error.response?.data?.message || 'Failed to remove item');
            setUpdating(prev => {
                const newState = { ...prev };
                delete newState[cartItemId];
                return newState;
            });
        });
    };

    const handleClearCart = () => {
        if (!confirm('Are you sure you want to clear your cart?')) {
            return;
        }

        axios.post('/api/user/cart/clear', {}, getApiConfig())
        .then(response => {
            if (response.data.status || response.data.success) {
                loadCart();
            } else {
                alert(response.data.message || 'Failed to clear cart');
            }
        })
        .catch(error => {
            console.error('Error clearing cart:', error);
            alert(error.response?.data?.message || 'Failed to clear cart');
        });
    };

    const renderContent = () => {
        if (loading) {
            return (
                <div className="min-h-screen bg-gray-50 flex items-center justify-center px-4">
                    <div className="text-center">
                        <div className="text-indigo-600 text-lg sm:text-xl md:text-2xl">Loading cart...</div>
                    </div>
                </div>
            );
        }

        if (error) {
            return (
                <div className="min-h-screen bg-gray-50 flex items-center justify-center px-4">
                    <div className="text-center">
                        <div className="text-lg sm:text-xl md:text-2xl font-bold text-red-600 mb-4">{error}</div>
                        <Button onClick={loadCart}>Try Again</Button>
                    </div>
                </div>
            );
        }

        if (!cart || !Array.isArray(cart.items) || cart.items.length === 0) {
            return (
                <div className="min-h-screen bg-gray-50">
                    <div className="max-w-7xl mx-auto py-6 px-4 sm:py-8 sm:px-6 lg:py-12 lg:px-8">
                        <Heading level={1} className="text-2xl sm:text-3xl md:text-4xl mb-6">Shopping Cart</Heading>
                        <Card className="p-8 text-center">
                            <Text className="text-lg mb-4">Your cart is empty</Text>
                            <div className="flex flex-col sm:flex-row gap-3 justify-center">
                                <Link href="/products">
                                    <Button>Continue Shopping</Button>
                                </Link>
                                {!user && (
                                    <Link href="/login?redirect=/cart">
                                        <Button variant="outline">Login to Save Cart</Button>
                                    </Link>
                                )}
                            </div>
                        </Card>
                    </div>
                </div>
            );
        }

        const subtotal = cart.subtotal || cart.items.reduce((sum, item) => {
            const itemPrice = (item.price || 0) - (item.discount_amount || 0);
            return sum + (itemPrice * (item.quantity || 0));
        }, 0);
        const totalDiscount = cart.total_discount || cart.items.reduce((sum, item) => {
            return sum + ((item.discount_amount || 0) * (item.quantity || 0));
        }, 0);
        const total = subtotal;

        return (
            <div className="min-h-screen bg-gray-50">
                <div className="max-w-7xl mx-auto py-6 px-4 sm:py-8 sm:px-6 lg:py-12 lg:px-8">
                    <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                        <Heading level={1} className="text-2xl sm:text-3xl md:text-4xl">Shopping Cart</Heading>
                        <div className="flex gap-2">
                            {!user && (
                                <Link href={`/login?redirect=/cart`}>
                                    <Button size="sm">Login to Checkout</Button>
                                </Link>
                            )}
                            {cart.items.length > 0 && (
                                <Button variant="outline" size="sm" onClick={handleClearCart}>
                                    Clear Cart
                                </Button>
                            )}
                        </div>
                    </div>

                    {!user && cart.items.length > 0 && (
                        <Card className="mb-6 p-4 bg-blue-50 border-blue-200">
                            <div className="flex items-center justify-between">
                                <div>
                                    <Text className="font-semibold text-blue-900 mb-1">Guest Shopping</Text>
                                    <Text size="sm" className="text-blue-700">
                                        Login to save your cart and proceed to checkout
                                    </Text>
                                </div>
                                <Link href={`/login?redirect=/cart`}>
                                    <Button size="sm">Login</Button>
                                </Link>
                            </div>
                        </Card>
                    )}

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Cart Items */}
                        <div className="lg:col-span-2 space-y-4">
                            {cart.items.map((item) => {
                                const product = item.product;
                                const itemPrice = (item.price || 0) - (item.discount_amount || 0);
                                const itemSubtotal = itemPrice * (item.quantity || 0);

                                return (
                                    <Card key={item.id} className="p-4 sm:p-6">
                                        <div className="flex flex-col sm:flex-row gap-4">
                                            {/* Product Image */}
                                            <div className="flex-shrink-0">
                                                <Link href={`/product?uuid=${product?.uuid || product?.id}`}>
                                                    <img
                                                        src={product?.primary_image_url || product?.image || '/images/placeholder.svg'}
                                                        alt={product?.name || 'Product'}
                                                        className="w-24 h-24 sm:w-32 sm:h-32 object-cover rounded-md"
                                                        onError={(e) => {
                                                            e.target.src = '/images/placeholder.svg';
                                                        }}
                                                    />
                                                </Link>
                                            </div>

                                            {/* Product Details */}
                                            <div className="flex-1">
                                                <Link href={`/product?uuid=${product?.uuid || product?.id}`}>
                                                    <Heading level={3} className="text-lg sm:text-xl mb-2 hover:text-indigo-600">
                                                        {product?.name || 'Product'}
                                                    </Heading>
                                                </Link>
                                                <Text size="sm" className="text-gray-600 mb-2 line-clamp-2">
                                                    {product?.short_description || product?.description || ''}
                                                </Text>


                                                {/* Price and Quantity */}
                                                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mt-4">
                                                    <div className="flex flex-col">
                                                        {item.discount_amount > 0 ? (
                                                            <>
                                                                <span className="text-sm text-gray-400 line-through">
                                                                    ${((item.price || 0) * (item.quantity || 0)).toFixed(2)}
                                                                </span>
                                                                <span className="text-lg font-bold text-red-600">
                                                                    ${itemSubtotal.toFixed(2)}
                                                                </span>
                                                            </>
                                                        ) : (
                                                            <span className="text-lg font-bold text-indigo-600">
                                                                ${itemSubtotal.toFixed(2)}
                                                            </span>
                                                        )}
                                                        <Text size="xs" className="text-gray-500">
                                                            ${itemPrice.toFixed(2)} each
                                                        </Text>
                                                    </div>

                                                    {/* Quantity Controls */}
                                                    <div className="flex items-center gap-2">
                                                        <button
                                                            type="button"
                                                            onClick={() => handleUpdateQuantity(item.id, (item.quantity || 1) - 1)}
                                                            disabled={updating[item.id]}
                                                            className="px-3 py-2 border border-gray-300 rounded-md bg-white text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                                                        >
                                                            -
                                                        </button>
                                                        <input
                                                            type="number"
                                                            min="1"
                                                            value={item.quantity || 1}
                                                            onChange={(e) => {
                                                                const newQty = parseInt(e.target.value) || 1;
                                                                handleUpdateQuantity(item.id, newQty);
                                                            }}
                                                            disabled={updating[item.id]}
                                                            className="w-20 px-3 py-2 border border-gray-300 rounded-md text-center"
                                                        />
                                                        <button
                                                            type="button"
                                                            onClick={() => handleUpdateQuantity(item.id, (item.quantity || 1) + 1)}
                                                            disabled={updating[item.id]}
                                                            className="px-3 py-2 border border-gray-300 rounded-md bg-white text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                                                        >
                                                            +
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={() => handleRemoveItem(item.id)}
                                                            disabled={updating[item.id]}
                                                            className="ml-2 px-3 py-2 text-red-600 hover:text-red-700 disabled:opacity-50"
                                                        >
                                                            Remove
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </Card>
                                );
                            })}
                        </div>

                        {/* Order Summary */}
                        <div className="lg:col-span-1">
                            <Card className="p-6 sticky top-4">
                                <Heading level={2} className="text-xl mb-4">Order Summary</Heading>
                                
                                <div className="space-y-3 mb-4">
                                    <div className="flex justify-between">
                                        <Text>Subtotal</Text>
                                        <Text className="font-semibold">${subtotal.toFixed(2)}</Text>
                                    </div>
                                    {totalDiscount > 0 && (
                                        <div className="flex justify-between text-green-600">
                                            <Text>Discount</Text>
                                            <Text className="font-semibold">-${totalDiscount.toFixed(2)}</Text>
                                        </div>
                                    )}
                                    <div className="border-t pt-3 flex justify-between">
                                        <Text className="font-bold text-lg">Total</Text>
                                        <Text className="font-bold text-lg text-indigo-600">${total.toFixed(2)}</Text>
                                    </div>
                                </div>

                                <div className="space-y-3">
                                    <Link href="/products" className="block">
                                        <Button variant="outline" block>Continue Shopping</Button>
                                    </Link>
                                    {user ? (
                                        <Link href="/checkout" className="block">
                                            <Button block>Proceed to Checkout</Button>
                                        </Link>
                                    ) : (
                                        <>
                                            <Link href={`/login?redirect=/cart`} className="block">
                                                <Button block>Login to Checkout</Button>
                                            </Link>
                                            <Text size="xs" className="text-gray-500 text-center block">
                                                Please login to proceed with checkout
                                            </Text>
                                        </>
                                    )}
                                </div>
                            </Card>
                        </div>
                    </div>
                </div>
            </div>
        );
    };

    return (
        <>
            <Head title="Shopping Cart" />
            {user ? (
                <UserLayout>
                    {renderContent()}
                </UserLayout>
            ) : (
                <GuestLayout>
                    {renderContent()}
                </GuestLayout>
            )}
        </>
    );
}

