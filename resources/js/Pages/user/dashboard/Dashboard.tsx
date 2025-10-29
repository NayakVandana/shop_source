// @ts-nocheck
import React, { useEffect, useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import axios from 'axios';
import UserLayout from '../../../Layouts/UserLayout';

export default function Dashboard() {
    const { auth } = usePage().props;
    const user = auth.user;
    const [stats, setStats] = useState({});
    const [recentProducts, setRecentProducts] = useState([]);
    const [topCategories, setTopCategories] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        Promise.all([
            axios.post('/api/user/products/list', { per_page: 6 }).catch(() => null),
            axios.post('/api/user/products/featured', {}).catch(() => null)
        ])
            .then(([listRes, featuredRes]) => {
                const listOk = listRes && (listRes.data.success || listRes.data.status);
                const featuredOk = featuredRes && (featuredRes.data.success || featuredRes.data.status);

                const listData = listOk ? (Array.isArray(listRes.data.data) ? listRes.data.data : (listRes.data.data?.data || [])) : [];
                const featuredData = featuredOk ? (Array.isArray(featuredRes.data.data) ? featuredRes.data.data : (featuredRes.data.data?.data || [])) : [];

                setRecentProducts(listData);
                setTopCategories([]); // No endpoint provided; leaving empty for now
                setStats({
                    total_products: listData.length,
                    featured_products: featuredData.length
                });
            })
            .catch(() => {
                setError('Failed to load dashboard data');
            })
            .finally(() => setLoading(false));
    }, []);

    return (
        <UserLayout>
            <Head title="Dashboard" />
            <div className="min-h-screen bg-gray-50">
                <div className="max-w-7xl mx-auto py-6 px-4 sm:py-8 sm:px-6 lg:py-12 lg:px-8">
                    <h1 className="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900 mb-6 sm:mb-8">Dashboard</h1>

                    {loading && (
                        <div className="text-indigo-600 text-base sm:text-lg md:text-xl">Loading...</div>
                    )}
                    {error && (
                        <div className="text-red-600 text-sm sm:text-base mb-4">{error}</div>
                    )}

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:grid-cols-3 lg:gap-6 mb-8 sm:mb-12">
                        <div className="bg-white rounded-lg shadow p-4 sm:p-5 md:p-6">
                            <h3 className="text-xs sm:text-sm font-medium text-gray-500">Total Products</h3>
                            <p className="mt-2 text-2xl sm:text-3xl md:text-4xl font-semibold text-gray-900">{stats.total_products ?? 0}</p>
                        </div>
                        <div className="bg-white rounded-lg shadow p-4 sm:p-5 md:p-6">
                            <h3 className="text-xs sm:text-sm font-medium text-gray-500">Featured Products</h3>
                            <p className="mt-2 text-2xl sm:text-3xl md:text-4xl font-semibold text-gray-900">{stats.featured_products ?? 0}</p>
                        </div>
                    </div>

                    <div className="bg-white rounded-lg shadow mb-6 sm:mb-8">
                        <div className="px-4 py-3 sm:px-6 sm:py-4 border-b border-gray-200">
                            <h2 className="text-lg sm:text-xl font-semibold text-gray-900">Recent Products</h2>
                        </div>
                        <div className="p-4 sm:p-6">
                            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3">
                                {recentProducts.map((product) => (
                                    <div key={product.uuid || product.id} className="border rounded-lg p-3 sm:p-4 transition-shadow hover:shadow-md">
                                        <h3 className="font-medium text-sm sm:text-base text-gray-900 line-clamp-1">{product.name}</h3>
                                        <p className="text-xs sm:text-sm text-gray-500 mt-1 line-clamp-2">{product.description}</p>
                                        <p className="text-base sm:text-lg font-semibold text-indigo-600 mt-2">${product.price}</p>
                                    </div>
                                ))}
                            </div>
                            {recentProducts.length === 0 && !loading && (
                                <p className="text-center text-sm sm:text-base text-gray-500 py-4">No recent products</p>
                            )}
                        </div>
                    </div>

                    <div className="bg-white rounded-lg shadow">
                        <div className="px-4 py-3 sm:px-6 sm:py-4 border-b border-gray-200">
                            <h2 className="text-lg sm:text-xl font-semibold text-gray-900">Top Categories</h2>
                        </div>
                        <div className="p-4 sm:p-6">
                            {topCategories.length === 0 && (
                                <p className="text-center text-sm sm:text-base text-gray-500 py-4">No categories available</p>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </UserLayout>
    );
}

