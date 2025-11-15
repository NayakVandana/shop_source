// @ts-nocheck
import React, { useEffect, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import axios from 'axios';
import AdminLayout from '../../../Layouts/AdminLayout';
import Card from '../../../Components/ui/Card';
import Button from '../../../Components/ui/Button';
import { Heading, Text } from '../../../Components/ui/Typography';

export default function AdminCategories() {
    const [categories, setCategories] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [search, setSearch] = useState('');
    const [filterStatus, setFilterStatus] = useState('');
    const [pagination, setPagination] = useState({ current_page: 1, last_page: 1, total: 0, per_page: 10 });
    const [counts, setCounts] = useState({ total: 0, active: 0, inactive: 0 });

    useEffect(() => {
        loadCategories();
    }, []);

    const loadCategories = async (page = 1) => {
        setLoading(true);
        setError(null);
        try {
            // Get token from localStorage/cookies only (not URL)
            const token = localStorage.getItem('admin_token') || '';
            
            const params: any = {
                per_page: 10,
                page,
            };
            if (search) params.search = search;
            if (filterStatus !== '') params.is_active = filterStatus === 'active';

            const res = await axios.post('/api/admin/categories/index', params, {
                headers: { AdminToken: token }
            });
            
            if (res.data && res.data.status) {
                const data = res.data.data;
                setCategories(Array.isArray(data?.data) ? data.data : []);
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
                    });
                }
            } else {
                setCategories([]);
                if (res.data && res.data.message) {
                    setError(res.data.message);
                }
            }
        } catch (err) {
            setError('Failed to load categories');
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    const handleSearch = () => {
        loadCategories(1);
    };

    const handleDelete = async (uuid: string) => {
        if (!confirm('Are you sure you want to delete this category? This action cannot be undone if the category has products.')) return;
        
        try {
            // Get token from localStorage/cookies only (not URL)
            const token = localStorage.getItem('admin_token') || '';
            
            const res = await axios.post('/api/admin/categories/destroy', { id: uuid }, {
                headers: { AdminToken: token }
            });
            
            if (res.data && res.data.status) {
                loadCategories(pagination.current_page);
            } else {
                alert(res.data?.message || 'Failed to delete category');
            }
        } catch (err) {
            const errorMsg = err.response?.data?.message || 'Failed to delete category';
            alert(errorMsg);
        }
    };

    const getImageUrl = (category) => {
        if (category.image) {
            return category.image.startsWith('http') ? category.image : `/storage/${category.image}`;
        }
        return '/images/placeholder.svg';
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

    return (
        <AdminLayout>
            <Head title="Admin - Categories" />
            <div className="p-4 sm:p-6 lg:p-8">
                <div className="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <Heading level={1}>Categories Management</Heading>
                    <Link href={`/admin/categories/create`}>
                        <Button>Add New Category</Button>
                    </Link>
                </div>

                {/* Filters */}
                <Card className="mb-6">
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Search</label>
                            <input
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Name, description..."
                                className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                                onKeyPress={(e) => e.key === 'Enter' && handleSearch()}
                            />
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

                {/* Categories Table */}
                {loading ? (
                    <Card>
                        <div className="text-center py-12">
                            <Text>Loading categories...</Text>
                        </div>
                    </Card>
                ) : error ? (
                    <Card>
                        <div className="text-center py-12 text-red-600">
                            <Text>{error}</Text>
                        </div>
                    </Card>
                ) : categories.length === 0 ? (
                    <Card>
                        <div className="text-center py-12">
                            <Text muted>No categories found</Text>
                        </div>
                    </Card>
                ) : (
                    <>
                        {/* Statistics Cards */}
                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                            <Card>
                                <div className="p-4">
                                    <Text className="text-sm text-gray-500 mb-1">Total Categories</Text>
                                    <Heading level={2} className="text-2xl font-bold text-gray-900">{counts.total || 0}</Heading>
                                    <Text className="text-xs text-gray-500 mt-1">
                                        {counts.total === 1 ? 'category' : 'categories'} in total
                                    </Text>
                                </div>
                            </Card>
                            <Card className={counts.active > 0 ? 'border-l-4 border-green-500' : ''}>
                                <div className="p-4">
                                    <Text className="text-sm text-gray-500 mb-1">Active Categories</Text>
                                    <Heading level={2} className="text-2xl font-bold text-green-600">{counts.active || 0}</Heading>
                                    <Text className="text-xs text-gray-500 mt-1">
                                        {counts.inactive || 0} inactive
                                    </Text>
                                </div>
                            </Card>
                            <Card className={counts.inactive > 0 ? 'border-l-4 border-red-500' : ''}>
                                <div className="p-4">
                                    <Text className="text-sm text-gray-500 mb-1">Inactive Categories</Text>
                                    <Heading level={2} className="text-2xl font-bold text-red-600">{counts.inactive || 0}</Heading>
                                    <Text className="text-xs text-gray-500 mt-1">
                                        Categories disabled
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
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slug</th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Products</th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sort Order</th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {categories.map((category) => (
                                            <tr key={category.uuid} className="hover:bg-gray-50">
                                                <td className="px-4 py-3 whitespace-nowrap">
                                                    <img
                                                        src={getImageUrl(category)}
                                                        alt={category.name}
                                                        className="w-12 h-12 sm:w-16 sm:h-16 object-cover rounded"
                                                        onError={(e) => {
                                                            e.target.src = '/images/placeholder.svg';
                                                        }}
                                                    />
                                                </td>
                                                <td className="px-4 py-3">
                                                    <div className="text-sm font-medium text-gray-900">{category.name}</div>
                                                    {category.description && (
                                                        <div className="text-xs text-gray-500 mt-1 line-clamp-2">{category.description}</div>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 text-sm text-gray-500">
                                                    {category.slug}
                                                </td>
                                                <td className="px-4 py-3 text-sm text-gray-500">
                                                    {category.products_count || 0}
                                                </td>
                                                <td className="px-4 py-3 text-sm text-gray-500">
                                                    {category.sort_order || 0}
                                                </td>
                                                <td className="px-4 py-3 whitespace-nowrap">
                                                    <span className={`px-2 py-1 text-xs rounded-full ${
                                                        category.is_active 
                                                            ? 'bg-green-100 text-green-800' 
                                                            : 'bg-red-100 text-red-800'
                                                    }`}>
                                                        {category.is_active ? 'Active' : 'Inactive'}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                                                    <div className="flex justify-end gap-2">
                                                        <Link href={`/admin/categories/edit?id=${category.uuid}`}>
                                                            <Button variant="outline" size="sm">Edit</Button>
                                                        </Link>
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => handleDelete(category.uuid)}
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
                                    onClick={() => loadCategories(pagination.current_page - 1)}
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
                                    onClick={() => loadCategories(pagination.current_page + 1)}
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

