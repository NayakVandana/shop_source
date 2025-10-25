import React from 'react';
import { Link } from '@inertiajs/react';

interface NavigationProps {
    user?: {
        name: string;
        email: string;
    };
}

export default function Navigation({ user }: NavigationProps) {
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
                            href="/products"
                            className="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium"
                        >
                            Products
                        </Link>
                        
                        {user ? (
                            <div className="flex items-center space-x-4">
                                <span className="text-gray-700">Welcome, {user.name}</span>
                                <Link
                                    href="/logout"
                                    method="post"
                                    className="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 text-sm font-medium"
                                >
                                    Logout
                                </Link>
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
