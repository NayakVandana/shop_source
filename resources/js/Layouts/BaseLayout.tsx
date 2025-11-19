// @ts-nocheck
import React from 'react';
import { usePage } from '@inertiajs/react';
import Navigation from '../Components/Navigation';
import Footer from '../Components/Footer';

interface BaseLayoutProps {
    children: React.ReactNode;
    is404?: boolean;
}

export default function BaseLayout({ children, is404 = false }: BaseLayoutProps) {
    const { auth } = usePage().props;
    const user = auth.user;

    return (
        <div className="min-h-screen flex flex-col bg-gray-50">
            {/* Navigation */}
            <Navigation user={user} />

            {/* Main Content */}
            <main className="flex-1">
                {is404 ? (
                    <div className="min-h-full flex items-center justify-center px-4 py-16">
                        <div className="text-center">
                            <div className="text-3xl font-bold text-gray-900 mb-2">404</div>
                            <div className="text-lg text-gray-600 mb-6">Page not found</div>
                            <div className="space-x-4">
                                <a href="/products" className="text-indigo-600 hover:text-indigo-500 font-medium">Go to products</a>
                            </div>
                        </div>
                    </div>
                ) : (
                    children
                )}
            </main>

            {/* Footer */}
            <Footer />
        </div>
    );
}

