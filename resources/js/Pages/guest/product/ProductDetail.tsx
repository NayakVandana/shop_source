// @ts-nocheck
import React, { useState, useEffect } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import axios from 'axios';
import UserLayout from '../../../Layouts/UserLayout';
import GuestLayout from '../../../Layouts/GuestLayout';

export default function ProductDetail() {
    const { auth } = usePage().props;
    const user = auth.user;
    const [product, setProduct] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        // Extract UUID from query parameter
        const urlParams = new URLSearchParams(window.location.search);
        const uuid = urlParams.get('uuid');

        if (uuid) {
            axios.post('/api/user/products/show', { id: uuid })
                .then(response => {
                    if (response.data.success) {
                        setProduct(response.data.data);
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
                        <div className="w-full md:w-1/2">
                            <div className="aspect-w-16 aspect-h-9 bg-gray-200">
                                {product.image ? (
                                    <img
                                        src={product.image}
                                        alt={product.name}
                                        className="w-full h-64 sm:h-80 md:h-96 object-cover"
                                    />
                                ) : (
                                    <div className="w-full h-64 sm:h-80 md:h-96 bg-gray-200 flex items-center justify-center">
                                        <span className="text-gray-400 text-base sm:text-lg md:text-xl">No Image Available</span>
                                    </div>
                                )}
                            </div>
                        </div>
                        <div className="w-full md:w-1/2 p-4 sm:p-6 md:p-8">
                            <h1 className="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900 mb-3 sm:mb-4">
                                {product.name}
                            </h1>
                            
                            <div className="mb-4 sm:mb-6">
                                <span className="text-3xl sm:text-4xl md:text-5xl font-bold text-indigo-600">
                                    ${product.price}
                                </span>
                            </div>

                            <div className="mb-6 sm:mb-8">
                                <h2 className="text-base sm:text-lg font-semibold text-gray-900 mb-2 sm:mb-3">Description</h2>
                                <p className="text-sm sm:text-base text-gray-600 leading-relaxed">
                                    {product.description}
                                </p>
                            </div>

                            <div className="flex flex-col sm:flex-row gap-3 sm:gap-4">
                                <button className="flex-1 sm:flex-none bg-indigo-600 text-white px-6 sm:px-8 py-3 sm:py-3.5 rounded-md hover:bg-indigo-700 active:bg-indigo-800 transition-colors font-medium text-sm sm:text-base touch-manipulation min-h-[44px]">
                                    Add to Cart
                                </button>
                                <button className="flex-1 sm:flex-none border border-gray-300 text-gray-700 px-6 sm:px-8 py-3 sm:py-3.5 rounded-md hover:bg-gray-50 active:bg-gray-100 transition-colors font-medium text-sm sm:text-base touch-manipulation min-h-[44px]">
                                    Add to Wishlist
                                </button>
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

