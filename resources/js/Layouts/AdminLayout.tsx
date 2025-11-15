// @ts-nocheck
import React, { useState, useEffect } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { canViewModule, hasPermission } from '../Pages/admin/permissions/helpers/permissions';

export default function AdminLayout({ children, is404 = false }) {
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
            <aside className={`${sidebarOpen ? 'translate-x-0' : '-translate-x-full'} lg:translate-x-0 fixed lg:static inset-y-0 left-0 z-50 w-64 sm:w-72 bg-gray-900 transition-transform duration-300`}>
                <div className="h-full flex flex-col">
                    {/* Logo */}
                    <div className="flex items-center justify-between h-14 sm:h-16 px-4 sm:px-6 border-b border-gray-800">
                        <Link href={`/admin/dashboard${tokenParam}`} className="text-lg sm:text-xl font-bold text-white">
                            ShopSource
                        </Link>
                        <button
                            onClick={() => setSidebarOpen(false)}
                            className="lg:hidden text-gray-400 hover:text-white touch-manipulation p-2 min-w-[44px] min-h-[44px] flex items-center justify-center"
                        >
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {/* Navigation */}
                    <nav className="flex-1 px-3 sm:px-4 py-4 sm:py-6 space-y-1 sm:space-y-2 overflow-y-auto">
                        {/* Dashboard - Always visible for admins */}
                        {canViewModule(user, 'dashboard') && (
                            <Link
                                href={`/admin/dashboard${tokenParam}`}
                                className="block px-3 sm:px-4 py-2.5 sm:py-3 text-sm sm:text-base text-gray-300 hover:bg-gray-800 hover:text-white rounded-md transition-colors touch-manipulation min-h-[44px] flex items-center"
                                onClick={() => setSidebarOpen(false)}
                            >
                                Dashboard
                            </Link>
                        )}
                        
                        {/* Products - Check for products:view permission */}
                        {canViewModule(user, 'products') && (
                            <Link
                                href={`/admin/products${tokenParam}`}
                                className="block px-3 sm:px-4 py-2.5 sm:py-3 text-sm sm:text-base text-gray-300 hover:bg-gray-800 hover:text-white rounded-md transition-colors touch-manipulation min-h-[44px] flex items-center"
                                onClick={() => setSidebarOpen(false)}
                            >
                                Products
                            </Link>
                        )}
                        
                        {/* Discounts - Check for products:view permission */}
                        {canViewModule(user, 'products') && (
                            <Link
                                href={`/admin/discounts${tokenParam}`}
                                className="block px-3 sm:px-4 py-2.5 sm:py-3 text-sm sm:text-base text-gray-300 hover:bg-gray-800 hover:text-white rounded-md transition-colors touch-manipulation min-h-[44px] flex items-center"
                                onClick={() => setSidebarOpen(false)}
                            >
                                Discounts
                            </Link>
                        )}
                        
                        {/* Coupon Codes - Check for products:view permission */}
                        {canViewModule(user, 'products') && (
                            <Link
                                href={`/admin/coupon-codes${tokenParam}`}
                                className="block px-3 sm:px-4 py-2.5 sm:py-3 text-sm sm:text-base text-gray-300 hover:bg-gray-800 hover:text-white rounded-md transition-colors touch-manipulation min-h-[44px] flex items-center"
                                onClick={() => setSidebarOpen(false)}
                            >
                                Coupon Codes
                            </Link>
                        )}
                        
                        {/* Categories - Check for categories:view permission */}
                        {canViewModule(user, 'categories') && (
                            <Link
                                href={`/admin/categories${tokenParam}`}
                                className="block px-3 sm:px-4 py-2.5 sm:py-3 text-sm sm:text-base text-gray-300 hover:bg-gray-800 hover:text-white rounded-md transition-colors touch-manipulation min-h-[44px] flex items-center"
                                onClick={() => setSidebarOpen(false)}
                            >
                                Categories
                            </Link>
                        )}
                        
                        {/* Users - Check for users:view permission */}
                        {canViewModule(user, 'users') && (
                            <Link
                                href={`/admin/users${tokenParam}`}
                                className="block px-3 sm:px-4 py-2.5 sm:py-3 text-sm sm:text-base text-gray-300 hover:bg-gray-800 hover:text-white rounded-md transition-colors touch-manipulation min-h-[44px] flex items-center"
                                onClick={() => setSidebarOpen(false)}
                            >
                                Users
                            </Link>
                        )}
                        
                        {/* Orders - Check for orders:view permission */}
                        {canViewModule(user, 'orders') && (
                            <Link
                                href={`/admin/orders${tokenParam}`}
                                className="block px-3 sm:px-4 py-2.5 sm:py-3 text-sm sm:text-base text-gray-300 hover:bg-gray-800 hover:text-white rounded-md transition-colors touch-manipulation min-h-[44px] flex items-center"
                                onClick={() => setSidebarOpen(false)}
                            >
                                Orders
                            </Link>
                        )}
                        
                        {/* Permissions - Check for permissions:view or permissions:manage permission */}
                        {(hasPermission(user, 'permissions:view') || hasPermission(user, 'permissions:manage')) && (
                            <Link
                                href={`/admin/permissions${tokenParam}`}
                                className="block px-3 sm:px-4 py-2.5 sm:py-3 text-sm sm:text-base text-gray-300 hover:bg-gray-800 hover:text-white rounded-md transition-colors touch-manipulation min-h-[44px] flex items-center"
                                onClick={() => setSidebarOpen(false)}
                            >
                                Permissions
                            </Link>
                        )}
                        
                        {/* View Site - Always visible */}
                        <Link
                            href="/"
                            onClick={() => setSidebarOpen(false)}
                            className="block px-3 sm:px-4 py-2.5 sm:py-3 text-sm sm:text-base text-gray-300 hover:bg-gray-800 hover:text-white rounded-md transition-colors touch-manipulation min-h-[44px] flex items-center"
                        >
                            View Site
                        </Link>
                    </nav>

                    {/* User Info */}
                    {user && user.name ? (
                        <div className="border-t border-gray-800 px-3 sm:px-4 py-3 sm:py-4">
                            <div className="flex items-center">
                                <div className="h-9 w-9 sm:h-10 sm:w-10 bg-indigo-600 rounded-full flex items-center justify-center flex-shrink-0">
                                    <span className="text-white text-xs sm:text-sm font-medium">
                                        {user.name.charAt(0).toUpperCase()}
                                    </span>
                                </div>
                                <div className="ml-2 sm:ml-3 min-w-0">
                                    <p className="text-xs sm:text-sm font-medium text-white truncate">{user.name}</p>
                                    <p className="text-xs text-gray-400">Administrator</p>
                                </div>
                            </div>
                        </div>
                    ) : (
                        <div className="border-t border-gray-800 px-3 sm:px-4 py-3 sm:py-4">
                            <div className="flex items-center">
                                <div className="h-9 w-9 sm:h-10 sm:w-10 bg-gray-700 rounded-full flex items-center justify-center flex-shrink-0">
                                    <span className="text-gray-300 text-xs sm:text-sm font-medium">...</span>
                                </div>
                                <div className="ml-2 sm:ml-3">
                                    <p className="text-xs sm:text-sm font-medium text-gray-300">Loading...</p>
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
                    <div className="flex items-center justify-between h-14 sm:h-16 px-4 sm:px-6">
                        <button
                            onClick={() => setSidebarOpen(true)}
                            className="lg:hidden text-gray-600 hover:text-gray-900 touch-manipulation p-2 min-w-[44px] min-h-[44px] flex items-center justify-center"
                        >
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>

                        <div className="flex items-center space-x-2 sm:space-x-4 ml-auto">
                            <Link
                                href="/products"
                                className="bg-indigo-600 text-white px-3 sm:px-4 py-2 sm:py-2.5 rounded-md hover:bg-indigo-700 active:bg-indigo-800 text-xs sm:text-sm font-medium transition-colors touch-manipulation min-h-[36px] sm:min-h-[40px] flex items-center justify-center"
                            >
                                User Panel
                            </Link>
                        </div>
                    </div>
                </header>

                {/* Page Content */}
                <main className="flex-1 overflow-auto">
                    {is404 ? (
                        <div className="min-h-full flex items-center justify-center px-4 py-16">
                            <div className="text-center">
                                <div className="text-3xl font-bold text-gray-900 mb-2">404</div>
                                <div className="text-lg text-gray-600 mb-6">Page not found</div>
                                <a
                                    href={`/admin/dashboard${tokenParam}`}
                                    className="text-indigo-600 hover:text-indigo-500 font-medium"
                                >
                                    Back to Admin Dashboard
                                </a>
                            </div>
                        </div>
                    ) : (
                        children
                    )}
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

