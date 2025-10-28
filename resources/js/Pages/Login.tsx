// @ts-nocheck
import React, { useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { FormEvent } from 'react';
import GuestLayout from '../Layouts/GuestLayout';

export default function Login() {
    const { auth } = usePage().props;
    const [formData, setFormData] = useState({
        email: '',
        password: '',
        login_with: 'PASSWORD',
        login_type: 'web'
    });
    const [error, setError] = useState('');
    const [processing, setProcessing] = useState(false);

    const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        setError('');
        setProcessing(true);
        
        // Use regular form submission since the API returns JSON
        fetch('/api/user/login', {
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
                    localStorage.setItem('auth_token', data.data.access_token);
                }
                // Redirect to products page by default (include token if present)
                const token = data.data?.access_token || '';
                if (token) {
                    window.location.href = `/products?token=${token}`;
                } else {
                    window.location.href = '/products';
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
            <Head title="Login" />
            <div className="flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
                <div className="max-w-md w-full space-y-8">
                    <div>
                        <h2 className="mt-6 text-center text-3xl font-extrabold text-gray-900">
                            Sign in to your account
                        </h2>
                        <p className="mt-2 text-center text-sm text-gray-600">
                            Or{' '}
                            <Link
                                href="/register"
                                className="font-medium text-indigo-600 hover:text-indigo-500"
                            >
                                create a new account
                            </Link>
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
                                <label htmlFor="email" className="block text-sm font-medium text-gray-700">
                                    Email Address
                                </label>
                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    autoComplete="email"
                                    required
                                    className="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    placeholder="Enter your email"
                                    value={formData.email}
                                    onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                                />
                            </div>
                            
                            <div>
                                <label htmlFor="password" className="block text-sm font-medium text-gray-700">
                                    Password
                                </label>
                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    autoComplete="current-password"
                                    required
                                    className="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    placeholder="Enter your password"
                                    value={formData.password}
                                    onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                                />
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
