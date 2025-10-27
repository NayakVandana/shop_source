// @ts-nocheck
import React from 'react';
import { usePage } from '@inertiajs/react';
import Navigation from '../Components/Navigation';

export default function GuestLayout({ children, user }) {
    const { auth } = usePage().props;
    const currentUser = user || auth.user;

    return (
        <div className="min-h-screen flex flex-col">
            {/* Navigation */}
            <Navigation user={currentUser} />

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

