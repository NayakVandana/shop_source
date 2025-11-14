// @ts-nocheck
import React, { useEffect, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import axios from 'axios';
import AdminLayout from '../../../Layouts/AdminLayout';
import Card from '../../../Components/ui/Card';
import Button from '../../../Components/ui/Button';
import { Heading, Text } from '../../../Components/ui/Typography';

export default function AdminUsers() {
    const [users, setUsers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [search, setSearch] = useState('');
    const [filterRole, setFilterRole] = useState('');
    const [filterAdmin, setFilterAdmin] = useState('');
    const [filterStatus, setFilterStatus] = useState('');
    const [pagination, setPagination] = useState({ current_page: 1, last_page: 1 });

    useEffect(() => {
        loadUsers();
    }, []);

    const loadUsers = async (page = 1) => {
        setLoading(true);
        setError(null);
        try {
            const urlParams = new URLSearchParams(window.location.search);
            const token = urlParams.get('token') || localStorage.getItem('admin_token') || '';
            
            const params: any = {
                per_page: 15,
                page,
            };
            
            // Search filter
            if (search && search.trim()) {
                params.search = search.trim();
            }
            
            // Role filter
            if (filterRole && filterRole.trim()) {
                params.role = filterRole;
            }
            
            // Admin status filter
            if (filterAdmin !== '') {
                params.is_admin = filterAdmin === 'admin';
            }
            
            // Active status filter
            if (filterStatus !== '') {
                params.is_active = filterStatus === 'active';
            }

            console.log('Loading users with params:', params);
            console.log('API endpoint: /api/admin/users/index');
            console.log('Token present:', !!token);

            const res = await axios.post('/api/admin/users/index', params, {
                headers: { 
                    'AdminToken': token,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });
            
            console.log('API response:', res.data);
            
            if (res.data && res.data.status) {
                const data = res.data.data;
                const usersList = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : []);
                setUsers(usersList);
                setPagination({
                    current_page: data?.current_page || 1,
                    last_page: data?.last_page || 1,
                    total: data?.total || 0,
                    per_page: data?.per_page || 15,
                });
                setError(null);
            } else {
                setUsers([]);
                const errorMsg = res.data?.message || 'Failed to load users';
                setError(errorMsg);
                console.error('API returned error:', errorMsg);
            }
        } catch (err: any) {
            const errorMsg = err.response?.data?.message || err.message || 'Failed to load users';
            setError(errorMsg);
            console.error('Error loading users:', err);
            if (err.response) {
                console.error('Error status:', err.response.status);
                console.error('Error response data:', err.response.data);
            }
            setUsers([]);
        } finally {
            setLoading(false);
        }
    };

    const handleSearch = (e?: React.FormEvent) => {
        if (e) {
            e.preventDefault();
        }
        // Reset to page 1 when applying filters
        setPagination({ current_page: 1, last_page: 1 });
        loadUsers(1);
    };

    const handleResetFilters = () => {
        setSearch('');
        setFilterRole('');
        setFilterAdmin('');
        setFilterStatus('');
        // Load users with empty filters after a short delay to allow state to update
        setTimeout(() => {
            loadUsers(1);
        }, 100);
    };

    const handleDelete = async (uuid: string) => {
        if (!confirm('Are you sure you want to delete this user?')) return;
        
        try {
            const urlParams = new URLSearchParams(window.location.search);
            const token = urlParams.get('token') || localStorage.getItem('admin_token') || '';
            
            await axios.post('/api/admin/users/destroy', { id: uuid }, {
                headers: { AdminToken: token }
            });
            loadUsers(pagination.current_page);
        } catch (err) {
            alert(err.response?.data?.message || 'Failed to delete user');
        }
    };

    const formatDate = (dateString) => {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    };

    const getRoleLabel = (role) => {
        const roleLabels = {
            'user': 'User',
            'admin': 'Admin',
            'super_admin': 'Super Admin',
            'sales': 'Sales',
            'marketing': 'Marketing',
            'tester': 'Tester',
            'developer': 'Developer',
        };
        return roleLabels[role] || role;
    };

    const tokenParam = typeof window !== 'undefined' 
        ? (new URLSearchParams(window.location.search).get('token') || localStorage.getItem('admin_token') || '')
        : '';
    const tokenQuery = tokenParam ? `?token=${tokenParam}` : '';

    return (
        <AdminLayout>
            <Head title="Admin - Users" />
            <div className="p-4 sm:p-6 lg:p-8">
                <div className="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <Heading level={1}>Users Management</Heading>
                </div>

                {/* Filters */}
                <Card className="mb-6">
                    <form onSubmit={handleSearch}>
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Search</label>
                                <input
                                    type="text"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyPress={(e) => {
                                        if (e.key === 'Enter') {
                                            e.preventDefault();
                                            handleSearch();
                                        }
                                    }}
                                    placeholder="Name, Email, Mobile..."
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Role</label>
                                <select
                                    value={filterRole}
                                    onChange={(e) => setFilterRole(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                    <option value="">All Roles</option>
                                    <option value="user">User</option>
                                    <option value="admin">Admin</option>
                                    <option value="super_admin">Super Admin</option>
                                    <option value="sales">Sales</option>
                                    <option value="marketing">Marketing</option>
                                    <option value="tester">Tester</option>
                                    <option value="developer">Developer</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Admin Status</label>
                                <select
                                    value={filterAdmin}
                                    onChange={(e) => setFilterAdmin(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                    <option value="">All</option>
                                    <option value="admin">Admin</option>
                                    <option value="user">Regular User</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                <select
                                    value={filterStatus}
                                    onChange={(e) => setFilterStatus(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                    <option value="">All</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div className="flex items-end gap-2">
                                <Button type="submit" block>Apply Filters</Button>
                                <Button type="button" variant="outline" onClick={handleResetFilters} block>Reset</Button>
                            </div>
                        </div>
                    </form>
                </Card>

                {/* Users Table */}
                {loading ? (
                    <Card>
                        <div className="text-center py-12">
                            <Text>Loading users...</Text>
                        </div>
                    </Card>
                ) : error ? (
                    <Card>
                        <div className="text-center py-12 text-red-600">
                            <Text>{error}</Text>
                        </div>
                    </Card>
                ) : users.length === 0 ? (
                    <Card>
                        <div className="text-center py-12">
                            <Text muted>No users found</Text>
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
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mobile</th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Login</th>
                                            <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {users.map((user) => (
                                            <tr key={user.uuid} className="hover:bg-gray-50">
                                                <td className="px-4 py-3">
                                                    <div className="text-sm font-medium text-gray-900">{user.name}</div>
                                                    <div className="text-xs text-gray-500">ID: {user.id}</div>
                                                </td>
                                                <td className="px-4 py-3 text-sm text-gray-900">
                                                    {user.email || 'N/A'}
                                                </td>
                                                <td className="px-4 py-3 text-sm text-gray-500">
                                                    {user.mobile || 'N/A'}
                                                </td>
                                                <td className="px-4 py-3 whitespace-nowrap">
                                                    <span className={`px-2 py-1 text-xs rounded-full ${
                                                        user.is_admin 
                                                            ? 'bg-purple-100 text-purple-800' 
                                                            : 'bg-gray-100 text-gray-800'
                                                    }`}>
                                                        {getRoleLabel(user.role)}
                                                    </span>
                                                    {user.is_admin && (
                                                        <div className="text-xs text-indigo-600 mt-1">Admin</div>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 whitespace-nowrap">
                                                    <div className="flex flex-col gap-1">
                                                        <span className={`px-2 py-1 text-xs rounded-full ${
                                                            user.is_active 
                                                                ? 'bg-green-100 text-green-800' 
                                                                : 'bg-red-100 text-red-800'
                                                        }`}>
                                                            {user.is_active ? 'Active' : 'Inactive'}
                                                        </span>
                                                        {!user.is_registered && (
                                                            <span className="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">
                                                                Unregistered
                                                            </span>
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="px-4 py-3 text-sm text-gray-500">
                                                    {formatDate(user.last_login_at)}
                                                </td>
                                                <td className="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                                                    <div className="flex justify-end gap-2">
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => handleDelete(user.uuid)}
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
                                    onClick={() => loadUsers(pagination.current_page - 1)}
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
                                    onClick={() => loadUsers(pagination.current_page + 1)}
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

