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

export default function ProductForm() {
    const [loading, setLoading] = useState(false);
    const [categories, setCategories] = useState([]);
    const [errors, setErrors] = useState({});
    const [generalError, setGeneralError] = useState('');
    
    const [formData, setFormData] = useState({
        name: '',
        description: '',
        short_description: '',
        price: '',
        sale_price: '',
        category_id: '',
        stock_quantity: '0',
        manage_stock: true,
        in_stock: true,
        is_featured: false,
        is_active: true,
        weight: '',
        dimensions: '',
    });
    
    const [imagePreview, setImagePreview] = useState(null);
    const [imagesPreview, setImagesPreview] = useState([]);
    const [videoPreview, setVideoPreview] = useState(null);
    const [videosPreview, setVideosPreview] = useState([]);
    
    const isEdit = typeof window !== 'undefined' && new URLSearchParams(window.location.search).has('id');
    const productId = typeof window !== 'undefined' 
        ? new URLSearchParams(window.location.search).get('id') 
        : null;

    useEffect(() => {
        loadCategories();
        if (isEdit && productId) {
            loadProduct(productId);
        }
    }, []);

    const loadCategories = async () => {
        try {
            const token = getToken();
            const res = await axios.post('/api/admin/categories/list', { per_page: 100 }, {
                headers: { AdminToken: token }
            });
            if (res.data && res.data.status) {
                setCategories(res.data.data?.data || res.data.data || []);
            }
        } catch (err) {
            console.error('Failed to load categories:', err);
        }
    };

    const loadProduct = async (id: string) => {
        setLoading(true);
        try {
            const token = getToken();
            const res = await axios.post('/api/admin/products/show', { id }, {
                headers: { AdminToken: token }
            });
            if (res.data && res.data.status) {
                const product = res.data.data;
                setFormData({
                    name: product.name || '',
                    description: product.description || '',
                    short_description: product.short_description || '',
                    price: product.price || '',
                    sale_price: product.sale_price || '',
                    category_id: product.category_id || '',
                    stock_quantity: product.stock_quantity || '0',
                    manage_stock: product.manage_stock !== undefined ? product.manage_stock : true,
                    in_stock: product.in_stock !== undefined ? product.in_stock : true,
                    is_featured: product.is_featured || false,
                    is_active: product.is_active !== undefined ? product.is_active : true,
                    weight: product.weight || '',
                    dimensions: product.dimensions || '',
                });
                
                // Load existing images
                if (product.media) {
                    const images = product.media.filter(m => m.type === 'image');
                    const videos = product.media.filter(m => m.type === 'video');
                    
                    if (images.length > 0) {
                        const imagePreviews = images.map(img => img.url || (img.file_path.startsWith('http') ? img.file_path : `/storage/${img.file_path}`));
                        setImagesPreview(imagePreviews);
                        setImagePreview(imagePreviews[0]);
                    }
                    
                    if (videos.length > 0) {
                        const videoPreviews = videos.map(vid => vid.url || (vid.file_path.startsWith('http') ? vid.file_path : `/storage/${vid.file_path}`));
                        setVideosPreview(videoPreviews);
                        setVideoPreview(videoPreviews[0]);
                    }
                } else if (product.images && product.images.length > 0) {
                    // Fallback for legacy format
                    const previews = product.images.map(img => 
                        img.startsWith('http') ? img : `/storage/${img}`
                    );
                    setImagesPreview(previews);
                    setImagePreview(previews[0]);
                }
            }
        } catch (err) {
            alert('Failed to load product');
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

    const handleImagesChange = (e) => {
        const files = Array.from(e.target.files || []);
        const imageFiles = files.filter(f => f.type.startsWith('image/'));
        
        // Validate all files are images
        if (imageFiles.length !== files.length) {
            setErrors(prev => ({ ...prev, images: 'Please select only valid image files' }));
            return;
        }
        
        // Validate file sizes (5MB max per file)
        const oversizedFiles = imageFiles.filter(f => f.size > 5 * 1024 * 1024);
        if (oversizedFiles.length > 0) {
            setErrors(prev => ({ ...prev, images: 'All images must be less than 5MB each' }));
            return;
        }
        
        // Clear error if validation passes
        if (errors.images) {
            setErrors(prev => {
                const newErrors = { ...prev };
                delete newErrors.images;
                return newErrors;
            });
        }
        
        const previews = imageFiles.map(f => URL.createObjectURL(f));
        setImagesPreview(previews);
        setFormData(prev => ({ ...prev, images: imageFiles }));
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

    const handleVideosChange = (e) => {
        const files = Array.from(e.target.files || []);
        const validVideoTypes = ['video/mp4', 'video/avi', 'video/quicktime', 'video/x-msvideo', 'video/x-ms-wmv', 'video/x-flv', 'video/webm'];
        const videoFiles = files.filter(f => 
            validVideoTypes.includes(f.type) || f.name.match(/\.(mp4|avi|mov|wmv|flv|webm)$/i)
        );
        
        // Validate all files are videos
        if (videoFiles.length !== files.length) {
            setErrors(prev => ({ ...prev, videos: 'Please select only valid video files (MP4, AVI, MOV, WMV, FLV, or WEBM)' }));
            return;
        }
        
        // Validate file sizes (10MB max per file)
        const oversizedFiles = videoFiles.filter(f => f.size > 10 * 1024 * 1024);
        if (oversizedFiles.length > 0) {
            setErrors(prev => ({ ...prev, videos: 'All videos must be less than 10MB each' }));
            return;
        }
        
        // Clear error if validation passes
        if (errors.videos) {
            setErrors(prev => {
                const newErrors = { ...prev };
                delete newErrors.videos;
                return newErrors;
            });
        }
        
        const previews = videoFiles.map(f => URL.createObjectURL(f));
        setVideosPreview(previews);
        setFormData(prev => ({ ...prev, videos: videoFiles }));
    };

    const validateForm = () => {
        const validationErrors = {};
        
        // Required field validations
        if (!formData.name || (typeof formData.name === 'string' && formData.name.trim() === '')) {
            validationErrors.name = 'Product name is required.';
        }
        
        if (!formData.price || formData.price === '' || formData.price === null || formData.price === undefined) {
            validationErrors.price = 'Price is required.';
        } else {
            const price = parseFloat(formData.price);
            if (isNaN(price) || price <= 0) {
                validationErrors.price = 'Price must be a valid number greater than 0.';
            }
        }
        
        if (!formData.category_id || formData.category_id === '' || formData.category_id === null || formData.category_id === undefined) {
            validationErrors.category_id = 'Category is required.';
        }
        
        if (formData.stock_quantity === '' || formData.stock_quantity === null || formData.stock_quantity === undefined) {
            validationErrors.stock_quantity = 'Stock quantity is required.';
        } else {
            const stockQty = parseInt(formData.stock_quantity);
            if (isNaN(stockQty) || stockQty < 0) {
                validationErrors.stock_quantity = 'Stock quantity must be a valid number (0 or greater).';
            }
        }
        
        // Validate sale_price if provided (must be less than price)
        if (formData.sale_price && formData.sale_price !== '' && formData.sale_price !== null && formData.sale_price !== undefined) {
            const salePrice = parseFloat(formData.sale_price);
            const price = parseFloat(formData.price);
            if (isNaN(salePrice) || salePrice < 0) {
                validationErrors.sale_price = 'Sale price must be a valid number greater than or equal to 0.';
            } else if (price && !isNaN(price) && salePrice >= price) {
                validationErrors.sale_price = 'Sale price must be less than the regular price.';
            }
        }
        
        // Validate weight if provided
        if (formData.weight && formData.weight !== '' && formData.weight !== null && formData.weight !== undefined) {
            const weight = parseFloat(formData.weight);
            if (isNaN(weight) || weight < 0) {
                validationErrors.weight = 'Weight must be a valid number greater than or equal to 0.';
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
            if (formData.images && formData.images.length > 0) {
                formData.images.forEach(img => formDataToSend.append('images[]', img));
            }
            if (formData.video) {
                formDataToSend.append('video', formData.video);
            }
            if (formData.videos && formData.videos.length > 0) {
                formData.videos.forEach(vid => formDataToSend.append('videos[]', vid));
            }

            const url = isEdit 
                ? '/api/admin/products/update' 
                : '/api/admin/products/store';
            
            if (isEdit) {
                formDataToSend.append('id', productId);
            }

            const res = await axios.post(url, formDataToSend, {
                headers: {
                    AdminToken: token,
                    'Content-Type': 'multipart/form-data'
                }
            });

            if (res.data && res.data.status) {
                const tokenQuery = token ? `?token=${token}` : '';
                router.visit(`/admin/products${tokenQuery}`);
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
                setGeneralError(err.message || 'Failed to save product. Please try again.');
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
                    <Text>Loading product...</Text>
                </div>
            </AdminLayout>
        );
    }

    return (
        <AdminLayout>
            <Head title={isEdit ? 'Edit Product' : 'Create Product'} />
            <div className="p-4 sm:p-6 lg:p-8">
                <div className="mb-6">
                    <Link href={`/admin/products${tokenQuery}`} className="text-primary-600 hover:text-primary-700 mb-4 inline-block">
                        ← Back to Products
                    </Link>
                    <Heading level={1}>{isEdit ? 'Edit Product' : 'Create New Product'}</Heading>
                </div>

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
                                        title="Product Name *"
                                        error={errors.name}
                                    />

                                    <FormTextarea
                                        name="short_description"
                                        value={formData.short_description}
                                        onChange={handleInputChange}
                                        rows={2}
                                        title="Short Description"
                                        error={errors.short_description}
                                    />

                                    <FormTextarea
                                        name="description"
                                        value={formData.description}
                                        onChange={handleInputChange}
                                        rows={5}
                                        title="Description"
                                        error={errors.description}
                                    />
                                </div>
                            </Card>

                            {/* Pricing & Inventory */}
                            <Card>
                                <Heading level={2} className="mb-4">Pricing & Inventory</Heading>
                                
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <FormInput
                                        type="number"
                                        name="price"
                                        value={formData.price}
                                        onChange={handleInputChange}
                                        step="0.01"
                                        min="0"
                                        title="Price *"
                                        error={errors.price}
                                    />

                                    <FormInput
                                        type="number"
                                        name="sale_price"
                                        value={formData.sale_price}
                                        onChange={handleInputChange}
                                        step="0.01"
                                        min="0"
                                        title="Sale Price"
                                        error={errors.sale_price}
                                    />

                                    <FormInput
                                        type="number"
                                        name="stock_quantity"
                                        value={formData.stock_quantity}
                                        onChange={handleInputChange}
                                        min="0"
                                        title="Stock Quantity *"
                                        error={errors.stock_quantity}
                                    />

                                    <FormInput
                                        type="number"
                                        name="weight"
                                        value={formData.weight}
                                        onChange={handleInputChange}
                                        step="0.01"
                                        min="0"
                                        title="Weight (kg)"
                                        error={errors.weight}
                                    />
                                </div>
                            </Card>
                        </div>

                        {/* Sidebar */}
                        <div className="space-y-6">
                            {/* Category */}
                            <Card>
                                <Heading level={2} className="mb-4">Category</Heading>
                                <FormSelect
                                    name="category_id"
                                    value={formData.category_id}
                                    onChange={handleInputChange}
                                    title="Category *"
                                    error={errors.category_id}
                                >
                                    <option value="">Select Category</option>
                                    {categories.map((cat) => (
                                        <option key={cat.uuid} value={cat.id}>{cat.name}</option>
                                    ))}
                                </FormSelect>
                            </Card>

                            {/* Images */}
                            <Card>
                                <Heading level={2} className="mb-4">Images</Heading>
                                <div className="space-y-4">
                                    <div>
                                        <FormInput
                                            type="file"
                                            accept="image/*"
                                            onChange={handleImageChange}
                                            title="Main Image"
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

                                    <div>
                                        <FormInput
                                            type="file"
                                            accept="image/*"
                                            multiple
                                            onChange={handleImagesChange}
                                            title="Additional Images"
                                            error={errors.images}
                                        />
                                        {imagesPreview.length > 0 && (
                                            <div className="mt-2 grid grid-cols-2 gap-2">
                                                {imagesPreview.map((preview, idx) => (
                                                    <img
                                                        key={idx}
                                                        src={preview}
                                                        alt={`Preview ${idx + 1}`}
                                                        className="w-full h-24 object-cover rounded"
                                                    />
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </Card>

                            {/* Videos */}
                            <Card>
                                <Heading level={2} className="mb-4">Videos</Heading>
                                <div className="space-y-4">
                                    <div>
                                        <FormInput
                                            type="file"
                                            accept="video/mp4,video/avi,video/quicktime,video/x-msvideo,video/x-ms-wmv,video/x-flv,video/webm"
                                            onChange={handleVideoChange}
                                            title="Main Video"
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

                                    <div>
                                        <FormInput
                                            type="file"
                                            accept="video/mp4,video/avi,video/quicktime,video/x-msvideo,video/x-ms-wmv,video/x-flv,video/webm"
                                            multiple
                                            onChange={handleVideosChange}
                                            title="Additional Videos"
                                            error={errors.videos}
                                        />
                                        {videosPreview.length > 0 && (
                                            <div className="mt-2 grid grid-cols-1 gap-2">
                                                {videosPreview.map((preview, idx) => (
                                                    <video
                                                        key={idx}
                                                        src={preview}
                                                        controls
                                                        className="w-full h-24 object-cover rounded"
                                                    >
                                                        Your browser does not support the video tag.
                                                    </video>
                                                ))}
                                            </div>
                                        )}
                                    </div>
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
                                    <FormCheckbox
                                        name="is_featured"
                                        checked={formData.is_featured}
                                        onChange={handleInputChange}
                                        label="Featured"
                                        nomargin
                                    />
                                    <FormCheckbox
                                        name="in_stock"
                                        checked={formData.in_stock}
                                        onChange={handleInputChange}
                                        label="In Stock"
                                        nomargin
                                    />
                                </div>
                            </Card>

                            {/* Actions */}
                            <Card>
                                <div className="space-y-3">
                                    <Button type="submit" block disabled={loading}>
                                        {loading ? 'Saving...' : isEdit ? 'Update Product' : 'Create Product'}
                                    </Button>
                                    <Link href={`/admin/products${tokenQuery}`}>
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

