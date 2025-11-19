// @ts-nocheck
import React, { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import axios from 'axios';
import AppLayout from '../../../Layouts/AppLayout';
import FormInput from '../../../Components/FormInputs/FormInput';
import Button from '../../../Components/ui/Button';
import Card from '../../../Components/ui/Card';
import { Heading, Text } from '../../../Components/ui/Typography';

export default function Profile() {
    const [formData, setFormData] = useState({
        name: '',
        email: '',
        mobile: ''
    });
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    const [loading, setLoading] = useState(true);
    const [processing, setProcessing] = useState(false);

    useEffect(() => {
        loadProfile();
    }, []);

    const loadProfile = async () => {
        setLoading(true);
        setError('');
        try {
            const token = localStorage.getItem('auth_token') || '';
            
            const res = await axios.post('/api/user/profile', {}, {
                headers: { 
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                withCredentials: true
            });
            
            if (res.data && res.data.status) {
                const user = res.data.data;
                setFormData({
                    name: user.name || '',
                    email: user.email || '',
                    mobile: user.mobile || ''
                });
                setError('');
            } else {
                setError(res.data?.message || 'Failed to load profile');
            }
        } catch (err: any) {
            const errorMsg = err.response?.data?.message || err.message || 'Failed to load profile';
            setError(errorMsg);
            console.error('Error loading profile:', err);
        } finally {
            setLoading(false);
        }
    };

    const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        setError('');
        setSuccess('');
        setProcessing(true);
        
        try {
            const token = localStorage.getItem('auth_token') || '';
            
            const res = await axios.post('/api/user/profile/update', formData, {
                headers: { 
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                withCredentials: true
            });
            
            if (res.data && res.data.status) {
                setSuccess('Profile updated successfully!');
                setError('');
                // Update form data with response
                const user = res.data.data;
                setFormData({
                    name: user.name || '',
                    email: user.email || '',
                    mobile: user.mobile || ''
                });
            } else {
                const errorMsg = res.data?.message || 'Failed to update profile';
                const errors = res.data?.data?.errors;
                if (errors) {
                    const errorMessages = Object.values(errors).flat().join(', ');
                    setError(errorMessages);
                } else {
                    setError(errorMsg);
                }
            }
        } catch (err: any) {
            const errorMsg = err.response?.data?.message || err.message || 'An error occurred';
            const errors = err.response?.data?.data?.errors;
            if (errors) {
                const errorMessages = Object.values(errors).flat().join(', ');
                setError(errorMessages);
            } else {
                setError(errorMsg);
            }
            console.error('Profile update error:', err);
        } finally {
            setProcessing(false);
        }
    };

    if (loading) {
        return (
            <AppLayout>
                <Head title="Profile" />
                <div className="flex items-center justify-center py-8 px-4 sm:py-12 sm:px-6 lg:px-8 min-h-[calc(100vh-200px)]">
                    <div className="text-center">
                        <div className="text-indigo-600 text-lg sm:text-xl md:text-2xl">Loading profile...</div>
                    </div>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout>
            <Head title="Profile" />
            <div className="flex items-center justify-center py-8 px-4 sm:py-12 sm:px-6 lg:px-8 min-h-[calc(100vh-200px)]">
                <div className="max-w-md w-full">
                    <Card>
                        <div className="mb-6">
                            <Heading level={1} className="text-2xl sm:text-3xl font-extrabold text-gray-900">
                                Update Profile
                            </Heading>
                            <Text className="mt-2 text-sm text-gray-600">
                                Update your user information
                            </Text>
                        </div>

                        <form className="space-y-5 sm:space-y-6" onSubmit={handleSubmit}>
                            {error && (
                                <div className="bg-red-50 border border-red-200 text-red-600 px-3 sm:px-4 py-2.5 sm:py-3 rounded-md text-sm sm:text-base">
                                    {error}
                                </div>
                            )}

                            {success && (
                                <div className="bg-green-50 border border-green-200 text-green-600 px-3 sm:px-4 py-2.5 sm:py-3 rounded-md text-sm sm:text-base">
                                    {success}
                                </div>
                            )}
                            
                            <div className="space-y-4 sm:space-y-5">
                                <FormInput
                                    id="name"
                                    name="name"
                                    type="text"
                                    required
                                    placeholder="Enter your full name"
                                    value={formData.name}
                                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                    title="User Name"
                                />
                                
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
                                    id="mobile"
                                    name="mobile"
                                    type="tel"
                                    placeholder="Enter your mobile number"
                                    value={formData.mobile}
                                    onChange={(e) => setFormData({ ...formData, mobile: e.target.value })}
                                    title="Mobile Number"
                                />
                            </div>

                            <div>
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    block
                                >
                                    {processing ? 'Updating...' : 'Apply Changes'}
                                </Button>
                            </div>
                        </form>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}

