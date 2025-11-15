// @ts-nocheck
import React, { useState, useEffect } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import axios from 'axios';
import UserLayout from '../../../Layouts/UserLayout';
import GuestLayout from '../../../Layouts/GuestLayout';
import Button from '../../../Components/ui/Button';
import { Heading, Text } from '../../../Components/ui/Typography';
import Card from '../../../Components/ui/Card';

export default function OrderConfirmation() {
    const { auth } = usePage().props;
    const user = auth.user;
    const [order, setOrder] = useState(null);
    const [loading, setLoading] = useState(true);
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
                // Remove token from URL immediately (but keep order_id)
                url.searchParams.delete('token');
                window.history.replaceState({}, '', url.toString());
            }
        } catch (_) {}
    }, []);

    // API config helper
    const getApiConfig = (options = {}) => {
        const { token: providedToken = null } = options;
        const isWebBrowser = typeof window !== 'undefined' && typeof document !== 'undefined';
        
        // Get token from localStorage/cookies only (not URL)
        let token = providedToken;
        if (!token && isWebBrowser) {
            // Try localStorage first
            try {
                token = localStorage.getItem('auth_token') || null;
            } catch (e) {
                token = null;
            }
            
            // Then try cookie (for persistent authentication)
            if (!token) {
                try {
                    const cookieToken = document.cookie
                        .split(';')
                        .find(c => c.trim().startsWith('auth_token='));
                    if (cookieToken) {
                        token = cookieToken.split('=')[1]?.trim() || null;
                    }
                } catch (e) {
                    token = null;
                }
            }
        }
        
        const headers = { 'Content-Type': 'application/json' };
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }
        
        return {
            headers,
            ...(isWebBrowser ? { withCredentials: true } : {})
        };
    };

    useEffect(() => {
        const urlParams = new URLSearchParams(window.location.search);
        const orderId = urlParams.get('order_id');

        if (orderId) {
            loadOrder(orderId);
        } else {
            setError('Order ID not found');
            setLoading(false);
        }
    }, []);

    const loadOrder = (orderId) => {
        setLoading(true);
        setError(null);

        axios.post('/api/user/orders/show', { id: orderId }, getApiConfig())
        .then(response => {
            if (response.data.status || response.data.success) {
                setOrder(response.data.data);
            } else {
                setError(response.data.message || 'Failed to load order');
            }
            setLoading(false);
        })
        .catch(error => {
            console.error('Error loading order:', error);
            setError(error.response?.data?.message || 'Failed to load order');
            setLoading(false);
        });
    };

    if (loading) {
        return (
            <>
                <Head title="Order Confirmation" />
                {user ? (
                    <UserLayout>
                        <div className="min-h-screen bg-gray-50 flex items-center justify-center px-4">
                            <div className="text-center">
                                <div className="text-indigo-600 text-lg sm:text-xl md:text-2xl">Loading order...</div>
                            </div>
                        </div>
                    </UserLayout>
                ) : (
                    <GuestLayout>
                        <div className="min-h-screen bg-gray-50 flex items-center justify-center px-4">
                            <div className="text-center">
                                <div className="text-indigo-600 text-lg sm:text-xl md:text-2xl">Loading order...</div>
                            </div>
                        </div>
                    </GuestLayout>
                )}
            </>
        );
    }

    if (error || !order) {
        return (
            <>
                <Head title="Order Not Found" />
                {user ? (
                    <UserLayout>
                        <div className="min-h-screen bg-gray-50">
                            <div className="max-w-7xl mx-auto py-6 px-4 sm:py-8 sm:px-6 lg:py-12 lg:px-8">
                                <Card className="p-8 text-center">
                                    <Text className="text-lg mb-4 text-red-600">{error || 'Order not found'}</Text>
                                    <div className="flex flex-col sm:flex-row gap-3 justify-center">
                                        <Link href="/products">
                                            <Button>Continue Shopping</Button>
                                        </Link>
                                        {user && (
                                            <Link href="/orders">
                                                <Button variant="outline">View My Orders</Button>
                                            </Link>
                                        )}
                                    </div>
                                </Card>
                            </div>
                        </div>
                    </UserLayout>
                ) : (
                    <GuestLayout>
                        <div className="min-h-screen bg-gray-50">
                            <div className="max-w-7xl mx-auto py-6 px-4 sm:py-8 sm:px-6 lg:py-12 lg:px-8">
                                <Card className="p-8 text-center">
                                    <Text className="text-lg mb-4 text-red-600">{error || 'Order not found'}</Text>
                                    <Link href="/products">
                                        <Button>Continue Shopping</Button>
                                    </Link>
                                </Card>
                            </div>
                        </div>
                    </GuestLayout>
                )}
            </>
        );
    }

    return (
        <>
            <Head title="Order Confirmation" />
            {user ? (
                <UserLayout>
                    <div className="min-h-screen bg-gray-50">
                        <div className="max-w-4xl mx-auto py-6 px-4 sm:py-8 sm:px-6 lg:py-12 lg:px-8">
                            <Card className="p-6 sm:p-8">
                                <div className="text-center mb-6">
                                    <div className="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 mb-4">
                                        <svg className="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <Heading level={1} className="text-2xl sm:text-3xl mb-2">Order Confirmed!</Heading>
                                    <Text className="text-gray-600">Thank you for your order. We've received your order and will process it shortly.</Text>
                                </div>

                                <div className="border-t border-b py-4 mb-6">
                                    <div className="flex justify-between items-center">
                                        <Text className="font-semibold">Order Number:</Text>
                                        <Text className="font-mono text-lg">{order.uuid || order.id}</Text>
                                    </div>
                                    <div className="flex justify-between items-center mt-2">
                                        <Text className="font-semibold">Order Status:</Text>
                                        <span className={`px-3 py-1 rounded-full text-sm font-medium ${
                                            order.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                            order.status === 'processing' ? 'bg-blue-100 text-blue-800' :
                                            order.status === 'completed' ? 'bg-green-100 text-green-800' :
                                            'bg-gray-100 text-gray-800'
                                        }`}>
                                            {order.status?.toUpperCase() || 'PENDING'}
                                        </span>
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                    <div>
                                        <Heading level={3} className="text-lg mb-3">Shipping Address</Heading>
                                        <Text className="text-gray-600">
                                            {order.shipping_name}<br />
                                            {order.shipping_address}<br />
                                            {order.shipping_city}{order.shipping_state ? `, ${order.shipping_state}` : ''}<br />
                                            {order.shipping_postal_code && `${order.shipping_postal_code} `}
                                            {order.shipping_country}<br />
                                            {order.shipping_email}<br />
                                            {order.shipping_phone}
                                        </Text>
                                    </div>
                                    <div>
                                        <Heading level={3} className="text-lg mb-3">Order Summary</Heading>
                                        <div className="space-y-2">
                                            <div className="flex justify-between">
                                                <Text>Subtotal:</Text>
                                                <Text>${(order.subtotal || 0).toFixed(2)}</Text>
                                            </div>
                                            {order.discount_amount > 0 && (
                                                <div className="flex justify-between text-green-600">
                                                    <Text>Discount:</Text>
                                                    <Text>-${(order.discount_amount || 0).toFixed(2)}</Text>
                                                </div>
                                            )}
                                            {order.coupon_discount > 0 && (
                                                <div className="flex justify-between text-green-600">
                                                    <Text>Coupon ({order.coupon_code}):</Text>
                                                    <Text>-${(order.coupon_discount || 0).toFixed(2)}</Text>
                                                </div>
                                            )}
                                            {order.tax_amount > 0 && (
                                                <div className="flex justify-between">
                                                    <Text>Tax:</Text>
                                                    <Text>${(order.tax_amount || 0).toFixed(2)}</Text>
                                                </div>
                                            )}
                                            {order.shipping_amount > 0 && (
                                                <div className="flex justify-between">
                                                    <Text>Shipping:</Text>
                                                    <Text>${(order.shipping_amount || 0).toFixed(2)}</Text>
                                                </div>
                                            )}
                                            <div className="border-t pt-2 flex justify-between font-bold text-lg">
                                                <Text>Total:</Text>
                                                <Text className="text-indigo-600">${(order.total || 0).toFixed(2)}</Text>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {order.items && order.items.length > 0 && (
                                    <div className="mb-6">
                                        <Heading level={3} className="text-lg mb-3">Order Items</Heading>
                                        <div className="space-y-3">
                                            {order.items.map((item) => {
                                                const itemPrice = (item.price || 0) - (item.discount_amount || 0);
                                                const itemSubtotal = itemPrice * (item.quantity || 0);
                                                return (
                                                    <div key={item.id} className="flex gap-4 p-3 bg-gray-50 rounded-md">
                                                        <div className="flex-shrink-0">
                                                            <img
                                                                src={item.product?.primary_image_url || item.product?.image || '/images/placeholder.svg'}
                                                                alt={item.product_name || 'Product'}
                                                                className="w-16 h-16 object-cover rounded-md"
                                                                onError={(e) => {
                                                                    e.target.src = '/images/placeholder.svg';
                                                                }}
                                                            />
                                                        </div>
                                                        <div className="flex-1">
                                                            <Text className="font-semibold">{item.product_name || 'Product'}</Text>
                                                            <Text size="sm" className="text-gray-600">SKU: {item.product_sku || 'N/A'}</Text>
                                                            <Text size="sm" className="text-gray-600">Quantity: {item.quantity}</Text>
                                                        </div>
                                                        <div className="text-right">
                                                            <Text className="font-semibold">${itemSubtotal.toFixed(2)}</Text>
                                                            {item.discount_amount > 0 && (
                                                                <Text size="xs" className="text-gray-500 line-through">${((item.price || 0) * (item.quantity || 0)).toFixed(2)}</Text>
                                                            )}
                                                        </div>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    </div>
                                )}

                                <div className="flex flex-col sm:flex-row gap-3 justify-center">
                                    <Link href="/products">
                                        <Button>Continue Shopping</Button>
                                    </Link>
                                    {user && (
                                        <Link href="/orders">
                                            <Button variant="outline">View My Orders</Button>
                                        </Link>
                                    )}
                                </div>
                            </Card>
                        </div>
                    </div>
                </UserLayout>
            ) : (
                <GuestLayout>
                    <div className="min-h-screen bg-gray-50">
                        <div className="max-w-4xl mx-auto py-6 px-4 sm:py-8 sm:px-6 lg:py-12 lg:px-8">
                            <Card className="p-6 sm:p-8">
                                <div className="text-center mb-6">
                                    <div className="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 mb-4">
                                        <svg className="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <Heading level={1} className="text-2xl sm:text-3xl mb-2">Order Confirmed!</Heading>
                                    <Text className="text-gray-600">Thank you for your order.</Text>
                                </div>
                                <Link href="/products">
                                    <Button block>Continue Shopping</Button>
                                </Link>
                            </Card>
                        </div>
                    </div>
                </GuestLayout>
            )}
        </>
    );
}

