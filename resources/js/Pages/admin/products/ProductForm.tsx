// @ts-nocheck
import React, { useEffect, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import axios from 'axios';
import AdminLayout from '../../../Layouts/AdminLayout';
import Card from '../../../Components/ui/Card';
import Button from '../../../Components/ui/Button';
import { Heading, Text } from '../../../Components/ui/Typography';

export default function ProductForm() {
    const [loading, setLoading] = useState(false);
    const [categories, setCategories] = useState([]);
    const [errors, setErrors] = useState({});
    
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
            if (res.data && res.data.success) {
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
            if (res.data && res.data.success) {
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
                
                if (product.images && product.images.length > 0) {
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
        if (errors[name]) {
            setErrors(prev => {
                const newErrors = { ...prev };
                delete newErrors[name];
                return newErrors;
            });
        }
    };

    const handleImageChange = (e) => {
        const file = e.target.files?.[0];
        if (file) {
            if (!file.type.startsWith('image/')) {
                alert('Please select an image file');
                return;
            }
            setImagePreview(URL.createObjectURL(file));
            setFormData(prev => ({ ...prev, image: file }));
        }
    };

    const handleImagesChange = (e) => {
        const files = Array.from(e.target.files || []);
        const imageFiles = files.filter(f => f.type.startsWith('image/'));
        if (imageFiles.length !== files.length) {
            alert('Please select only image files');
        }
        const previews = imageFiles.map(f => URL.createObjectURL(f));
        setImagesPreview(previews);
        setFormData(prev => ({ ...prev, images: imageFiles }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setErrors({});

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

            if (res.data && res.data.success) {
                const tokenQuery = token ? `?token=${token}` : '';
                router.visit(`/admin/products${tokenQuery}`);
            } else {
                setErrors(res.data?.data?.errors || {});
                alert(res.data?.message || 'Operation failed');
            }
        } catch (err) {
            if (err.response?.data?.data?.errors) {
                setErrors(err.response.data.data.errors);
            } else {
                alert('Failed to save product');
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
                    <Link href={`/admin/products${tokenQuery}`} className="text-indigo-600 hover:text-indigo-700 mb-4 inline-block">
                        ← Back to Products
                    </Link>
                    <Heading level={1}>{isEdit ? 'Edit Product' : 'Create New Product'}</Heading>
                </div>

                <form onSubmit={handleSubmit}>
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Main Form */}
                        <div className="lg:col-span-2 space-y-6">
                            {/* Basic Information */}
                            <Card>
                                <Heading level={2} className="mb-4">Basic Information</Heading>
                                
                                <div className="space-y-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Product Name <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            name="name"
                                            value={formData.name}
                                            onChange={handleInputChange}
                                            required
                                            className={`w-full px-3 py-2 border rounded-md text-sm ${
                                                errors.name ? 'border-red-500' : 'border-gray-300'
                                            }`}
                                        />
                                        {errors.name && <Text className="text-red-500 text-xs mt-1">{errors.name[0]}</Text>}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Short Description
                                        </label>
                                        <textarea
                                            name="short_description"
                                            value={formData.short_description}
                                            onChange={handleInputChange}
                                            rows={2}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Description
                                        </label>
                                        <textarea
                                            name="description"
                                            value={formData.description}
                                            onChange={handleInputChange}
                                            rows={5}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                                        />
                                    </div>
                                </div>
                            </Card>

                            {/* Pricing & Inventory */}
                            <Card>
                                <Heading level={2} className="mb-4">Pricing & Inventory</Heading>
                                
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Price <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="number"
                                            name="price"
                                            value={formData.price}
                                            onChange={handleInputChange}
                                            step="0.01"
                                            min="0"
                                            required
                                            className={`w-full px-3 py-2 border rounded-md text-sm ${
                                                errors.price ? 'border-red-500' : 'border-gray-300'
                                            }`}
                                        />
                                        {errors.price && <Text className="text-red-500 text-xs mt-1">{errors.price[0]}</Text>}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Sale Price
                                        </label>
                                        <input
                                            type="number"
                                            name="sale_price"
                                            value={formData.sale_price}
                                            onChange={handleInputChange}
                                            step="0.01"
                                            min="0"
                                            className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Stock Quantity <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="number"
                                            name="stock_quantity"
                                            value={formData.stock_quantity}
                                            onChange={handleInputChange}
                                            min="0"
                                            required
                                            className={`w-full px-3 py-2 border rounded-md text-sm ${
                                                errors.stock_quantity ? 'border-red-500' : 'border-gray-300'
                                            }`}
                                        />
                                        {errors.stock_quantity && <Text className="text-red-500 text-xs mt-1">{errors.stock_quantity[0]}</Text>}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Weight (kg)
                                        </label>
                                        <input
                                            type="number"
                                            name="weight"
                                            value={formData.weight}
                                            onChange={handleInputChange}
                                            step="0.01"
                                            min="0"
                                            className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                                        />
                                    </div>
                                </div>
                            </Card>
                        </div>

                        {/* Sidebar */}
                        <div className="space-y-6">
                            {/* Category */}
                            <Card>
                                <Heading level={2} className="mb-4">Category</Heading>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Category <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        name="category_id"
                                        value={formData.category_id}
                                        onChange={handleInputChange}
                                        required
                                        className={`w-full px-3 py-2 border rounded-md text-sm ${
                                            errors.category_id ? 'border-red-500' : 'border-gray-300'
                                        }`}
                                    >
                                        <option value="">Select Category</option>
                                        {categories.map((cat) => (
                                            <option key={cat.uuid} value={cat.id}>{cat.name}</option>
                                        ))}
                                    </select>
                                    {errors.category_id && <Text className="text-red-500 text-xs mt-1">{errors.category_id[0]}</Text>}
                                </div>
                            </Card>

                            {/* Images */}
                            <Card>
                                <Heading level={2} className="mb-4">Images</Heading>
                                <div className="space-y-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Main Image
                                        </label>
                                        <input
                                            type="file"
                                            accept="image/*"
                                            onChange={handleImageChange}
                                            className="w-full text-sm"
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
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Additional Images
                                        </label>
                                        <input
                                            type="file"
                                            accept="image/*"
                                            multiple
                                            onChange={handleImagesChange}
                                            className="w-full text-sm"
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

                            {/* Status */}
                            <Card>
                                <Heading level={2} className="mb-4">Status</Heading>
                                <div className="space-y-3">
                                    <label className="flex items-center">
                                        <input
                                            type="checkbox"
                                            name="is_active"
                                            checked={formData.is_active}
                                            onChange={handleInputChange}
                                            className="mr-2"
                                        />
                                        <Text>Active</Text>
                                    </label>
                                    <label className="flex items-center">
                                        <input
                                            type="checkbox"
                                            name="is_featured"
                                            checked={formData.is_featured}
                                            onChange={handleInputChange}
                                            className="mr-2"
                                        />
                                        <Text>Featured</Text>
                                    </label>
                                    <label className="flex items-center">
                                        <input
                                            type="checkbox"
                                            name="in_stock"
                                            checked={formData.in_stock}
                                            onChange={handleInputChange}
                                            className="mr-2"
                                        />
                                        <Text>In Stock</Text>
                                    </label>
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

