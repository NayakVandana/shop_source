// @ts-nocheck
import React, { useEffect } from 'react';
import { Link } from '@inertiajs/react';

export default function Navigation({ user }) {
    // Only allow admin view for admins
    const isAdmin = user && user.name && (user.is_admin === true || user.role === 'admin');

    useEffect(() => {
        if (!isAdmin) {
            localStorage.removeItem('admin_token');
        }
    }, [isAdmin]);

    // Always strip token from URL after initial render to avoid lingering re-auth
    useEffect(() => {
        try {
            const url = new URL(window.location.href);
            if (url.searchParams.has('token')) {
                url.searchParams.delete('token');
                window.history.replaceState({}, '', url.toString());
            }
        } catch (_) {}
    }, []);

    // Auto-logout admin when browsing non-admin (user/guest) pages
    useEffect(() => {
        try {
            const isAdminPath = typeof window !== 'undefined' && window.location.pathname.startsWith('/admin');
            if (isAdminPath) return;

            const urlParams = new URLSearchParams(window.location.search);
            const qpToken = urlParams.get('token');
            const adminToken = localStorage.getItem('admin_token') || qpToken || '';

            if (adminToken) {
                fetch('/api/admin/logout', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'AdminToken': adminToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'include'
                }).catch(() => {});

                // Clear stored/admin URL token
                localStorage.removeItem('admin_token');

                // Remove token from URL without reload
                try {
                    const url = new URL(window.location.href);
                    url.searchParams.delete('token');
                    window.history.replaceState({}, '', url.toString());
                } catch (_) {}
            }
        } catch (_) {}
    }, []);

    // Optionally pass admin token in query if needed
    const adminToken = isAdmin ? localStorage.getItem('admin_token') : null;
    const adminPanelUrl = adminToken ? `/admin/dashboard?token=${adminToken}` : '/admin/dashboard';

    // Logout handler
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
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'include',
                }).catch(() => {});
            }

            // Also try admin logout if an admin token is present
            try {
                const adminLocal = localStorage.getItem('admin_token') || '';
                const adminToken = qpToken || adminLocal || '';
                if (adminToken) {
                    await fetch('/api/admin/logout', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'AdminToken': adminToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'include',
                    }).catch(() => {});
                }
            } catch (_) {}
        } catch (_) {}

        // Clear local/session data
        try {
            localStorage.removeItem('auth_token');
            localStorage.removeItem('admin_token');
            document.cookie.split(';').forEach((c) => {
                document.cookie = c
                    .replace(/^ +/, '')
                    .replace(/=.*/, `=;expires=${new Date(0).toUTCString()};path=/`);
            });
        } catch (_) {}

        // Refresh to get latest backend state and props
        window.location.replace("/");
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
                        {user && user.name ? (
                            <div className="flex items-center space-x-4">
                                {isAdmin && (
                                    <Link
                                        href={adminPanelUrl}
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
                                    onClick={handleLogout}
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
