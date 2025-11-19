import React from 'react';
import { Head, usePage } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import AdminLayout from '../../Layouts/AdminLayout';

export default function PageNotFound() {
    const isAdminRoute = typeof window !== 'undefined' && window.location?.pathname?.startsWith('/admin');

    return (
        <>
            <Head title="404 - Not Found" />
            {isAdminRoute
                ? <AdminLayout is404>{null}</AdminLayout>
                : <AppLayout is404>{null}</AppLayout>}
        </>
    );
}


