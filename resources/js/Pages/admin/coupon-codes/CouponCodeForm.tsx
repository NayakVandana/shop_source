// @ts-nocheck
import React, { useEffect, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import axios from 'axios';
import AdminLayout from '../../../Layouts/AdminLayout';
import Card from '../../../Components/ui/Card';
import Button from '../../../Components/ui/Button';
import { Heading, Text } from '../../../Components/ui/Typography';
import FormInput from '../../../Components/FormInputs/FormInput';
import FormTextarea from '../../../Components/FormInputs/FormTextarea';
import FormSelect from '../../../Components/FormInputs/FormSelect';
import FormCheckbox from '../../../Components/FormInputs/FormCheckbox';

export default function CouponCodeForm() {
    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState({});
    const [generalError, setGeneralError] = useState('');
    
    const [formData, setFormData] = useState({
        code: '',
        name: '',
        description: '',
        type: 'percentage',
        value: '',
        min_purchase_amount: '',
        max_discount_amount: '',
        start_date: '',
        end_date: '',
        usage_limit: '',
        usage_limit_per_user: '',
        is_active: true,
    });
    
    const isEdit = typeof window !== 'undefined' && new URLSearchParams(window.location.search).has('id');
    const couponId = typeof window !== 'undefined' 
        ? new URLSearchParams(window.location.search).get('id') 
        : null;

    useEffect(() => {
        if (isEdit && couponId) {
            loadCoupon(couponId);
        }
    }, []);

    const loadCoupon = async (id: string) => {
        setLoading(true);
        try {
            const token = getToken();
            const res = await axios.post('/api/admin/coupon-codes/show', { id }, {
                headers: { AdminToken: token }
            });
            if (res.data && res.data.status) {
                const coupon = res.data.data;
                setFormData({
                    code: coupon.code || '',
                    name: coupon.name || '',
                    description: coupon.description || '',
                    type: coupon.type || 'percentage',
                    value: coupon.value || '',
                    min_purchase_amount: coupon.min_purchase_amount || '',
                    max_discount_amount: coupon.max_discount_amount || '',
                    start_date: coupon.start_date ? new Date(coupon.start_date).toISOString().slice(0, 16) : '',
                    end_date: coupon.end_date ? new Date(coupon.end_date).toISOString().slice(0, 16) : '',
                    usage_limit: coupon.usage_limit || '',
                    usage_limit_per_user: coupon.usage_limit_per_user || '',
                    is_active: coupon.is_active !== undefined ? coupon.is_active : true,
                });
            }
        } catch (err) {
            alert('Failed to load coupon code');
        } finally {
            setLoading(false);
        }
    };

    const getToken = () => {
        if (typeof window === 'undefined') return '';
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('token') || localStorage.getItem('admin_token') || '';
    };

    const handleInputChange = (e) => {
        const { name, value, type, checked } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: type === 'checkbox' ? checked : value
        }));
        if (errors[name]) {
            setErrors(prev => {
                const newErrors = { ...prev };
                delete newErrors[name];
                return newErrors;
            });
        }
        if (generalError) {
            setGeneralError('');
        }
    };

    const generateCode = () => {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        let code = '';
        for (let i = 0; i < 8; i++) {
            code += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        setFormData(prev => ({ ...prev, code }));
    };

    const validateForm = () => {
        const validationErrors = {};
        
        if (!formData.name || (typeof formData.name === 'string' && formData.name.trim() === '')) {
            validationErrors.name = 'Coupon name is required.';
        }
        
        if (!formData.code || (typeof formData.code === 'string' && formData.code.trim() === '')) {
            validationErrors.code = 'Coupon code is required.';
        }
        
        if (!formData.type) {
            validationErrors.type = 'Discount type is required.';
        }
        
        if (!formData.value || formData.value === '' || formData.value === null || formData.value === undefined) {
            validationErrors.value = 'Discount value is required.';
        } else {
            const value = parseFloat(formData.value);
            if (isNaN(value) || value < 0) {
                validationErrors.value = 'Value must be a valid number (0 or greater).';
            }
            if (formData.type === 'percentage' && value > 100) {
                validationErrors.value = 'Percentage cannot exceed 100%.';
            }
        }
        
        if (formData.min_purchase_amount && formData.min_purchase_amount !== '' && formData.min_purchase_amount !== null && formData.min_purchase_amount !== undefined) {
            const minAmount = parseFloat(formData.min_purchase_amount);
            if (isNaN(minAmount) || minAmount < 0) {
                validationErrors.min_purchase_amount = 'Minimum purchase amount must be a valid number (0 or greater).';
            }
        }
        
        if (formData.max_discount_amount && formData.max_discount_amount !== '' && formData.max_discount_amount !== null && formData.max_discount_amount !== undefined) {
            const maxAmount = parseFloat(formData.max_discount_amount);
            if (isNaN(maxAmount) || maxAmount < 0) {
                validationErrors.max_discount_amount = 'Maximum discount amount must be a valid number (0 or greater).';
            }
        }
        
        if (formData.start_date && formData.end_date) {
            if (new Date(formData.start_date) > new Date(formData.end_date)) {
                validationErrors.end_date = 'End date must be after start date.';
            }
        }
        
        if (formData.usage_limit && formData.usage_limit !== '' && formData.usage_limit !== null && formData.usage_limit !== undefined) {
            const limit = parseInt(formData.usage_limit);
            if (isNaN(limit) || limit < 0) {
                validationErrors.usage_limit = 'Usage limit must be a valid number (0 or greater).';
            }
        }
        
        if (formData.usage_limit_per_user && formData.usage_limit_per_user !== '' && formData.usage_limit_per_user !== null && formData.usage_limit_per_user !== undefined) {
            const limit = parseInt(formData.usage_limit_per_user);
            if (isNaN(limit) || limit < 0) {
                validationErrors.usage_limit_per_user = 'Per user usage limit must be a valid number (0 or greater).';
            }
        }
        
        return validationErrors;
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setErrors({});
        setGeneralError('');

        // Client-side validation
        const validationErrors = validateForm();
        if (Object.keys(validationErrors).length > 0) {
            setErrors(validationErrors);
            setLoading(false);
            
            // Scroll to first error
            setTimeout(() => {
                const firstErrorField = Object.keys(validationErrors)[0];
                if (firstErrorField) {
                    const element = document.querySelector(`[name="${firstErrorField}"]`);
                    if (element) {
                        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        element.focus();
                    }
                }
            }, 100);
            return;
        }
        
        try {
            const token = getToken();
            const submitData = {
                ...formData,
                value: parseFloat(formData.value),
                min_purchase_amount: formData.min_purchase_amount ? parseFloat(formData.min_purchase_amount) : null,
                max_discount_amount: formData.max_discount_amount ? parseFloat(formData.max_discount_amount) : null,
                usage_limit: formData.usage_limit ? parseInt(formData.usage_limit) : null,
                usage_limit_per_user: formData.usage_limit_per_user ? parseInt(formData.usage_limit_per_user) : null,
                start_date: formData.start_date || null,
                end_date: formData.end_date || null,
            };
            
            const url = isEdit && couponId
                ? '/api/admin/coupon-codes/update'
                : '/api/admin/coupon-codes/store';
            
            if (isEdit && couponId) {
                submitData.id = couponId;
            }

            const res = await axios.post(url, submitData, {
                headers: { 
                    AdminToken: token,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });

            if (res.data && res.data.status) {
                const tokenQuery = token ? `?token=${token}` : '';
                router.visit(`/admin/coupon-codes${tokenQuery}`);
            } else {
                const errorData = res.data?.data?.errors || {};
                setErrors(errorData);
                if (res.data?.message) {
                    setErrors({ ...errorData, _general: res.data.message });
                }
                setGeneralError(res.data?.message || 'Please fix the errors below and try again.');
                
                // Scroll to first error
                setTimeout(() => {
                    const firstErrorField = Object.keys(errorData)[0];
                    if (firstErrorField) {
                        const element = document.querySelector(`[name="${firstErrorField}"]`);
                        if (element) {
                            element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            element.focus();
                        }
                    }
                }, 100);
            }
        } catch (err) {
            if (err.response?.data?.data?.errors) {
                const errorData = err.response.data.data.errors;
                setErrors(errorData);
                setGeneralError(err.response?.data?.message || 'Validation failed. Please check the errors below.');
                
                // Scroll to first error
                setTimeout(() => {
                    const firstErrorField = Object.keys(errorData)[0];
                    if (firstErrorField) {
                        const element = document.querySelector(`[name="${firstErrorField}"]`);
                        if (element) {
                            element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            element.focus();
                        }
                    }
                }, 100);
            } else {
                setGeneralError(err.response?.data?.message || 'Failed to save coupon code. Please try again.');
            }
        } finally {
            setLoading(false);
        }
    };

    const tokenParam = typeof window !== 'undefined' 
        ? (new URLSearchParams(window.location.search).get('token') || localStorage.getItem('admin_token') || '')
        : '';
    const tokenQuery = tokenParam ? `?token=${tokenParam}` : '';

    return (
        <AdminLayout>
            <Head title={isEdit ? 'Edit Coupon Code' : 'Create Coupon Code'} />
            <div className="p-4 sm:p-6 lg:p-8">
                <div className="mb-6">
                    <Heading level={1}>{isEdit ? 'Edit Coupon Code' : 'Create New Coupon Code'}</Heading>
                    <Link href={`/admin/coupon-codes${tokenQuery}`}>
                        <Text className="text-sm text-blue-600 hover:underline mt-2 inline-block">← Back to Coupon Codes</Text>
                    </Link>
                </div>

                <Card>
                    <form onSubmit={handleSubmit}>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div className="md:col-span-2">
                                <div className="flex gap-2">
                                    <div className="flex-1">
                                        <FormInput
                                            title="Coupon Code *"
                                            name="code"
                                            value={formData.code}
                                            onChange={handleInputChange}
                                            error={errors.code}
                                            placeholder="e.g., SAVE20"
                                        />
                                    </div>
                                    {!isEdit && (
                                        <div className="flex items-end">
                                            <Button type="button" variant="outline" onClick={generateCode}>
                                                Generate
                                            </Button>
                                        </div>
                                    )}
                                </div>
                                <Text className="text-xs text-gray-500 mt-1">
                                    Leave empty to auto-generate a random code
                                </Text>
                            </div>

                            <div className="md:col-span-2">
                                <FormInput
                                    title="Coupon Name *"
                                    name="name"
                                    value={formData.name}
                                    onChange={handleInputChange}
                                    error={errors.name}
                                />
                            </div>

                            <div className="md:col-span-2">
                                <FormTextarea
                                    title="Description"
                                    name="description"
                                    value={formData.description}
                                    onChange={handleInputChange}
                                    error={errors.description}
                                    rows={3}
                                />
                            </div>

                            <div>
                                <FormSelect
                                    title="Discount Type *"
                                    name="type"
                                    value={formData.type}
                                    onChange={handleInputChange}
                                    error={errors.type}
                                >
                                    <option value="percentage">Percentage</option>
                                    <option value="fixed">Fixed Amount</option>
                                </FormSelect>
                            </div>

                            <div>
                                <FormInput
                                    title={`Discount Value ${formData.type === 'percentage' ? '(%)' : '($)'} *`}
                                    name="value"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max={formData.type === 'percentage' ? '100' : undefined}
                                    value={formData.value}
                                    onChange={handleInputChange}
                                    error={errors.value}
                                />
                            </div>

                            <div>
                                <FormInput
                                    title="Minimum Purchase Amount ($)"
                                    name="min_purchase_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={formData.min_purchase_amount}
                                    onChange={handleInputChange}
                                    error={errors.min_purchase_amount}
                                />
                            </div>

                            <div>
                                <FormInput
                                    title="Maximum Discount Amount ($)"
                                    name="max_discount_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={formData.max_discount_amount}
                                    onChange={handleInputChange}
                                    error={errors.max_discount_amount}
                                />
                            </div>

                            <div>
                                <FormInput
                                    title="Start Date"
                                    name="start_date"
                                    type="datetime-local"
                                    value={formData.start_date}
                                    onChange={handleInputChange}
                                    error={errors.start_date}
                                />
                            </div>

                            <div>
                                <FormInput
                                    title="End Date"
                                    name="end_date"
                                    type="datetime-local"
                                    value={formData.end_date}
                                    onChange={handleInputChange}
                                    error={errors.end_date}
                                />
                            </div>

                            <div>
                                <FormInput
                                    title="Usage Limit (Total)"
                                    name="usage_limit"
                                    type="number"
                                    min="0"
                                    value={formData.usage_limit}
                                    onChange={handleInputChange}
                                    error={errors.usage_limit}
                                    helpertext="Leave empty for unlimited usage"
                                />
                            </div>

                            <div>
                                <FormInput
                                    title="Usage Limit Per User"
                                    name="usage_limit_per_user"
                                    type="number"
                                    min="0"
                                    value={formData.usage_limit_per_user}
                                    onChange={handleInputChange}
                                    error={errors.usage_limit_per_user}
                                    helpertext="Leave empty for unlimited per user"
                                />
                            </div>

                            <div className="md:col-span-2">
                                <FormCheckbox
                                    title="Active"
                                    name="is_active"
                                    checked={formData.is_active}
                                    onChange={handleInputChange}
                                />
                            </div>
                        </div>

                        <div className="mt-6 flex gap-4">
                            <Button type="submit" loading={loading}>
                                {isEdit ? 'Update Coupon Code' : 'Create Coupon Code'}
                            </Button>
                            <Link href={`/admin/coupon-codes${tokenQuery}`}>
                                <Button variant="outline" type="button">Cancel</Button>
                            </Link>
                        </div>
                    </form>
                </Card>
            </div>
        </AdminLayout>
    );
}

