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
                <div className="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
                    <div className="text-center">
                        <h1 className="text-4xl font-bold text-gray-900 sm:text-5xl md:text-6xl">
                            Welcome to ShopSource
                        </h1>
                        <p className="mt-3 max-w-md mx-auto text-base text-gray-500 sm:text-lg md:mt-5 md:text-xl md:max-w-3xl">
                            Your one-stop shop for all your needs
                        </p>
                        <div className="mt-5 max-w-md mx-auto sm:flex sm:justify-center md:mt-8">
                            <div className="rounded-md shadow">
                                <a
                                    href="/products"
                                    className="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 md:py-4 md:text-lg md:px-10"
                                >
                                    Browse Products
                                </a>
                            </div>
                            <div className="mt-3 rounded-md shadow sm:mt-0 sm:ml-3">
                                <a
                                    href="/login"
                                    className="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-indigo-600 bg-white hover:bg-gray-50 md:py-4 md:text-lg md:px-10"
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
                <div className="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
                    <div className="text-center">
                        <h1 className="text-4xl font-bold text-gray-900 sm:text-5xl md:text-6xl">
                            Welcome to ShopSource
                        </h1>
                        <p className="mt-3 max-w-md mx-auto text-base text-gray-500 sm:text-lg md:mt-5 md:text-xl md:max-w-3xl">
                            Your one-stop shop for all your needs
                        </p>
                        <div className="mt-5 max-w-md mx-auto sm:flex sm:justify-center md:mt-8">
                            <div className="rounded-md shadow">
                                <a
                                    href="/products"
                                    className="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 md:py-4 md:text-lg md:px-10"
                                >
                                    Browse Products
                                </a>
                            </div>
                            <div className="mt-3 rounded-md shadow sm:mt-0 sm:ml-3">
                                <a
                                    href="/login"
                                    className="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-indigo-600 bg-white hover:bg-gray-50 md:py-4 md:text-lg md:px-10"
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
