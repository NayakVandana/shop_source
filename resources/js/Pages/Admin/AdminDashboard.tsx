// @ts-nocheck
import React from 'react';
import { Head, usePage } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';

export default function AdminDashboard({ stats }) {
    const { auth } = usePage().props;

    return (
        <AdminLayout>
            <Head title="Admin Dashboard" />
            <div className="p-6">
                <h1 className="text-3xl font-bold text-gray-900 mb-8">
                    Admin Dashboard
                </h1>

                {/* Stats Grid */}
                <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 mb-8">
                    <div className="bg-white rounded-lg shadow p-6">
                        <h3 className="text-sm font-medium text-gray-500">Total Products</h3>
                        <p className="mt-2 text-3xl font-semibold text-gray-900">
                            {stats?.total_products || 0}
                        </p>
                    </div>
                    <div className="bg-white rounded-lg shadow p-6">
                        <h3 className="text-sm font-medium text-gray-500">Total Categories</h3>
                        <p className="mt-2 text-3xl font-semibold text-gray-900">
                            {stats?.total_categories || 0}
                        </p>
                    </div>
                    <div className="bg-white rounded-lg shadow p-6">
                        <h3 className="text-sm font-medium text-gray-500">Total Users</h3>
                        <p className="mt-2 text-3xl font-semibold text-gray-900">
                            {stats?.total_users || 0}
                        </p>
                    </div>
                    <div className="bg-white rounded-lg shadow p-6">
                        <h3 className="text-sm font-medium text-gray-500">Featured Products</h3>
                        <p className="mt-2 text-3xl font-semibold text-gray-900">
                            {stats?.featured_products || 0}
                        </p>
                    </div>
                    <div className="bg-white rounded-lg shadow p-6">
                        <h3 className="text-sm font-medium text-gray-500">Low Stock Products</h3>
                        <p className="mt-2 text-3xl font-semibold text-red-600">
                            {stats?.low_stock_products || 0}
                        </p>
                    </div>
                </div>

                {/* Quick Actions */}
                <div className="bg-white rounded-lg shadow p-6">
                    <h2 className="text-xl font-semibold text-gray-900 mb-4">Quick Actions</h2>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <button className="bg-indigo-600 text-white px-6 py-3 rounded-md hover:bg-indigo-700 transition-colors font-medium">
                            Add New Product
                        </button>
                        <button className="bg-green-600 text-white px-6 py-3 rounded-md hover:bg-green-700 transition-colors font-medium">
                            Manage Categories
                        </button>
                        <button className="bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 transition-colors font-medium">
                            View Orders
                        </button>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}

