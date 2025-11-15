// @ts-nocheck
import React, { useEffect, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import axios from 'axios';
import AdminLayout from '../../../Layouts/AdminLayout';
import Card from '../../../Components/ui/Card';
import Button from '../../../Components/ui/Button';
import { Heading, Text } from '../../../Components/ui/Typography';

export default function AdminDiscounts() {
    const [discounts, setDiscounts] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [search, setSearch] = useState('');
    const [filterStatus, setFilterStatus] = useState('');
    const [filterType, setFilterType] = useState('');
    const [pagination, setPagination] = useState({ current_page: 1, last_page: 1, total: 0, per_page: 10 });
    const [counts, setCounts] = useState({ total: 0, active: 0, inactive: 0 });

    useEffect(() => {
        loadDiscounts();
    }, []);

    const loadDiscounts = async (page = 1) => {
        setLoading(true);
        setError(null);
        try {
            const urlParams = new URLSearchParams(window.location.search);
            const token = urlParams.get('token') || localStorage.getItem('admin_token') || '';
            
            const params: any = {
                per_page: 10,
                page,
            };
            if (search) params.search = search;
            if (filterStatus !== '') params.is_active = filterStatus === 'active';
            if (filterType) params.type = filterType;

            const res = await axios.post('/api/admin/discounts/index', params, {
                headers: { 
                    'AdminToken': token,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });
            
            if (res.data && res.data.status) {
                const data = res.data.data;
                setDiscounts(Array.isArray(data?.data) ? data.data : []);
                setPagination({
                    current_page: data?.current_page || 1,
                    last_page: data?.last_page || 1,
                    total: data?.total || 0,
                    per_page: data?.per_page || 10,
                });
                if (data?.counts) {
                    setCounts({
                        total: data.counts.total || 0,
                        active: data.counts.active || 0,
                        inactive: data.counts.inactive || 0,
                    });
                }
            } else {
                setDiscounts([]);
                if (res.data && res.data.message) {
                    setError(res.data.message);
                }
            }
        } catch (err) {
            setError('Failed to load discounts');
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    const handleSearch = () => {
        loadDiscounts(1);
    };

    const handleDelete = async (uuid: string) => {
        if (!confirm('Are you sure you want to delete this discount?')) return;
        
        try {
            const urlParams = new URLSearchParams(window.location.search);
            const token = urlParams.get('token') || localStorage.getItem('admin_token') || '';
            
            await axios.post('/api/admin/discounts/destroy', { id: uuid }, {
                headers: { AdminToken: token }
            });
            loadDiscounts(pagination.current_page);
        } catch (err) {
            alert('Failed to delete discount');
        }
    };

    const formatDate = (dateString) => {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleDateString();
    };

    const formatDiscountValue = (discount) => {
        if (discount.type === 'percentage') {
            return `${discount.value}%`;
        }
        return `$${discount.value}`;
    };

    const tokenParam = typeof window !== 'undefined' 
        ? (new URLSearchParams(window.location.search).get('token') || localStorage.getItem('admin_token') || '')
        : '';
    const tokenQuery = tokenParam ? `?token=${tokenParam}` : '';

    return (
        <AdminLayout>
            <Head title="Admin - Discounts" />
            <div className="p-4 sm:p-6 lg:p-8">
                <div className="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <Heading level={1}>Discounts Management</Heading>
                        {!loading && (
                            <div className="mt-2 flex flex-wrap items-center gap-4 text-sm text-gray-600">
                                <Text className="text-sm text-gray-500">
                                    <span className="font-medium text-gray-900">{counts.total || 0}</span> total
                                </Text>
                                <Text className="text-sm text-gray-500">
                                    <span className="font-medium text-green-600">{counts.active || 0}</span> active, <span className="font-medium text-gray-600">{counts.inactive || 0}</span> inactive
                                </Text>
                            </div>
                        )}
                    </div>
                    <Link href={`/admin/discounts/create${tokenQuery}`}>
                        <Button>Add New Discount</Button>
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
                                placeholder="Name, description..."
                                className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Type</label>
                            <select
                                value={filterType}
                                onChange={(e) => setFilterType(e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                            >
                                <option value="">All Types</option>
                                <option value="percentage">Percentage</option>
                                <option value="fixed">Fixed Amount</option>
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

                {/* Discounts Table */}
                {loading ? (
                    <Card>
                        <div className="text-center py-12">
                            <Text>Loading discounts...</Text>
                        </div>
                    </Card>
                ) : error ? (
                    <Card>
                        <div className="text-center py-12 text-red-600">
                            <Text>{error}</Text>
                        </div>
                    </Card>
                ) : discounts.length === 0 ? (
                    <Card>
                        <div className="text-center py-12">
                            <Text muted>No discounts found</Text>
                        </div>
                    </Card>
                ) : (
                    <>
                        <Card padding="none" className="overflow-hidden">
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Products</th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valid Period</th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usage</th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {discounts.map((discount) => (
                                            <tr key={discount.uuid} className="hover:bg-gray-50">
                                                <td className="px-4 py-3">
                                                    <div className="text-sm font-medium text-gray-900">{discount.name}</div>
                                                    {discount.description && (
                                                        <div className="text-xs text-gray-500 mt-1">{discount.description.substring(0, 50)}...</div>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 text-sm text-gray-500 capitalize">
                                                    {discount.type}
                                                </td>
                                                <td className="px-4 py-3 text-sm font-medium text-gray-900">
                                                    {formatDiscountValue(discount)}
                                                </td>
                                                <td className="px-4 py-3 text-sm text-gray-500">
                                                    {discount.products?.length || 0} products
                                                </td>
                                                <td className="px-4 py-3 text-sm text-gray-500">
                                                    <div>{formatDate(discount.start_date)}</div>
                                                    <div className="text-xs">to {formatDate(discount.end_date)}</div>
                                                </td>
                                                <td className="px-4 py-3 text-sm text-gray-500">
                                                    {discount.usage_count || 0} / {discount.usage_limit || '∞'}
                                                </td>
                                                <td className="px-4 py-3 whitespace-nowrap">
                                                    <span className={`px-2 py-1 text-xs rounded-full ${
                                                        discount.is_active 
                                                            ? 'bg-green-100 text-green-800' 
                                                            : 'bg-red-100 text-red-800'
                                                    }`}>
                                                        {discount.is_active ? 'Active' : 'Inactive'}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                                                    <div className="flex justify-end gap-2">
                                                        <Link href={`/admin/discounts/edit${tokenQuery ? tokenQuery + '&' : '?'}id=${discount.uuid}`}>
                                                            <Button variant="outline" size="sm">Edit</Button>
                                                        </Link>
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => handleDelete(discount.uuid)}
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
                                    onClick={() => loadDiscounts(pagination.current_page - 1)}
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
                                    onClick={() => loadDiscounts(pagination.current_page + 1)}
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

