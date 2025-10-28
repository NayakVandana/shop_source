// @ts-nocheck
import React, { useState, useEffect } from 'react';
import { Link, usePage } from '@inertiajs/react';

export default function AdminLayout({ children }) {
    const { auth } = usePage().props;
    const user = auth.user;
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [mounted, setMounted] = useState(false);
    
    // Ensure component is mounted before accessing localStorage
    useEffect(() => {
        setMounted(true);
    }, []);
    
    // Get admin token from localStorage and URL
    const getAdminToken = () => {
        if (!mounted) return '';
        
        // Try to get from URL first (for initial redirect)
        const urlParams = new URLSearchParams(window.location.search);
        const urlToken = urlParams.get('token');
        
        // Then try localStorage
        const localToken = localStorage.getItem('admin_token');
        
        return urlToken || localToken || '';
    };
    
    const adminToken = getAdminToken();
    const tokenParam = adminToken ? `?token=${adminToken}` : '';
    
    // Update localStorage with token from URL if present
    useEffect(() => {
        if (adminToken && mounted) {
            localStorage.setItem('admin_token', adminToken);
        }
    }, [adminToken, mounted]);

    return (
        <div className="min-h-screen bg-gray-50 flex">
            {/* Sidebar */}
            <aside className={`${sidebarOpen ? 'translate-x-0' : '-translate-x-full'} lg:translate-x-0 fixed lg:static inset-y-0 left-0 z-50 w-64 bg-gray-900 transition-transform duration-300`}>
                <div className="h-full flex flex-col">
                    {/* Logo */}
                    <div className="flex items-center justify-between h-16 px-6 border-b border-gray-800">
                        <Link href={`/admin/dashboard${tokenParam}`} className="text-xl font-bold text-white">
                            ShopSource
                        </Link>
                        <button
                            onClick={() => setSidebarOpen(false)}
                            className="lg:hidden text-gray-400 hover:text-white"
                        >
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {/* Navigation */}
                    <nav className="flex-1 px-4 py-6 space-y-2">
                        <Link
                            href={`/admin/dashboard${tokenParam}`}
                            className="block px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white rounded-md transition-colors"
                        >
                            Dashboard
                        </Link>
                        <Link
                            href={`/admin/products${tokenParam}`}
                            className="block px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white rounded-md transition-colors"
                        >
                            Products
                        </Link>
                        <Link
                            href={`/admin/categories${tokenParam}`}
                            className="block px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white rounded-md transition-colors"
                        >
                            Categories
                        </Link>
                        <Link
                            href={`/admin/users${tokenParam}`}
                            className="block px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white rounded-md transition-colors"
                        >
                            Users
                        </Link>
                        <Link
                            href={`/admin/orders${tokenParam}`}
                            className="block px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white rounded-md transition-colors"
                        >
                            Orders
                        </Link>
                        <button
                            onClick={async () => {
                                try {
                                    const urlParams = new URLSearchParams(window.location.search);
                                    const qpToken = urlParams.get('token');
                                    const adminToken = localStorage.getItem('admin_token') || '';
                                    const token = qpToken || adminToken || '';

                                    if (token) {
                                        await fetch('/api/admin/logout', {
                                            method: 'POST',
                                            headers: {
                                                'Accept': 'application/json',
                                                'AdminToken': token,
                                                'X-Requested-With': 'XMLHttpRequest'
                                            },
                                            credentials: 'include'
                                        }).catch(() => {});
                                    }
                                } catch (_) {}

                                try {
                                    localStorage.removeItem('admin_token');
                                    document.cookie.split(';').forEach((c) => {
                                        document.cookie = c
                                            .replace(/^ +/, '')
                                            .replace(/=.*/, `=;expires=${new Date(0).toUTCString()};path=/`);
                                    });
                                } catch (_) {}

                                const url = new URL(window.location.href);
                                window.location.href = `${url.origin}/`;
                            }}
                            className="block w-full text-left px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white rounded-md transition-colors"
                        >
                            View Site
                        </button>
                    </nav>

                    {/* User Info */}
                    {user && user.name ? (
                        <div className="border-t border-gray-800 px-4 py-4">
                            <div className="flex items-center">
                                <div className="h-10 w-10 bg-indigo-600 rounded-full flex items-center justify-center">
                                    <span className="text-white text-sm font-medium">
                                        {user.name.charAt(0).toUpperCase()}
                                    </span>
                                </div>
                                <div className="ml-3">
                                    <p className="text-sm font-medium text-white">{user.name}</p>
                                    <p className="text-xs text-gray-400">Administrator</p>
                                </div>
                            </div>
                        </div>
                    ) : (
                        <div className="border-t border-gray-800 px-4 py-4">
                            <div className="flex items-center">
                                <div className="h-10 w-10 bg-gray-700 rounded-full flex items-center justify-center">
                                    <span className="text-gray-300 text-sm font-medium">...</span>
                                </div>
                                <div className="ml-3">
                                    <p className="text-sm font-medium text-gray-300">Loading...</p>
                                    <p className="text-xs text-gray-500">Admin</p>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </aside>

            {/* Main Content */}
            <div className="flex-1 flex flex-col">
                {/* Top Navigation */}
                <header className="bg-white shadow-sm border-b border-gray-200">
                    <div className="flex items-center justify-between h-16 px-6">
                        <button
                            onClick={() => setSidebarOpen(true)}
                            className="lg:hidden text-gray-600 hover:text-gray-900"
                        >
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>

                        <div className="flex items-center space-x-4 ml-auto">
                            <button
                                onClick={async () => {
                                    try {
                                        const urlParams = new URLSearchParams(window.location.search);
                                        const qpToken = urlParams.get('token');
                                        const adminToken = localStorage.getItem('admin_token') || '';
                                        const token = qpToken || adminToken || '';

                                        if (token) {
                                            await fetch('/api/admin/logout', {
                                                method: 'POST',
                                                headers: {
                                                    'Accept': 'application/json',
                                                    'AdminToken': token,
                                                    'X-Requested-With': 'XMLHttpRequest'
                                                },
                                                credentials: 'include'
                                            }).catch(() => {});
                                        }
                                    } catch (_) {}

                                    try {
                                        localStorage.removeItem('admin_token');
                                        document.cookie.split(';').forEach((c) => {
                                            document.cookie = c
                                                .replace(/^ +/, '')
                                                .replace(/=.*/, `=;expires=${new Date(0).toUTCString()};path=/`);
                                        });
                                    } catch (_) {}

                                    const url = new URL(window.location.href);
                                    window.location.href = `${url.origin}/`;
                                }}
                                className="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 text-sm font-medium"
                            >
                                User Panel
                            </button>
                        </div>
                    </div>
                </header>

                {/* Page Content */}
                <main className="flex-1 overflow-auto">
                    {children}
                </main>
            </div>

            {/* Overlay for mobile sidebar */}
            {sidebarOpen && (
                <div
                    className="lg:hidden fixed inset-0 bg-black bg-opacity-50 z-40"
                    onClick={() => setSidebarOpen(false)}
                />
            )}
        </div>
    );
}

