// @ts-nocheck
import React from 'react';
import { usePage } from '@inertiajs/react';
import Navigation from '../Components/Navigation';

export default function UserLayout({ children }) {
    const { auth } = usePage().props;
    const user = auth.user;

    return (
        <div className="min-h-screen flex flex-col bg-gray-50">
            {/* Navigation */}
            <Navigation user={user} />

            {/* Main Content */}
            <main className="flex-1">
                {children}
            </main>

            {/* Footer */}
            <footer className="bg-gray-900 text-white">
                <div className="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
                    <div className="text-center">
                        <p>&copy; {new Date().getFullYear()} ShopSource. All rights reserved.</p>
                    </div>
                </div>
            </footer>
        </div>
    );
}

