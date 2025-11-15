// @ts-nocheck
import React, { useState, useEffect } from 'react';
import { Head, Link, usePage, router } from '@inertiajs/react';
import axios from 'axios';
import UserLayout from '../../../Layouts/UserLayout';
import GuestLayout from '../../../Layouts/GuestLayout';
import Button from '../../../Components/ui/Button';
import { Heading, Text } from '../../../Components/ui/Typography';
import Card from '../../../Components/ui/Card';
import FormInput from '../../../Components/FormInputs/FormInput';

export default function Checkout() {
    const { auth } = usePage().props;
    const user = auth.user;
    const [cart, setCart] = useState(null);
    const [loading, setLoading] = useState(true);
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState(null);
    const [formData, setFormData] = useState({
        shipping_name: user?.name || '',
        shipping_email: user?.email || '',
        shipping_phone: '',
        shipping_address: '',
        shipping_city: '',
        shipping_state: '',
        shipping_postal_code: '',
        shipping_country: '',
        coupon_code: '',
        notes: '',
    });
    const [formErrors, setFormErrors] = useState({});

    // API config helper
    const getApiConfig = (options = {}) => {
        const { token: providedToken = null } = options;
        const isWebBrowser = typeof window !== 'undefined' && typeof document !== 'undefined';
        
        let token = providedToken;
        if (!token && isWebBrowser) {
            const urlParams = new URLSearchParams(window.location.search);
            token = urlParams.get('token');
            
            if (!token) {
                try {
                    token = localStorage.getItem('auth_token') || null;
                } catch (e) {
                    token = null;
                }
            }
            
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
        loadCart();
    }, []);

    const loadCart = () => {
        setLoading(true);
        setError(null);

        axios.post('/api/user/cart/index', {}, getApiConfig())
        .then(response => {
            if (response.data.status || response.data.success) {
                const cartData = response.data.data?.cart || response.data.data;
                if (cartData && Array.isArray(cartData.items) && cartData.items.length > 0) {
                    setCart(cartData);
                } else {
                    setError('Your cart is empty');
                }
            } else {
                setError(response.data.message || 'Failed to load cart');
            }
            setLoading(false);
        })
        .catch(error => {
            console.error('Error loading cart:', error);
            setError(error.response?.data?.message || 'Failed to load cart');
            setLoading(false);
        });
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        setError(null);
        setFormErrors({});
        setProcessing(true);

        axios.post('/api/user/orders/store', formData, getApiConfig())
        .then(response => {
            setProcessing(false);
            if (response.data.status || response.data.success) {
                // Redirect to order confirmation page
                const order = response.data.data;
                const urlParams = new URLSearchParams(window.location.search);
                const token = urlParams.get('token') || localStorage.getItem('auth_token') || '';
                const tokenParam = token ? `?token=${token}` : '';
                window.location.href = `/order-confirmation?order_id=${order.uuid}${tokenParam}`;
            } else {
                setError(response.data.message || 'Failed to place order');
                if (response.data.data?.errors) {
                    setFormErrors(response.data.data.errors);
                }
            }
        })
        .catch(error => {
            setProcessing(false);
            console.error('Error placing order:', error);
            setError(error.response?.data?.message || 'Failed to place order');
            if (error.response?.data?.data?.errors) {
                setFormErrors(error.response.data.data.errors);
            }
        });
    };

    const handleInputChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
        // Clear error for this field
        if (formErrors[name]) {
            setFormErrors(prev => {
                const newErrors = { ...prev };
                delete newErrors[name];
                return newErrors;
            });
        }
    };

    if (loading) {
        return (
            <>
                <Head title="Checkout" />
                {user ? (
                    <UserLayout>
                        <div className="min-h-screen bg-gray-50 flex items-center justify-center px-4">
                            <div className="text-center">
                                <div className="text-indigo-600 text-lg sm:text-xl md:text-2xl">Loading...</div>
                            </div>
                        </div>
                    </UserLayout>
                ) : (
                    <GuestLayout>
                        <div className="min-h-screen bg-gray-50 flex items-center justify-center px-4">
                            <div className="text-center">
                                <div className="text-indigo-600 text-lg sm:text-xl md:text-2xl">Loading...</div>
                            </div>
                        </div>
                    </GuestLayout>
                )}
            </>
        );
    }

    if (error && !cart) {
        return (
            <>
                <Head title="Checkout" />
                {user ? (
                    <UserLayout>
                        <div className="min-h-screen bg-gray-50">
                            <div className="max-w-7xl mx-auto py-6 px-4 sm:py-8 sm:px-6 lg:py-12 lg:px-8">
                                <Card className="p-8 text-center">
                                    <Text className="text-lg mb-4 text-red-600">{error}</Text>
                                    <Link href="/cart">
                                        <Button>Back to Cart</Button>
                                    </Link>
                                </Card>
                            </div>
                        </div>
                    </UserLayout>
                ) : (
                    <GuestLayout>
                        <div className="min-h-screen bg-gray-50">
                            <div className="max-w-7xl mx-auto py-6 px-4 sm:py-8 sm:px-6 lg:py-12 lg:px-8">
                                <Card className="p-8 text-center">
                                    <Text className="text-lg mb-4 text-red-600">{error}</Text>
                                    <Link href="/cart">
                                        <Button>Back to Cart</Button>
                                    </Link>
                                </Card>
                            </div>
                        </div>
                    </GuestLayout>
                )}
            </>
        );
    }

    if (!user) {
        return (
            <>
                <Head title="Checkout" />
                <GuestLayout>
                    <div className="min-h-screen bg-gray-50">
                        <div className="max-w-7xl mx-auto py-6 px-4 sm:py-8 sm:px-6 lg:py-12 lg:px-8">
                            <Card className="p-8 text-center">
                                <Text className="text-lg mb-4">Please login to proceed with checkout</Text>
                                <Link href={`/login?redirect=/checkout`}>
                                    <Button>Login</Button>
                                </Link>
                            </Card>
                        </div>
                    </div>
                </GuestLayout>
            </>
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
        <>
            <Head title="Checkout" />
            {user ? (
                <UserLayout>
                    <div className="min-h-screen bg-gray-50">
                        <div className="max-w-7xl mx-auto py-6 px-4 sm:py-8 sm:px-6 lg:py-12 lg:px-8">
                            <div className="mb-6">
                                <Link
                                    href="/cart"
                                    className="inline-flex items-center text-sm sm:text-base text-indigo-600 hover:text-indigo-500 font-medium touch-manipulation"
                                >
                                    <span className="mr-1">←</span> Back to Cart
                                </Link>
                            </div>

                            <Heading level={1} className="text-2xl sm:text-3xl md:text-4xl mb-6">Checkout</Heading>

                            {error && (
                                <Card className="mb-6 p-4 bg-red-50 border-red-200">
                                    <Text className="text-red-800">{error}</Text>
                                </Card>
                            )}

                            <form onSubmit={handleSubmit}>
                                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                    {/* Shipping Information */}
                                    <div className="lg:col-span-2">
                                        <Card className="p-6">
                                            <Heading level={2} className="text-xl mb-4">Shipping Information</Heading>
                                            
                                            <div className="space-y-4">
                                                <FormInput
                                                    id="shipping_name"
                                                    name="shipping_name"
                                                    type="text"
                                                    required
                                                    title="Full Name"
                                                    value={formData.shipping_name}
                                                    onChange={handleInputChange}
                                                    error={formErrors.shipping_name?.[0]}
                                                />

                                                <FormInput
                                                    id="shipping_email"
                                                    name="shipping_email"
                                                    type="email"
                                                    required
                                                    title="Email Address"
                                                    value={formData.shipping_email}
                                                    onChange={handleInputChange}
                                                    error={formErrors.shipping_email?.[0]}
                                                />

                                                <FormInput
                                                    id="shipping_phone"
                                                    name="shipping_phone"
                                                    type="tel"
                                                    required
                                                    title="Phone Number"
                                                    value={formData.shipping_phone}
                                                    onChange={handleInputChange}
                                                    error={formErrors.shipping_phone?.[0]}
                                                />

                                                <FormInput
                                                    id="shipping_address"
                                                    name="shipping_address"
                                                    type="text"
                                                    required
                                                    title="Address"
                                                    value={formData.shipping_address}
                                                    onChange={handleInputChange}
                                                    error={formErrors.shipping_address?.[0]}
                                                />

                                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                    <FormInput
                                                        id="shipping_city"
                                                        name="shipping_city"
                                                        type="text"
                                                        required
                                                        title="City"
                                                        value={formData.shipping_city}
                                                        onChange={handleInputChange}
                                                        error={formErrors.shipping_city?.[0]}
                                                    />

                                                    <FormInput
                                                        id="shipping_state"
                                                        name="shipping_state"
                                                        type="text"
                                                        title="State/Province"
                                                        value={formData.shipping_state}
                                                        onChange={handleInputChange}
                                                        error={formErrors.shipping_state?.[0]}
                                                    />
                                                </div>

                                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                    <FormInput
                                                        id="shipping_postal_code"
                                                        name="shipping_postal_code"
                                                        type="text"
                                                        title="Postal Code"
                                                        value={formData.shipping_postal_code}
                                                        onChange={handleInputChange}
                                                        error={formErrors.shipping_postal_code?.[0]}
                                                    />

                                                    <FormInput
                                                        id="shipping_country"
                                                        name="shipping_country"
                                                        type="text"
                                                        title="Country"
                                                        value={formData.shipping_country}
                                                        onChange={handleInputChange}
                                                        error={formErrors.shipping_country?.[0]}
                                                    />
                                                </div>

                                                <FormInput
                                                    id="coupon_code"
                                                    name="coupon_code"
                                                    type="text"
                                                    title="Coupon Code (Optional)"
                                                    placeholder="Enter coupon code"
                                                    value={formData.coupon_code}
                                                    onChange={handleInputChange}
                                                    error={formErrors.coupon_code?.[0]}
                                                />

                                                <div>
                                                    <label htmlFor="notes" className="block text-sm font-medium text-gray-700 mb-2">
                                                        Order Notes (Optional)
                                                    </label>
                                                    <textarea
                                                        id="notes"
                                                        name="notes"
                                                        rows={4}
                                                        value={formData.notes}
                                                        onChange={handleInputChange}
                                                        className="w-full px-3 sm:px-4 py-3 sm:py-3.5 border border-border-dark rounded-md text-sm sm:text-base focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                                        placeholder="Any special instructions for your order..."
                                                    />
                                                </div>
                                            </div>
                                        </Card>
                                    </div>

                                    {/* Order Summary */}
                                    <div className="lg:col-span-1">
                                        <Card className="p-6 sticky top-4">
                                            <Heading level={2} className="text-xl mb-4">Order Summary</Heading>
                                            
                                            <div className="space-y-3 mb-4">
                                                <div className="space-y-2">
                                                    {cart.items.map((item) => {
                                                        const itemPrice = (item.price || 0) - (item.discount_amount || 0);
                                                        const itemSubtotal = itemPrice * (item.quantity || 0);
                                                        return (
                                                            <div key={item.id} className="flex justify-between text-sm">
                                                                <div>
                                                                    <Text size="sm">{item.product?.name || 'Product'}</Text>
                                                                    <Text size="xs" className="text-gray-500">Qty: {item.quantity}</Text>
                                                                </div>
                                                                <Text size="sm" className="font-semibold">${itemSubtotal.toFixed(2)}</Text>
                                                            </div>
                                                        );
                                                    })}
                                                </div>

                                                <div className="border-t pt-3 space-y-2">
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
                                            </div>

                                            <Button
                                                type="submit"
                                                block
                                                disabled={processing}
                                            >
                                                {processing ? 'Processing...' : 'Place Order'}
                                            </Button>
                                        </Card>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </UserLayout>
            ) : (
                <GuestLayout>
                    <div className="min-h-screen bg-gray-50">
                        <div className="max-w-7xl mx-auto py-6 px-4 sm:py-8 sm:px-6 lg:py-12 lg:px-8">
                            <Card className="p-8 text-center">
                                <Text className="text-lg mb-4">Please login to proceed with checkout</Text>
                                <Link href={`/login?redirect=/checkout`}>
                                    <Button>Login</Button>
                                </Link>
                            </Card>
                        </div>
                    </div>
                </GuestLayout>
            )}
        </>
    );
}

