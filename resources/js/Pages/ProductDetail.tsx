// @ts-nocheck
import React, { useState, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import axios from 'axios';
import Navigation from '../Components/Navigation';

export default function ProductDetail({ user }) {
    const [product, setProduct] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        // Extract UUID from query parameter
        const urlParams = new URLSearchParams(window.location.search);
        const uuid = urlParams.get('uuid');

        if (uuid) {
            axios.post('/api/products/show', { id: uuid })
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
        return (
            <>
                <Head title="Loading..." />
                <Navigation user={user} />
                <div className="min-h-screen bg-gray-50 flex items-center justify-center">
                    <div className="text-center">
                        <div className="text-indigo-600 text-2xl">Loading product...</div>
                    </div>
                </div>
            </>
        );
    }

    if (!product) {
        return (
            <>
                <Head title="Product Not Found" />
                <Navigation user={user} />
                <div className="min-h-screen bg-gray-50 flex items-center justify-center">
                    <div className="text-center">
                        <div className="text-2xl font-bold text-gray-900 mb-4">Product Not Found</div>
                        <Link
                            href="/products"
                            className="text-indigo-600 hover:text-indigo-500 font-medium"
                        >
                            ← Back to Products
                        </Link>
                    </div>
                </div>
            </>
        );
    }
    return (
        <>
            <Head title={product.name} />
            <Navigation user={user} />
            <div className="min-h-screen bg-gray-50">
                <div className="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
                    <div className="mb-8">
                        <Link
                            href="/products"
                            className="text-indigo-600 hover:text-indigo-500 font-medium"
                        >
                            ← Back to Products
                        </Link>
                    </div>

                    <div className="bg-white rounded-lg shadow-lg overflow-hidden">
                        <div className="md:flex">
                            <div className="md:w-1/2">
                                <div className="aspect-w-16 aspect-h-9 bg-gray-200">
                                    {product.image ? (
                                        <img
                                            src={product.image}
                                            alt={product.name}
                                            className="w-full h-96 object-cover"
                                        />
                                    ) : (
                                        <div className="w-full h-96 bg-gray-200 flex items-center justify-center">
                                            <span className="text-gray-400 text-xl">No Image Available</span>
                                        </div>
                                    )}
                                </div>
                            </div>
                            <div className="md:w-1/2 p-8">
                                <h1 className="text-3xl font-bold text-gray-900 mb-4">
                                    {product.name}
                                </h1>
                                
                                <div className="mb-6">
                                    <span className="text-4xl font-bold text-indigo-600">
                                        ${product.price}
                                    </span>
                                </div>

                                <div className="mb-8">
                                    <h2 className="text-lg font-semibold text-gray-900 mb-3">Description</h2>
                                    <p className="text-gray-600 leading-relaxed">
                                        {product.description}
                                    </p>
                                </div>

                                <div className="flex space-x-4">
                                    <button className="bg-indigo-600 text-white px-8 py-3 rounded-md hover:bg-indigo-700 transition-colors font-medium">
                                        Add to Cart
                                    </button>
                                    <button className="border border-gray-300 text-gray-700 px-8 py-3 rounded-md hover:bg-gray-50 transition-colors font-medium">
                                        Add to Wishlist
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
