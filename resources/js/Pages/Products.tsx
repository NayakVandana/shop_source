// @ts-nocheck
import React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import UserLayout from '../Layouts/UserLayout';
import GuestLayout from '../Layouts/GuestLayout';

export default function Products({ products }) {
    const { auth } = usePage().props;
    const user = auth.user;
    const safeProducts = Array.isArray(products) ? products : [];
    
    const content = (
        <div className="min-h-screen bg-gray-50">
                <div className="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
                    <div className="text-center mb-12">
                        <h1 className="text-4xl font-bold text-gray-900 sm:text-5xl md:text-6xl">
                            Our Products
                        </h1>
                        <p className="mt-3 max-w-md mx-auto text-base text-gray-500 sm:text-lg md:mt-5 md:text-xl md:max-w-3xl">
                            Discover our amazing collection of products
                        </p>
                    </div>

                    <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        {safeProducts.map((product) => (
                            <div key={product.uuid} className="bg-white rounded-lg shadow-md overflow-hidden">
                                <div className="aspect-w-16 aspect-h-9 bg-gray-200">
                                    {product.image ? (
                                        <img
                                            src={product.image}
                                            alt={product.name}
                                            className="w-full h-48 object-cover"
                                        />
                                    ) : (
                                        <div className="w-full h-48 bg-gray-200 flex items-center justify-center">
                                            <span className="text-gray-400">No Image</span>
                                        </div>
                                    )}
                                </div>
                                <div className="p-6">
                                    <h3 className="text-lg font-semibold text-gray-900 mb-2">
                                        {product.name}
                                    </h3>
                                    <p className="text-gray-600 text-sm mb-4 line-clamp-2">
                                        {product.description}
                                    </p>
                                    <div className="flex items-center justify-between">
                                        <span className="text-2xl font-bold text-indigo-600">
                                            ${product.price}
                                        </span>
                                        <Link
                                            href={`/product?uuid=${product.uuid}`}
                                            className="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors"
                                        >
                                            View Details
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>

                    {safeProducts.length === 0 && (
                        <div className="text-center py-12">
                            <p className="text-gray-500 text-lg">No products available at the moment.</p>
                        </div>
                    )}
                </div>
            </div>
    );

    return (
        <>
            <Head title="Products" />
            {user ? (
                <UserLayout>
                    {content}
                </UserLayout>
            ) : (
                <GuestLayout>
                    {content}
                </GuestLayout>
            )}
        </>
    );
}
