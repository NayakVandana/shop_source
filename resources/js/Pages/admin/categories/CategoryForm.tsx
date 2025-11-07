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
import FormCheckbox from '../../../Components/FormInputs/FormCheckbox';

export default function CategoryForm() {
    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState({});
    const [generalError, setGeneralError] = useState('');
    
    const [formData, setFormData] = useState({
        name: '',
        description: '',
        slug: '',
        sort_order: '0',
        is_active: true,
    });
    
    const [imagePreview, setImagePreview] = useState(null);
    const [videoPreview, setVideoPreview] = useState(null);
    
    const isEdit = typeof window !== 'undefined' && new URLSearchParams(window.location.search).has('id');
    const categoryId = typeof window !== 'undefined' 
        ? new URLSearchParams(window.location.search).get('id') 
        : null;

    useEffect(() => {
        if (isEdit && categoryId) {
            loadCategory(categoryId);
        }
    }, []);

    const loadCategory = async (id: string) => {
        setLoading(true);
        try {
            const token = getToken();
            const res = await axios.post('/api/admin/categories/show', { id }, {
                headers: { AdminToken: token }
            });
            if (res.data && res.data.success) {
                const category = res.data.data;
                setFormData({
                    name: category.name || '',
                    description: category.description || '',
                    slug: category.slug || '',
                    sort_order: category.sort_order || '0',
                    is_active: category.is_active !== undefined ? category.is_active : true,
                });
                
                // Load existing image
                if (category.image) {
                    const imageUrl = category.image.startsWith('http') 
                        ? category.image 
                        : `/storage/${category.image}`;
                    setImagePreview(imageUrl);
                }
                
                // Load existing video
                if (category.video) {
                    const videoUrl = category.video.startsWith('http') 
                        ? category.video 
                        : `/storage/${category.video}`;
                    setVideoPreview(videoUrl);
                }
            }
        } catch (err) {
            alert('Failed to load category');
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
        // Clear field error when user starts typing
        if (errors[name]) {
            setErrors(prev => {
                const newErrors = { ...prev };
                delete newErrors[name];
                return newErrors;
            });
        }
        // Clear general error when user makes changes
        if (generalError) {
            setGeneralError('');
        }
    };

    const handleImageChange = (e) => {
        const file = e.target.files?.[0];
        if (file) {
            // Validate file type
            if (!file.type.startsWith('image/')) {
                setErrors(prev => ({ ...prev, image: 'Please select a valid image file' }));
                return;
            }
            // Validate file size (5MB max)
            if (file.size > 5 * 1024 * 1024) {
                setErrors(prev => ({ ...prev, image: 'Image size must be less than 5MB' }));
                return;
            }
            // Clear error if validation passes
            if (errors.image) {
                setErrors(prev => {
                    const newErrors = { ...prev };
                    delete newErrors.image;
                    return newErrors;
                });
            }
            setImagePreview(URL.createObjectURL(file));
            setFormData(prev => ({ ...prev, image: file }));
        }
    };

    const handleVideoChange = (e) => {
        const file = e.target.files?.[0];
        if (file) {
            // Validate file type
            const validVideoTypes = ['video/mp4', 'video/avi', 'video/quicktime', 'video/x-msvideo', 'video/x-ms-wmv', 'video/x-flv', 'video/webm'];
            if (!validVideoTypes.includes(file.type) && !file.name.match(/\.(mp4|avi|mov|wmv|flv|webm)$/i)) {
                setErrors(prev => ({ ...prev, video: 'Please select a valid video file (MP4, AVI, MOV, WMV, FLV, or WEBM)' }));
                return;
            }
            // Validate file size (10MB max)
            if (file.size > 10 * 1024 * 1024) {
                setErrors(prev => ({ ...prev, video: 'Video size must be less than 10MB' }));
                return;
            }
            // Clear error if validation passes
            if (errors.video) {
                setErrors(prev => {
                    const newErrors = { ...prev };
                    delete newErrors.video;
                    return newErrors;
                });
            }
            setVideoPreview(URL.createObjectURL(file));
            setFormData(prev => ({ ...prev, video: file }));
        }
    };

    const validateForm = () => {
        const validationErrors = {};
        
        // Required field validations
        if (!formData.name || (typeof formData.name === 'string' && formData.name.trim() === '')) {
            validationErrors.name = 'Category name is required.';
        }
        
        // Validate sort_order if provided
        if (formData.sort_order !== '' && formData.sort_order !== null && formData.sort_order !== undefined) {
            const sortOrder = parseInt(formData.sort_order);
            if (isNaN(sortOrder) || sortOrder < 0) {
                validationErrors.sort_order = 'Sort order must be a valid number (0 or greater).';
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
            setGeneralError('Please fill in all required fields correctly.');
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
            const formDataToSend = new FormData();
            
            Object.keys(formData).forEach(key => {
                if (formData[key] !== '' && formData[key] !== null && formData[key] !== undefined) {
                    if (typeof formData[key] === 'boolean') {
                        formDataToSend.append(key, formData[key] ? '1' : '0');
                    } else {
                        formDataToSend.append(key, formData[key]);
                    }
                }
            });

            if (formData.image) {
                formDataToSend.append('image', formData.image);
            }
            if (formData.video) {
                formDataToSend.append('video', formData.video);
            }

            const url = isEdit 
                ? '/api/admin/categories/update' 
                : '/api/admin/categories/store';
            
            if (isEdit) {
                formDataToSend.append('id', categoryId);
            }

            const res = await axios.post(url, formDataToSend, {
                headers: {
                    AdminToken: token,
                    'Content-Type': 'multipart/form-data'
                }
            });

            if (res.data && res.data.success) {
                const tokenQuery = token ? `?token=${token}` : '';
                router.visit(`/admin/categories${tokenQuery}`);
            } else {
                const errorData = res.data?.data?.errors || {};
                setErrors(errorData);
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
                setGeneralError(err.message || 'Failed to save category. Please try again.');
            }
        } finally {
            setLoading(false);
        }
    };

    const tokenParam = getToken();
    const tokenQuery = tokenParam ? `?token=${tokenParam}` : '';

    if (loading && isEdit) {
        return (
            <AdminLayout>
                <Head title="Loading..." />
                <div className="p-6 text-center">
                    <Text>Loading category...</Text>
                </div>
            </AdminLayout>
        );
    }

    return (
        <AdminLayout>
            <Head title={isEdit ? 'Edit Category' : 'Create Category'} />
            <div className="p-4 sm:p-6 lg:p-8">
                <div className="mb-6">
                    <Link href={`/admin/categories${tokenQuery}`} className="text-primary-600 hover:text-primary-700 mb-4 inline-block">
                        ← Back to Categories
                    </Link>
                    <Heading level={1}>{isEdit ? 'Edit Category' : 'Create New Category'}</Heading>
                </div>

                {generalError && (
                    <Card className="mb-6 bg-red-50 border-red-200">
                        <Text className="text-red-800">{generalError}</Text>
                    </Card>
                )}

                <form onSubmit={handleSubmit} noValidate>
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Main Form */}
                        <div className="lg:col-span-2 space-y-6">
                            {/* Basic Information */}
                            <Card>
                                <Heading level={2} className="mb-4">Basic Information</Heading>
                                
                                <div className="space-y-4">
                                    <FormInput
                                        type="text"
                                        name="name"
                                        value={formData.name}
                                        onChange={handleInputChange}
                                        title="Category Name *"
                                        error={errors.name}
                                    />

                                    <FormInput
                                        type="text"
                                        name="slug"
                                        value={formData.slug}
                                        onChange={handleInputChange}
                                        title="Slug"
                                        error={errors.slug}
                                        helpText="Leave empty to auto-generate from name"
                                    />

                                    <FormTextarea
                                        name="description"
                                        value={formData.description}
                                        onChange={handleInputChange}
                                        rows={5}
                                        title="Description"
                                        error={errors.description}
                                    />

                                    <FormInput
                                        type="number"
                                        name="sort_order"
                                        value={formData.sort_order}
                                        onChange={handleInputChange}
                                        min="0"
                                        title="Sort Order"
                                        error={errors.sort_order}
                                        helpText="Lower numbers appear first"
                                    />
                                </div>
                            </Card>
                        </div>

                        {/* Sidebar */}
                        <div className="space-y-6">
                            {/* Image */}
                            <Card>
                                <Heading level={2} className="mb-4">Image</Heading>
                                <div className="space-y-4">
                                    <FormInput
                                        type="file"
                                        accept="image/*"
                                        onChange={handleImageChange}
                                        title="Category Image"
                                        error={errors.image}
                                    />
                                    {imagePreview && (
                                        <img
                                            src={imagePreview}
                                            alt="Preview"
                                            className="mt-2 w-full h-32 object-cover rounded"
                                        />
                                    )}
                                </div>
                            </Card>

                            {/* Video */}
                            <Card>
                                <Heading level={2} className="mb-4">Video</Heading>
                                <div className="space-y-4">
                                    <FormInput
                                        type="file"
                                        accept="video/mp4,video/avi,video/quicktime,video/x-msvideo,video/x-ms-wmv,video/x-flv,video/webm"
                                        onChange={handleVideoChange}
                                        title="Category Video"
                                        error={errors.video}
                                    />
                                    {videoPreview && (
                                        <video
                                            src={videoPreview}
                                            controls
                                            className="mt-2 w-full h-32 object-cover rounded"
                                        >
                                            Your browser does not support the video tag.
                                        </video>
                                    )}
                                </div>
                            </Card>

                            {/* Status */}
                            <Card>
                                <Heading level={2} className="mb-4">Status</Heading>
                                <div className="space-y-3">
                                    <FormCheckbox
                                        name="is_active"
                                        checked={formData.is_active}
                                        onChange={handleInputChange}
                                        label="Active"
                                        nomargin
                                    />
                                </div>
                            </Card>

                            {/* Actions */}
                            <Card>
                                <div className="space-y-3">
                                    <Button type="submit" block disabled={loading}>
                                        {loading ? 'Saving...' : isEdit ? 'Update Category' : 'Create Category'}
                                    </Button>
                                    <Link href={`/admin/categories${tokenQuery}`}>
                                        <Button variant="outline" block>Cancel</Button>
                                    </Link>
                                </div>
                            </Card>
                        </div>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}

