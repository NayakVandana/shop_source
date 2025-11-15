// @ts-nocheck
import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import { FormEvent } from 'react';
import GuestLayout from '../../../Layouts/GuestLayout';
import FormInput from '../../../Components/FormInputs/FormInput';
import Button from '../../../Components/ui/Button';

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
        
        // Convert user_id to number
        const submitData = {
            user_id: parseInt(formData.user_id) || 0,
            login_type: formData.login_type
        };
        
        console.log('Sending login request with:', submitData);
        
        fetch('/api/admin/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'include',
            body: JSON.stringify(submitData)
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            setProcessing(false);
            if (data.status) {
                // Store token in localStorage (cookie is already set by backend)
                if (data.data && data.data.access_token) {
                    localStorage.setItem('admin_token', data.data.access_token);
                    console.log('✅ Admin token stored in localStorage:', data.data.access_token.substring(0, 20) + '...');
                }
                
                // Redirect without token in URL - use localStorage/cookies only
                // Small delay to ensure cookie is set in browser
                setTimeout(() => {
                    console.log('🔄 Redirecting to admin dashboard');
                    window.location.href = '/admin/dashboard';
                }, 100);
            } else {
                // Show error messages with more details
                const errorMsg = data.message || 'Login failed';
                const errors = data.data ? JSON.stringify(data.data) : '';
                setError(`${errorMsg}${errors ? ' - ' + errors : ''}`);
                console.error('Login failed:', data);
            }
        })
        .catch(error => {
            setProcessing(false);
            console.error('Login error:', error);
            setError('An error occurred during login. Please check your connection and try again.');
        });
    };

    return (
        <GuestLayout>
            <Head title="Admin Login" />
            <div className="flex items-center justify-center py-8 px-4 sm:py-12 sm:px-6 lg:px-8 min-h-[calc(100vh-200px)]">
                <div className="max-w-md w-full space-y-6 sm:space-y-8">
                    <div>
                        <h2 className="mt-4 sm:mt-6 text-center text-2xl sm:text-3xl font-extrabold text-gray-900">
                            Admin Sign In
                        </h2>
                        <p className="mt-2 text-center text-xs sm:text-sm text-gray-600">
                            Sign in to access the admin panel
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
                                id="user_id"
                                name="user_id"
                                type="text"
                                required
                                placeholder="Enter your admin user ID (e.g., 1)"
                                value={formData.user_id}
                                onChange={(e) => setFormData({ ...formData, user_id: e.target.value })}
                                title="Admin User ID"
                                helpertext="Enter your admin user ID from the database"
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
        </GuestLayout>
    );
}

