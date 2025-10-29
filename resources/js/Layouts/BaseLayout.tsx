// @ts-nocheck
import React from 'react';
import { usePage } from '@inertiajs/react';
import Navigation from '../Components/Navigation';
import Footer from '../Components/Footer';

interface BaseLayoutProps {
    children: React.ReactNode;
}

export default function BaseLayout({ children }: BaseLayoutProps) {
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
            <Footer />
        </div>
    );
}

