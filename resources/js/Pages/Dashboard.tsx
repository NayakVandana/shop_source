// @ts-nocheck
import React from 'react';
import { Head, usePage } from '@inertiajs/react';
import UserLayout from '../Layouts/UserLayout';

export default function Dashboard({ stats, recent_products, top_categories }) {
    const { auth } = usePage().props;
    const user = auth.user;
    // Provide safe defaults so the page doesn't crash when props are missing
    const safeStats = stats || {};
    const safeRecentProducts = Array.isArray(recent_products) ? recent_products : [];
    const safeTopCategories = Array.isArray(top_categories) ? top_categories : [];

    return (
        <UserLayout>
            <Head title="Dashboard" />
            <div className="min-h-screen bg-gray-50">
                <div className="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
                    <h1 className="text-4xl font-bold text-gray-900 mb-8">
                        Dashboard
                    </h1>

                    {/* Stats Grid */}
                    <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 mb-12">
                        <div className="bg-white rounded-lg shadow p-6">
                            <h3 className="text-sm font-medium text-gray-500">Total Products</h3>
                            <p className="mt-2 text-3xl font-semibold text-gray-900">{safeStats.total_products ?? 0}</p>
                        </div>
                        <div className="bg-white rounded-lg shadow p-6">
                            <h3 className="text-sm font-medium text-gray-500">Total Categories</h3>
                            <p className="mt-2 text-3xl font-semibold text-gray-900">{safeStats.total_categories ?? 0}</p>
                        </div>
                        <div className="bg-white rounded-lg shadow p-6">
                            <h3 className="text-sm font-medium text-gray-500">Total Users</h3>
                            <p className="mt-2 text-3xl font-semibold text-gray-900">{safeStats.total_users ?? 0}</p>
                        </div>
                        <div className="bg-white rounded-lg shadow p-6">
                            <h3 className="text-sm font-medium text-gray-500">Featured Products</h3>
                            <p className="mt-2 text-3xl font-semibold text-gray-900">{safeStats.featured_products ?? 0}</p>
                        </div>
                        <div className="bg-white rounded-lg shadow p-6">
                            <h3 className="text-sm font-medium text-gray-500">Low Stock Products</h3>
                            <p className="mt-2 text-3xl font-semibold text-red-600">{safeStats.low_stock_products ?? 0}</p>
                        </div>
                    </div>

                    {/* Recent Products */}
                    <div className="bg-white rounded-lg shadow mb-8">
                        <div className="px-6 py-4 border-b border-gray-200">
                            <h2 className="text-xl font-semibold text-gray-900">Recent Products</h2>
                        </div>
                        <div className="p-6">
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                {safeRecentProducts.map((product) => (
                                    <div key={product.uuid} className="border rounded-lg p-4">
                                        <h3 className="font-medium text-gray-900">{product.name}</h3>
                                        <p className="text-sm text-gray-500 mt-1 line-clamp-2">{product.description}</p>
                                        <p className="text-lg font-semibold text-indigo-600 mt-2">${product.price}</p>
                                    </div>
                                ))}
                            </div>
                            {safeRecentProducts.length === 0 && (
                                <p className="text-center text-gray-500 py-4">No recent products</p>
                            )}
                        </div>
                    </div>

                    {/* Top Categories */}
                    <div className="bg-white rounded-lg shadow">
                        <div className="px-6 py-4 border-b border-gray-200">
                            <h2 className="text-xl font-semibold text-gray-900">Top Categories</h2>
                        </div>
                        <div className="p-6">
                            <div className="space-y-4">
                                {safeTopCategories.map((category) => (
                                    <div key={category.uuid} className="flex items-center justify-between border-b pb-4 last:border-b-0">
                                        <div>
                                            <h3 className="font-medium text-gray-900">{category.name}</h3>
                                            <p className="text-sm text-gray-500">{category.products_count} products</p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                            {safeTopCategories.length === 0 && (
                                <p className="text-center text-gray-500 py-4">No categories available</p>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </UserLayout>
    );
}

