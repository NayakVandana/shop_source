// @ts-nocheck
import React from 'react';
import BaseLayout from './BaseLayout';

interface AppLayoutProps {
    children: React.ReactNode;
    is404?: boolean;
}

/**
 * AppLayout - Automatically handles user/guest layout selection
 * Since UserLayout and GuestLayout are identical, this component
 * provides a single entry point for all pages.
 */
export default function AppLayout({ children, is404 = false }: AppLayoutProps) {
    // Use BaseLayout directly since UserLayout and GuestLayout are identical
    // This eliminates the need for conditional checks in every page component
    return (
        <BaseLayout is404={is404}>
            {children}
        </BaseLayout>
    );
}

