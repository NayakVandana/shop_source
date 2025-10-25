import React from 'react';
import { Head, Link } from '@inertiajs/react';

interface Product {
    id: string;
    name: string;
    description: string;
    price: number;
    image?: string;
    created_at: string;
    updated_at: string;
}

interface ProductDetailProps {
    product: Product;
}

export default function ProductDetail({ product }: ProductDetailProps) {
    return (
        <>
            <Head title={product.name} />
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
