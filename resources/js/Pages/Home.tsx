// @ts-nocheck
import React from 'react';
import { Head, usePage } from '@inertiajs/react';
import GuestLayout from '../Layouts/GuestLayout';
import UserLayout from '../Layouts/UserLayout';

export default function Home() {
    const { auth } = usePage().props;
    const user = auth.user;
    // Determine which layout to use based on authentication
    if (user) {
        return (
            <UserLayout>
                <Head title="Home" />
                <div className="min-h-screen bg-gray-50">
                <div className="max-w-7xl mx-auto py-8 px-4 sm:py-12 sm:px-6 lg:px-8">
                    <div className="text-center">
                        <h1 className="text-3xl font-bold text-gray-900 sm:text-4xl md:text-5xl lg:text-6xl">
                            Welcome to ShopSource
                        </h1>
                        <p className="mt-4 max-w-md mx-auto text-base text-gray-500 sm:text-lg sm:mt-5 md:text-xl md:max-w-3xl">
                            Your one-stop shop for all your needs
                        </p>
                        <div className="mt-6 max-w-md mx-auto flex flex-col sm:flex-row sm:justify-center sm:gap-3 md:mt-8">
                            <div className="rounded-md shadow-sm sm:shadow">
                                <a
                                    href="/products"
                                    className="w-full flex items-center justify-center px-6 py-3 sm:px-8 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 transition-colors touch-manipulation md:py-4 md:text-lg md:px-10"
                                >
                                    Browse Products
                                </a>
                            </div>
                            <div className="mt-3 rounded-md shadow-sm sm:mt-0 sm:shadow">
                                <a
                                    href="/login"
                                    className="w-full flex items-center justify-center px-6 py-3 sm:px-8 border border-transparent text-base font-medium rounded-md text-indigo-600 bg-white hover:bg-gray-50 active:bg-gray-100 transition-colors touch-manipulation md:py-4 md:text-lg md:px-10"
                                >
                                    Sign In
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </UserLayout>
        );
    }

    return (
        <GuestLayout>
            <Head title="Home" />
            <div className="min-h-screen bg-gray-50">
                <div className="max-w-7xl mx-auto py-8 px-4 sm:py-12 sm:px-6 lg:px-8">
                    <div className="text-center">
                        <h1 className="text-3xl font-bold text-gray-900 sm:text-4xl md:text-5xl lg:text-6xl">
                            Welcome to ShopSource
                        </h1>
                        <p className="mt-4 max-w-md mx-auto text-base text-gray-500 sm:text-lg sm:mt-5 md:text-xl md:max-w-3xl">
                            Your one-stop shop for all your needs
                        </p>
                        <div className="mt-6 max-w-md mx-auto flex flex-col sm:flex-row sm:justify-center sm:gap-3 md:mt-8">
                            <div className="rounded-md shadow-sm sm:shadow">
                                <a
                                    href="/products"
                                    className="w-full flex items-center justify-center px-6 py-3 sm:px-8 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 transition-colors touch-manipulation md:py-4 md:text-lg md:px-10"
                                >
                                    Browse Products
                                </a>
                            </div>
                            <div className="mt-3 rounded-md shadow-sm sm:mt-0 sm:shadow">
                                <a
                                    href="/login"
                                    className="w-full flex items-center justify-center px-6 py-3 sm:px-8 border border-transparent text-base font-medium rounded-md text-indigo-600 bg-white hover:bg-gray-50 active:bg-gray-100 transition-colors touch-manipulation md:py-4 md:text-lg md:px-10"
                                >
                                    Sign In
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </GuestLayout>
    );
}
