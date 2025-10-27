// @ts-nocheck
import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import { FormEvent } from 'react';
import GuestLayout from '../../Layouts/GuestLayout';

export default function AdminLogin() {
    const [formData, setFormData] = useState({
        user_id: '',
        login_type: 'web'
    });
    const [error, setError] = useState('');
    const [processing, setProcessing] = useState(false);

    const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        setError('');
        setProcessing(true);
        
        fetch('/api/admin/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'include',
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            setProcessing(false);
            if (data.status) {
                // Store the token if provided
                if (data.data && data.data.access_token) {
                    localStorage.setItem('admin_token', data.data.access_token);
                }
                // Redirect to admin dashboard with token
                const token = data.data?.access_token || '';
                if (token) {
                    window.location.href = `/admin/dashboard?token=${token}`;
                } else {
                    window.location.href = '/admin/dashboard';
                }
            } else {
                // Show error messages
                setError(data.message || 'Login failed');
            }
        })
        .catch(error => {
            setProcessing(false);
            console.error('Login error:', error);
            setError('An error occurred during login');
        });
    };

    return (
        <GuestLayout>
            <Head title="Admin Login" />
            <div className="flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
                <div className="max-w-md w-full space-y-8">
                    <div>
                        <h2 className="mt-6 text-center text-3xl font-extrabold text-gray-900">
                            Admin Sign In
                        </h2>
                        <p className="mt-2 text-center text-sm text-gray-600">
                            Sign in to access the admin panel
                        </p>
                    </div>
                    <form className="mt-8 space-y-6" onSubmit={handleSubmit}>
                        {error && (
                            <div className="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-md">
                                {error}
                            </div>
                        )}
                        
                        <div className="space-y-4">
                            <div>
                                <label htmlFor="user_id" className="block text-sm font-medium text-gray-700">
                                    Admin User ID
                                </label>
                                <input
                                    id="user_id"
                                    name="user_id"
                                    type="number"
                                    required
                                    className="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    placeholder="Enter your admin user ID"
                                    value={formData.user_id}
                                    onChange={(e) => setFormData({ ...formData, user_id: e.target.value })}
                                />
                                <p className="mt-1 text-xs text-gray-500">
                                    Enter your admin user ID
                                </p>
                            </div>
                        </div>

                        <div>
                            <button
                                type="submit"
                                disabled={processing}
                                className="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {processing ? 'Signing In...' : 'Sign In'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </GuestLayout>
    );
}

