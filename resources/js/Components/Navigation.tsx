// @ts-nocheck
import React from 'react';
import { Link } from '@inertiajs/react';

export default function Navigation({ user }) {
    const isAdmin = user?.is_admin || user?.role === 'admin';

    return (
        <nav className="bg-white shadow-lg">
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="flex justify-between h-16">
                    <div className="flex items-center">
                        <Link href="/" className="text-xl font-bold text-indigo-600">
                            ShopSource
                        </Link>
                    </div>
                    
                    <div className="flex items-center space-x-4">
                        <Link
                            href="/"
                            className="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium"
                        >
                            Home
                        </Link>
                        <Link
                            href="/products"
                            className="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium"
                        >
                            Products
                        </Link>
                        {!user && (
                            <Link
                                href="/admin/login"
                                className="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium"
                            >
                                Admin Login
                            </Link>
                        )}
                        
                        {user ? (
                            <div className="flex items-center space-x-4">
                                {isAdmin && (
                                    <Link
                                        href={"/admin/dashboard?token=" + (localStorage.getItem('admin_token') || '')}
                                        className="text-indigo-600 hover:text-indigo-700 px-3 py-2 rounded-md text-sm font-medium border border-indigo-600"
                                    >
                                        Admin Panel
                                    </Link>
                                )}
                                <div className="flex items-center">
                                    <div className="h-8 w-8 bg-indigo-600 rounded-full flex items-center justify-center">
                                        <span className="text-white text-sm font-medium">
                                            {user.name.charAt(0).toUpperCase()}
                                        </span>
                                    </div>
                                    <span className="ml-2 text-gray-700 hidden sm:block">
                                        {user.name}
                                    </span>
                                </div>
                                <button
                                    onClick={() => {
                                        localStorage.removeItem('auth_token');
                                        localStorage.removeItem('admin_token');
                                        window.location.href = '/login';
                                    }}
                                    className="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 text-sm font-medium"
                                >
                                    Logout
                                </button>
                            </div>
                        ) : (
                            <div className="flex items-center space-x-2">
                                <Link
                                    href="/login"
                                    className="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium"
                                >
                                    Login
                                </Link>
                                <Link
                                    href="/register"
                                    className="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 text-sm font-medium"
                                >
                                    Register
                                </Link>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </nav>
    );
}
