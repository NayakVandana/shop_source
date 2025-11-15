// @ts-nocheck
import React, { useEffect, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import axios from 'axios';
import AdminLayout from '../../../Layouts/AdminLayout';
import Card from '../../../Components/ui/Card';
import Button from '../../../Components/ui/Button';
import { Heading, Text } from '../../../Components/ui/Typography';

export default function AdminProducts() {
    const [products, setProducts] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [search, setSearch] = useState('');
    const [filterCategory, setFilterCategory] = useState('');
    const [filterStatus, setFilterStatus] = useState('');
    const [categories, setCategories] = useState([]);
    const [pagination, setPagination] = useState({ current_page: 1, last_page: 1, total: 0, per_page: 10 });
    const [counts, setCounts] = useState({ total: 0, active: 0, inactive: 0, in_stock: 0, out_of_stock: 0 });

    useEffect(() => {
        loadCategories();
        loadProducts();
    }, []);

    const loadCategories = async () => {
        try {
            const urlParams = new URLSearchParams(window.location.search);
            const token = urlParams.get('token') || localStorage.getItem('admin_token') || '';
            
            const res = await axios.post('/api/admin/categories/list', {}, {
                headers: { AdminToken: token }
            });
            if (res.data && res.data.status) {
                setCategories(res.data.data?.data || res.data.data || []);
            }
        } catch (err) {
            console.error('Failed to load categories:', err);
        }
    };

    const loadProducts = async (page = 1) => {
        setLoading(true);
        setError(null);
        try {
            const urlParams = new URLSearchParams(window.location.search);
            const token = urlParams.get('token') || localStorage.getItem('admin_token') || '';
            
            console.log('Loading products with token:', token ? 'Token exists' : 'No token');
            
            const params: any = {
                per_page: 10,
                page,
            };
            if (search) params.search = search;
            if (filterCategory) params.category_id = filterCategory;
            if (filterStatus !== '') params.is_active = filterStatus === 'active';

            const res = await axios.post('/api/admin/products/index', params, {
                headers: { 
                    'AdminToken': token,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });
            
            if (res.data && res.data.status) {
                const data = res.data.data;
                setProducts(Array.isArray(data?.data) ? data.data : []);
                setPagination({
                    current_page: data?.current_page || 1,
                    last_page: data?.last_page || 1,
                    total: data?.total || 0,
                    per_page: data?.per_page || 10,
                });
                // Set counts from backend
                if (data?.counts) {
                    setCounts({
                        total: data.counts.total || 0,
                        active: data.counts.active || 0,
                        inactive: data.counts.inactive || 0,
                        in_stock: data.counts.in_stock || 0,
                        out_of_stock: data.counts.out_of_stock || 0,
                    });
                }
            } else {
                setProducts([]);
                if (res.data && res.data.message) {
                    setError(res.data.message);
                }
            }
        } catch (err) {
            setError('Failed to load products');
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    const handleSearch = () => {
        loadProducts(1);
    };

    const handleDelete = async (uuid: string) => {
        if (!confirm('Are you sure you want to delete this product?')) return;
        
        try {
            const urlParams = new URLSearchParams(window.location.search);
            const token = urlParams.get('token') || localStorage.getItem('admin_token') || '';
            
            await axios.post('/api/admin/products/destroy', { id: uuid }, {
                headers: { AdminToken: token }
            });
            loadProducts(pagination.current_page);
        } catch (err) {
            alert('Failed to delete product');
        }
    };

    const getImageUrl = (product) => {
        // Check new media system first
        if (product.media && product.media.length > 0) {
            const imageMedia = product.media.find(m => m.type === 'image') || product.media[0];
            if (imageMedia) {
                const url = imageMedia.url || (imageMedia.file_path?.startsWith('http') 
                    ? imageMedia.file_path 
                    : `/storage/${imageMedia.file_path}`);
                return url || '/images/placeholder.svg';
            }
        }
        // Fallback to legacy images array
        if (product.images && product.images.length > 0) {
            const img = product.images[0];
            return img.startsWith('http') ? img : `/storage/${img}`;
        }
        return '/images/placeholder.svg';
    };

    const tokenParam = typeof window !== 'undefined' 
        ? (new URLSearchParams(window.location.search).get('token') || localStorage.getItem('admin_token') || '')
        : '';
    const tokenQuery = tokenParam ? `?token=${tokenParam}` : '';

    return (
        <AdminLayout>
            <Head title="Admin - Products" />
            <div className="p-4 sm:p-6 lg:p-8">
                <div className="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <Heading level={1}>Products Management</Heading>
                        {!loading && (
                            <div className="mt-2 flex flex-wrap items-center gap-4 text-sm text-gray-600">
                                <Text className="text-sm text-gray-500">
                                    <span className="font-medium text-gray-900">{counts.total || 0}</span> total
                                </Text>
                                <Text className="text-sm text-gray-500">
                                    <span className="font-medium text-green-600">{counts.active || 0}</span> active, <span className="font-medium text-gray-600">{counts.inactive || 0}</span> inactive
                                </Text>
                                <Text className="text-sm text-gray-500">
                                    <span className="font-medium text-blue-600">{counts.in_stock || 0}</span> in stock, <span className="font-medium text-red-600">{counts.out_of_stock || 0}</span> out of stock
                                </Text>
                            </div>
                        )}
                    </div>
                    <Link href={`/admin/products/create${tokenQuery}`}>
                        <Button>Add New Product</Button>
                    </Link>
                </div>

                {/* Filters */}
                <Card className="mb-6">
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Search</label>
                            <input
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Name, SKU..."
                                className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Category</label>
                            <select
                                value={filterCategory}
                                onChange={(e) => setFilterCategory(e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                            >
                                <option value="">All Categories</option>
                                {categories.map((cat) => (
                                    <option key={cat.uuid} value={cat.id}>{cat.name}</option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select
                                value={filterStatus}
                                onChange={(e) => setFilterStatus(e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                            >
                                <option value="">All</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div className="flex items-end">
                            <Button onClick={handleSearch} block>Apply Filters</Button>
                        </div>
                    </div>
                </Card>

                {/* Products Table */}
                {loading ? (
                    <Card>
                        <div className="text-center py-12">
                            <Text>Loading products...</Text>
                        </div>
                    </Card>
                ) : error ? (
                    <Card>
                        <div className="text-center py-12 text-red-600">
                            <Text>{error}</Text>
                        </div>
                    </Card>
                ) : products.length === 0 ? (
                    <Card>
                        <div className="text-center py-12">
                            <Text muted>No products found</Text>
                            {pagination.total === 0 && (
                                <Text className="text-sm text-gray-500 mt-2">Total: 0 products</Text>
                            )}
                        </div>
                    </Card>
                ) : (
                    <>
                        {/* Statistics Cards */}
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                            <Card>
                                <div className="p-4">
                                    <Text className="text-sm text-gray-500 mb-1">Total Products</Text>
                                    <Heading level={2} className="text-2xl font-bold text-gray-900">{counts.total || 0}</Heading>
                                    <Text className="text-xs text-gray-500 mt-1">
                                        {counts.total === 1 ? 'product' : 'products'} in total
                                    </Text>
                                </div>
                            </Card>
                            <Card className={counts.active > 0 ? 'border-l-4 border-green-500' : ''}>
                                <div className="p-4">
                                    <Text className="text-sm text-gray-500 mb-1">Active Products</Text>
                                    <Heading level={2} className="text-2xl font-bold text-green-600">{counts.active || 0}</Heading>
                                    <Text className="text-xs text-gray-500 mt-1">
                                        {counts.inactive || 0} inactive
                                    </Text>
                                </div>
                            </Card>
                            <Card className={counts.in_stock > 0 ? 'border-l-4 border-blue-500' : ''}>
                                <div className="p-4">
                                    <Text className="text-sm text-gray-500 mb-1">In Stock</Text>
                                    <Heading level={2} className="text-2xl font-bold text-blue-600">{counts.in_stock || 0}</Heading>
                                    <Text className="text-xs text-gray-500 mt-1">
                                        Products available
                                    </Text>
                                </div>
                            </Card>
                            <Card className={counts.out_of_stock > 0 ? 'border-l-4 border-red-500' : ''}>
                                <div className="p-4">
                                    <Text className="text-sm text-gray-500 mb-1">Out of Stock</Text>
                                    <Heading level={2} className="text-2xl font-bold text-red-600">{counts.out_of_stock || 0}</Heading>
                                    <Text className="text-xs text-red-600 mt-1">
                                        {counts.out_of_stock > 0 ? 'Needs restocking' : 'All in stock'}
                                    </Text>
                                </div>
                            </Card>
                        </div>
                        <Card padding="none" className="overflow-hidden">
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {products.map((product) => (
                                            <tr key={product.uuid} className="hover:bg-gray-50">
                                                <td className="px-4 py-3 whitespace-nowrap">
                                                    <img
                                                        src={getImageUrl(product)}
                                                        alt={product.name}
                                                        className="w-12 h-12 sm:w-16 sm:h-16 object-cover rounded"
                                                        onError={(e) => {
                                                            e.target.src = '/images/placeholder.svg';
                                                        }}
                                                    />
                                                </td>
                                                <td className="px-4 py-3">
                                                    <div className="text-sm font-medium text-gray-900">{product.name}</div>
                                                    <div className="text-xs text-gray-500">SKU: {product.sku || 'N/A'}</div>
                                                </td>
                                                <td className="px-4 py-3 text-sm text-gray-500">
                                                    {product.category?.name || 'N/A'}
                                                </td>
                                                <td className="px-4 py-3 text-sm text-gray-900">
                                                    <div className="flex flex-col">
                                                        {product.discount_info ? (
                                                            <>
                                                                <span className="text-xs text-gray-400 line-through">${product.discount_info.original_price}</span>
                                                                <span className="text-red-600 font-semibold">${product.discount_info.final_price}</span>
                                                                <span className="text-xs text-green-600 font-medium">{product.discount_info.display_text}</span>
                                                            </>
                                                        ) : (
                                                            <>
                                                                <span>${product.price}</span>
                                                                {product.sale_price && (
                                                                    <span className="text-xs text-red-600">Sale: ${product.sale_price}</span>
                                                                )}
                                                            </>
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="px-4 py-3 text-sm text-gray-500">
                                                    {product.stock_quantity || 0}
                                                </td>
                                                <td className="px-4 py-3 whitespace-nowrap">
                                                    <span className={`px-2 py-1 text-xs rounded-full ${
                                                        product.is_active 
                                                            ? 'bg-green-100 text-green-800' 
                                                            : 'bg-red-100 text-red-800'
                                                    }`}>
                                                        {product.is_active ? 'Active' : 'Inactive'}
                                                    </span>
                                                    {product.is_featured && (
                                                        <div className="text-xs text-indigo-600 mt-1">Featured</div>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                                                    <div className="flex justify-end gap-2">
                                                        <Link href={`/admin/products/edit${tokenQuery ? tokenQuery + '&' : '?'}id=${product.uuid}`}>
                                                            <Button variant="outline" size="sm">Edit</Button>
                                                        </Link>
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => handleDelete(product.uuid)}
                                                            className="text-red-600 hover:bg-red-50"
                                                        >
                                                            Delete
                                                        </Button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </Card>

                        {/* Pagination */}
                        {pagination.last_page > 1 && (
                            <div className="mt-6 flex justify-center gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => loadProducts(pagination.current_page - 1)}
                                    disabled={pagination.current_page === 1}
                                >
                                    Previous
                                </Button>
                                <Text className="self-center px-4">
                                    Page {pagination.current_page} of {pagination.last_page}
                                </Text>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => loadProducts(pagination.current_page + 1)}
                                    disabled={pagination.current_page === pagination.last_page}
                                >
                                    Next
                                </Button>
                            </div>
                        )}
                    </>
                )}
            </div>
        </AdminLayout>
    );
}

