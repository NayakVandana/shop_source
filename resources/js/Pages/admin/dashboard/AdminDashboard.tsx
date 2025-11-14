// @ts-nocheck
import React, { useEffect, useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import axios from 'axios';
import AdminLayout from '../../../Layouts/AdminLayout';
import Card from '../../../Components/ui/Card';
import Button from '../../../Components/ui/Button';
import { Heading, Text } from '../../../Components/ui/Typography';
import { canViewModule } from '../permissions/helpers/permissions';

export default function AdminDashboard() {
    const { auth } = usePage().props;
    const user = auth.user;
    const [stats, setStats] = useState({});
    const [recentProducts, setRecentProducts] = useState([]);
    const [topCategories, setTopCategories] = useState([]);
    const [recentUsers, setRecentUsers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        const urlParams = new URLSearchParams(window.location.search);
        const token = urlParams.get('token') || localStorage.getItem('admin_token') || '';
        
        axios
            .post('/api/admin/dashboard/stats', {}, {
                headers: { AdminToken: token }
            })
            .then((res) => {
                if (res.data && res.data.status) {
                    const data = res.data.data || {};
                    setStats(data.stats || {});
                    setRecentProducts(data.recent_products || []);
                    setTopCategories(data.top_categories || []);
                    setRecentUsers(data.recent_users || []);
                } else {
                    setStats({});
                    if (res.data && res.data.message) {
                        setError(res.data.message);
                    }
                }
            })
            .catch((err) => {
                setError('Failed to load admin stats');
                console.error('Dashboard error:', err);
            })
            .finally(() => setLoading(false));
    }, []);

    const tokenParam = typeof window !== 'undefined' 
        ? (new URLSearchParams(window.location.search).get('token') || localStorage.getItem('admin_token') || '')
        : '';
    const tokenQuery = tokenParam ? `?token=${tokenParam}` : '';

    const getImageUrl = (product) => {
        if (product.media && product.media.length > 0) {
            const imageMedia = product.media.find(m => m.type === 'image') || product.media[0];
            if (imageMedia) {
                const url = imageMedia.url || (imageMedia.file_path?.startsWith('http') 
                    ? imageMedia.file_path 
                    : `/storage/${imageMedia.file_path}`);
                return url || '/images/placeholder.svg';
            }
        }
        return '/images/placeholder.svg';
    };

    return (
        <AdminLayout>
            <Head title="Admin Dashboard" />
            <div className="p-4 sm:p-6 lg:p-8">
                <Heading level={1} className="mb-6">Admin Dashboard</Heading>

                {loading && (
                    <Card>
                        <div className="text-center py-12">
                            <Text>Loading dashboard data...</Text>
                        </div>
                    </Card>
                )}

                {error && (
                    <Card>
                        <div className="text-center py-12 text-red-600">
                            <Text>{error}</Text>
                        </div>
                    </Card>
                )}

                {!loading && !error && (
                    <>
                        {/* Statistics Cards */}
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 mb-6">
                            {/* Total Products - Only show if user has products:view permission */}
                            {canViewModule(user, 'products') && (
                                <Link href={`/admin/products${tokenQuery}`}>
                                    <Card className="cursor-pointer hover:shadow-lg transition-shadow">
                                        <div className="p-4">
                                            <Text className="text-sm text-gray-500 mb-1">Total Products</Text>
                                            <Heading level={2} className="text-3xl font-bold text-gray-900">{stats.total_products || 0}</Heading>
                                            <Text className="text-xs text-gray-500 mt-1">
                                                {stats.active_products || 0} active, {stats.inactive_products || 0} inactive
                                            </Text>
                                        </div>
                                    </Card>
                                </Link>
                            )}
                            
                            {/* Total Categories - Only show if user has categories:view permission */}
                            {canViewModule(user, 'categories') && (
                                <Link href={`/admin/categories${tokenQuery}`}>
                                    <Card className="cursor-pointer hover:shadow-lg transition-shadow">
                                        <div className="p-4">
                                            <Text className="text-sm text-gray-500 mb-1">Total Categories</Text>
                                            <Heading level={2} className="text-3xl font-bold text-gray-900">{stats.total_categories || 0}</Heading>
                    </div>
                                    </Card>
                                </Link>
                            )}
                            
                            {/* Total Users - Only show if user has users:view permission */}
                            {canViewModule(user, 'users') && (
                                <Link href={`/admin/users${tokenQuery}`}>
                                    <Card className="cursor-pointer hover:shadow-lg transition-shadow">
                                        <div className="p-4">
                                            <Text className="text-sm text-gray-500 mb-1">Total Users</Text>
                                            <Heading level={2} className="text-3xl font-bold text-gray-900">{stats.total_users || 0}</Heading>
                                            <Text className="text-xs text-gray-500 mt-1">
                                                {stats.active_users || 0} active, {stats.total_admin_users || 0} admins
                                            </Text>
                    </div>
                                    </Card>
                                </Link>
                            )}
                            
                            {/* Featured Products - Only show if user has products:view permission */}
                            {canViewModule(user, 'products') && (
                                <Link href={`/admin/products${tokenQuery}`}>
                                    <Card className="cursor-pointer hover:shadow-lg transition-shadow">
                                        <div className="p-4">
                                            <Text className="text-sm text-gray-500 mb-1">Featured Products</Text>
                                            <Heading level={2} className="text-3xl font-bold text-gray-900">{stats.featured_products || 0}</Heading>
                    </div>
                                    </Card>
                                </Link>
                            )}
                            
                            {/* Low Stock Products - Only show if user has products:view permission */}
                            {canViewModule(user, 'products') && (
                                <Link href={`/admin/products${tokenQuery}`}>
                                    <Card className={`cursor-pointer hover:shadow-lg transition-shadow ${stats.low_stock_products > 0 ? 'border-l-4 border-yellow-500' : ''}`}>
                                        <div className="p-4">
                                            <Text className="text-sm text-gray-500 mb-1">Low Stock Products</Text>
                                            <Heading level={2} className={`text-3xl font-bold ${stats.low_stock_products > 0 ? 'text-yellow-600' : 'text-gray-900'}`}>
                                                {stats.low_stock_products || 0}
                                            </Heading>
                                            {stats.low_stock_products > 0 && (
                                                <Text className="text-xs text-yellow-600 mt-1">Needs attention</Text>
                                            )}
                    </div>
                                    </Card>
                                </Link>
                            )}
                            
                            {/* Out of Stock - Only show if user has products:view permission */}
                            {canViewModule(user, 'products') && (
                                <Link href={`/admin/products${tokenQuery}`}>
                                    <Card className={`cursor-pointer hover:shadow-lg transition-shadow ${stats.out_of_stock_products > 0 ? 'border-l-4 border-red-500' : ''}`}>
                                        <div className="p-4">
                                            <Text className="text-sm text-gray-500 mb-1">Out of Stock</Text>
                                            <Heading level={2} className={`text-3xl font-bold ${stats.out_of_stock_products > 0 ? 'text-red-600' : 'text-gray-900'}`}>
                                                {stats.out_of_stock_products || 0}
                                            </Heading>
                                            {stats.out_of_stock_products > 0 && (
                                                <Text className="text-xs text-red-600 mt-1">Urgent restock needed</Text>
                                            )}
                    </div>
                                    </Card>
                                </Link>
                            )}
                </div>

                        {/* Quick Actions */}
                        <Card className="mb-6">
                            <Heading level={2} className="mb-4">Quick Actions</Heading>
                            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                                {canViewModule(user, 'products') && (
                                    <Link href={`/admin/products/create${tokenQuery}`}>
                                        <Button block>Add New Product</Button>
                                    </Link>
                                )}
                                {canViewModule(user, 'categories') && (
                                    <Link href={`/admin/categories${tokenQuery}`}>
                                        <Button variant="outline" block>Manage Categories</Button>
                                    </Link>
                                )}
                                {canViewModule(user, 'users') && (
                                    <Link href={`/admin/users${tokenQuery}`}>
                                        <Button variant="outline" block>Manage Users</Button>
                                    </Link>
                                )}
                                {canViewModule(user, 'permissions') && (
                                    <Link href={`/admin/permissions${tokenQuery}`}>
                                        <Button variant="outline" block>Manage Permissions</Button>
                                    </Link>
                                )}
                            </div>
                        </Card>

                        {/* Recent Data */}
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            {/* Recent Products - Only show if user has products:view permission */}
                            {canViewModule(user, 'products') && (
                                <Card>
                                    <Heading level={2} className="mb-4">Recent Products</Heading>
                                    {recentProducts.length === 0 ? (
                                        <Text muted>No recent products</Text>
                                    ) : (
                                        <div className="space-y-3">
                                            {recentProducts.map((product) => (
                                                <div key={product.uuid} className="flex items-center gap-3 p-2 hover:bg-gray-50 rounded">
                                                    <img
                                                        src={getImageUrl(product)}
                                                        alt={product.name}
                                                        className="w-12 h-12 object-cover rounded"
                                                        onError={(e) => {
                                                            e.target.src = '/images/placeholder.svg';
                                                        }}
                                                    />
                                                    <div className="flex-1 min-w-0">
                                                        <Text className="font-medium text-sm truncate">{product.name}</Text>
                                                        <Text className="text-xs text-gray-500">{product.category?.name || 'No category'}</Text>
                                                    </div>
                                                    <Link href={`/admin/products/edit${tokenQuery ? tokenQuery + '&' : '?'}id=${product.uuid}`}>
                                                        <Button size="sm" variant="outline">View</Button>
                                                    </Link>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                    <div className="mt-4">
                                        <Link href={`/admin/products${tokenQuery}`}>
                                            <Button variant="outline" size="sm" block>View All Products</Button>
                                        </Link>
                                    </div>
                                </Card>
                            )}

                            {/* Recent Users - Only show if user has users:view permission */}
                            {canViewModule(user, 'users') && (
                                <Card>
                                    <Heading level={2} className="mb-4">Recent Users</Heading>
                                    {recentUsers.length === 0 ? (
                                        <Text muted>No recent users</Text>
                                    ) : (
                                        <div className="space-y-3">
                                            {recentUsers.map((userItem) => (
                                                <div key={userItem.uuid} className="flex items-center gap-3 p-2 hover:bg-gray-50 rounded">
                                                    <div className="w-10 h-10 bg-indigo-600 rounded-full flex items-center justify-center">
                                                        <span className="text-white text-sm font-medium">
                                                            {userItem.name?.charAt(0).toUpperCase() || 'U'}
                                                        </span>
                                                    </div>
                                                    <div className="flex-1 min-w-0">
                                                        <Text className="font-medium text-sm truncate">{userItem.name}</Text>
                                                        <Text className="text-xs text-gray-500 truncate">{userItem.email || 'No email'}</Text>
                                                        <div className="flex gap-2 mt-1">
                                                            {userItem.is_admin && (
                                                                <span className="text-xs px-2 py-0.5 bg-purple-100 text-purple-800 rounded">Admin</span>
                                                            )}
                                                            <span className={`text-xs px-2 py-0.5 rounded ${
                                                                userItem.is_active 
                                                                    ? 'bg-green-100 text-green-800' 
                                                                    : 'bg-red-100 text-red-800'
                                                            }`}>
                                                                {userItem.is_active ? 'Active' : 'Inactive'}
                                                            </span>
                    </div>
                </div>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                    <div className="mt-4">
                                        <Link href={`/admin/users${tokenQuery}`}>
                                            <Button variant="outline" size="sm" block>View All Users</Button>
                                        </Link>
                                    </div>
                                </Card>
                            )}
                        </div>

                        {/* Top Categories - Only show if user has categories:view permission */}
                        {canViewModule(user, 'categories') && topCategories.length > 0 && (
                            <Card className="mt-6">
                                <Heading level={2} className="mb-4">Top Categories</Heading>
                                <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4">
                                    {topCategories.map((category) => (
                                        <div key={category.uuid} className="text-center p-3 bg-gray-50 rounded">
                                            <Text className="font-medium">{category.name}</Text>
                                            <Text className="text-sm text-gray-500">{category.products_count || 0} products</Text>
                                        </div>
                                    ))}
                                </div>
                            </Card>
                        )}
                    </>
                )}
            </div>
        </AdminLayout>
    );
}

