// @ts-nocheck
import React, { useState } from 'react';
import { Head, Link, usePage, router } from '@inertiajs/react';
import { FormEvent } from 'react';
import AppLayout from '../../../Layouts/AppLayout';
import FormInput from '../../../Components/FormInputs/FormInput';
import Button from '../../../Components/ui/Button';

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
                // Store token in localStorage (cookie is already set by backend)
                if (data.data && data.data.access_token) {
                    localStorage.setItem('auth_token', data.data.access_token);
                    console.log('✅ Token stored in localStorage:', data.data.access_token.substring(0, 20) + '...');
                }
                
                // Redirect to intended page or products page
                const urlParams = new URLSearchParams(window.location.search);
                const redirect = urlParams.get('redirect') || '/products';
                
                // Notify cart to refresh (cart will be merged on backend)
                localStorage.setItem('cart_updated', Date.now().toString());
                
                // Use full page reload to ensure cookie is sent with Inertia request
                // Small delay to ensure cookie is set in browser
                setTimeout(() => {
                    console.log('🔄 Redirecting to:', redirect);
                    window.location.href = redirect;
                }, 100);
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
        <AppLayout>
            <Head title="Login" />
            <div className="flex items-center justify-center py-8 px-4 sm:py-12 sm:px-6 lg:px-8 min-h-[calc(100vh-200px)]">
                <div className="max-w-md w-full space-y-6 sm:space-y-8">
                    <div>
                        <h2 className="mt-4 sm:mt-6 text-center text-2xl sm:text-3xl font-extrabold text-gray-900">
                            Sign in to your account
                        </h2>
                        <p className="mt-2 text-center text-xs sm:text-sm text-gray-600">
                            Or{' '}
                            <Link
                                href="/register"
                                className="font-medium text-indigo-600 hover:text-indigo-500 touch-manipulation"
                            >
                                create a new account
                            </Link>
                        </p>
                    </div>
                    <form className="mt-6 sm:mt-8 space-y-5 sm:space-y-6" onSubmit={handleSubmit}>
                        {error && (
                            <div className="bg-red-50 border border-red-200 text-red-600 px-3 sm:px-4 py-2.5 sm:py-3 rounded-md text-sm sm:text-base">
                                {error}
                            </div>
                        )}
                        
                        <div className="space-y-4 sm:space-y-5">
                            <FormInput
                                id="email"
                                name="email"
                                type="email"
                                autoComplete="email"
                                required
                                placeholder="Enter your email"
                                value={formData.email}
                                onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                                title="Email Address"
                            />
                            
                            <FormInput
                                id="password"
                                name="password"
                                type="password"
                                autoComplete="current-password"
                                required
                                placeholder="Enter your password"
                                value={formData.password}
                                onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                                title="Password"
                            />
                        </div>

                        <div>
                            <Button
                                type="submit"
                                disabled={processing}
                                block
                            >
                                {processing ? 'Signing In...' : 'Sign In'}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}

