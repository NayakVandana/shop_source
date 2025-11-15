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

export default function DiscountForm() {
    const [loading, setLoading] = useState(false);
    const [products, setProducts] = useState([]);
    const [errors, setErrors] = useState({});
    const [generalError, setGeneralError] = useState('');
    
    const [formData, setFormData] = useState({
        name: '',
        description: '',
        type: 'percentage',
        value: '',
        min_purchase_amount: '',
        max_discount_amount: '',
        start_date: '',
        end_date: '',
        usage_limit: '',
        is_active: true,
        product_ids: [],
    });
    
    const isEdit = typeof window !== 'undefined' && new URLSearchParams(window.location.search).has('id');
    const discountId = typeof window !== 'undefined' 
        ? new URLSearchParams(window.location.search).get('id') 
        : null;

    useEffect(() => {
        loadProducts();
        if (isEdit && discountId) {
            loadDiscount(discountId);
        }
    }, []);

    const loadProducts = async () => {
        try {
            const token = getToken();
            const res = await axios.post('/api/admin/products/index', { per_page: 1000 }, {
                headers: { AdminToken: token }
            });
            if (res.data && res.data.status) {
                const data = res.data.data;
                setProducts(Array.isArray(data?.data) ? data.data : []);
            }
        } catch (err) {
            console.error('Failed to load products:', err);
        }
    };

    const loadDiscount = async (id: string) => {
        setLoading(true);
        try {
            const token = getToken();
            const res = await axios.post('/api/admin/discounts/show', { id }, {
                headers: { AdminToken: token }
            });
            if (res.data && res.data.status) {
                const discount = res.data.data;
                setFormData({
                    name: discount.name || '',
                    description: discount.description || '',
                    type: discount.type || 'percentage',
                    value: discount.value || '',
                    min_purchase_amount: discount.min_purchase_amount || '',
                    max_discount_amount: discount.max_discount_amount || '',
                    start_date: discount.start_date ? new Date(discount.start_date).toISOString().slice(0, 16) : '',
                    end_date: discount.end_date ? new Date(discount.end_date).toISOString().slice(0, 16) : '',
                    usage_limit: discount.usage_limit || '',
                    is_active: discount.is_active !== undefined ? discount.is_active : true,
                    product_ids: discount.products?.map(p => p.id) || [],
                });
            }
        } catch (err) {
            alert('Failed to load discount');
        } finally {
            setLoading(false);
        }
    };

    // Remove token from URL immediately - use localStorage/cookies only
    useEffect(() => {
        try {
            const url = new URL(window.location.href);
            if (url.searchParams.has('token')) {
                // Extract token and save to localStorage if not already there
                const token = url.searchParams.get('token');
                if (token && !localStorage.getItem('admin_token')) {
                    localStorage.setItem('admin_token', token);
                }
                // Remove token from URL immediately
                url.searchParams.delete('token');
                window.history.replaceState({}, '', url.toString());
            }
        } catch (_) {}
    }, []);

    const getToken = () => {
        // Get token from localStorage/cookies only (not URL)
        return localStorage.getItem('admin_token') || '';
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

    const handleProductChange = (productId, checked) => {
        setFormData(prev => {
            const productIds = [...prev.product_ids];
            if (checked) {
                if (!productIds.includes(productId)) {
                    productIds.push(productId);
                }
            } else {
                const index = productIds.indexOf(productId);
                if (index > -1) {
                    productIds.splice(index, 1);
                }
            }
            return { ...prev, product_ids: productIds };
        });
    };

    const validateForm = () => {
        const validationErrors = {};
        
        if (!formData.name || (typeof formData.name === 'string' && formData.name.trim() === '')) {
            validationErrors.name = 'Discount name is required.';
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
                start_date: formData.start_date || null,
                end_date: formData.end_date || null,
            };
            
            const url = isEdit && discountId
                ? '/api/admin/discounts/update'
                : '/api/admin/discounts/store';
            
            if (isEdit && discountId) {
                submitData.id = discountId;
            }

            const res = await axios.post(url, submitData, {
                headers: { 
                    AdminToken: token,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });

            if (res.data && res.data.status) {
                router.visit('/admin/discounts');
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
                setGeneralError(err.response?.data?.message || 'Failed to save discount. Please try again.');
            }
        } finally {
            setLoading(false);
        }
    };


    return (
        <AdminLayout>
            <Head title={isEdit ? 'Edit Discount' : 'Create Discount'} />
            <div className="p-4 sm:p-6 lg:p-8">
                <div className="mb-6">
                    <Heading level={1}>{isEdit ? 'Edit Discount' : 'Create New Discount'}</Heading>
                    <Link href={`/admin/discounts`}>
                        <Text className="text-sm text-blue-600 hover:underline mt-2 inline-block">← Back to Discounts</Text>
                    </Link>
                </div>

                <Card>
                    <form onSubmit={handleSubmit}>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div className="md:col-span-2">
                                <FormInput
                                    title="Discount Name *"
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
                                    title="Usage Limit"
                                    name="usage_limit"
                                    type="number"
                                    min="0"
                                    value={formData.usage_limit}
                                    onChange={handleInputChange}
                                    error={errors.usage_limit}
                                    helpertext="Leave empty for unlimited usage"
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

                            <div className="md:col-span-2">
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Apply to Products
                                </label>
                                <div className="border border-gray-300 rounded-md p-4 max-h-64 overflow-y-auto">
                                    {products.length === 0 ? (
                                        <Text muted>No products available</Text>
                                    ) : (
                                        <div className="space-y-2">
                                            {products.map((product) => (
                                                <label key={product.id} className="flex items-center space-x-2 cursor-pointer hover:bg-gray-50 p-2 rounded">
                                                    <input
                                                        type="checkbox"
                                                        checked={formData.product_ids.includes(product.id)}
                                                        onChange={(e) => handleProductChange(product.id, e.target.checked)}
                                                        className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                    />
                                                    <span className="text-sm text-gray-700">{product.name}</span>
                                                </label>
                                            ))}
                                        </div>
                                    )}
                                </div>
                                <Text className="text-xs text-gray-500 mt-1">
                                    Select products to apply this discount to. Leave empty to apply to all products.
                                </Text>
                            </div>
                        </div>

                        <div className="mt-6 flex gap-4">
                            <Button type="submit" loading={loading}>
                                {isEdit ? 'Update Discount' : 'Create Discount'}
                            </Button>
                            <Link href={`/admin/discounts`}>
                                <Button variant="outline" type="button">Cancel</Button>
                            </Link>
                        </div>
                    </form>
                </Card>
            </div>
        </AdminLayout>
    );
}

