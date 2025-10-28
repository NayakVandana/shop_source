// @ts-nocheck
import React, { useEffect } from 'react';
import { Link } from '@inertiajs/react';

export default function Navigation({ user }) {
    // Only show admin features if user is authenticated AND is admin
    const isAdmin = user && user.name && (user.is_admin === true || user.role === 'admin');
    
    // Clear admin token if user is not an admin
    useEffect(() => {
        if (!isAdmin) {
            localStorage.removeItem('admin_token');
        }
    }, [isAdmin]);
    
    // Get admin token from localStorage if available
    const adminToken = isAdmin ? localStorage.getItem('admin_token') : null;
    const adminPanelUrl = adminToken ? `/admin/dashboard?token=${adminToken}` : '/admin/dashboard';
    
    // Call API logout and then clear tokens/cookies and redirect
    const handleLogout = async () => {
        try {
            const urlParams = new URLSearchParams(window.location.search);
            const qpToken = urlParams.get('token');
            const localToken = localStorage.getItem('auth_token') || '';
            const token = qpToken || localToken || '';

            if (token) {
                await fetch('/api/user/logout', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'include'
                }).catch(() => {});
            }
        } catch (_) {}

        try {
            // Clear localStorage
            localStorage.removeItem('auth_token');
            localStorage.removeItem('admin_token');

            // Clear cookies
            document.cookie.split(';').forEach((c) => {
                document.cookie = c
                    .replace(/^ +/, '')
                    .replace(/=.*/, `=;expires=${new Date(0).toUTCString()};path=/`);
            });
        } catch (_) {}

        // Redirect to home (strip query params)
        const url = new URL(window.location.href);
        window.location.href = `${url.origin}/`;
    };

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
                        {/* Common links for all users */}
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
                        
                        {/* User-specific actions */}
                        {user && user.name ? (
                            <div className="flex items-center space-x-4">
                                {/* Show Admin Panel link only for admins */}
                                {isAdmin && (
                                    <Link
                                        href={adminPanelUrl}
                                        className="text-indigo-600 hover:text-indigo-700 px-3 py-2 rounded-md text-sm font-medium border border-indigo-600"
                                    >
                                        Admin Panel
                                    </Link>
                                )}
                                
                                {/* User profile */}
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
                                
                                {/* Logout button */}
                                <button
                                    onClick={handleLogout}
                                    className="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 text-sm font-medium"
                                >
                                    Logout
                                </button>
                            </div>
                        ) : (
                            /* Guest user actions */
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
