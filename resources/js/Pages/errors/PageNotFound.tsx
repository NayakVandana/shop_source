import React from 'react';
import { Head, usePage } from '@inertiajs/react';
import UserLayout from '../../Layouts/UserLayout';
import GuestLayout from '../../Layouts/GuestLayout';
import AdminLayout from '../../Layouts/AdminLayout';

export default function PageNotFound() {
    const { auth } = usePage().props as any;
    const user = auth?.user;

    const isAdminRoute = typeof window !== 'undefined' && window.location?.pathname?.startsWith('/admin');

    return (
        <>
            <Head title="404 - Not Found" />
            {isAdminRoute
                ? <AdminLayout is404>{null}</AdminLayout>
                : (user ? <UserLayout is404>{null}</UserLayout> : <GuestLayout is404>{null}</GuestLayout>)}
        </>
    );
}


