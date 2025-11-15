// @ts-nocheck
import React, { useEffect, useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import axios from 'axios';
import AdminLayout from '../../../Layouts/AdminLayout';
import Card from '../../../Components/ui/Card';
import Button from '../../../Components/ui/Button';
import { Heading, Text } from '../../../Components/ui/Typography';
import { hasPermission, canCreateModule, canDeleteModule, canUpdateModule } from './helpers/permissions';

export default function AdminPermissions() {
    const { auth } = usePage().props;
    const user = auth.user;
    const [roleWisePermissions, setRoleWisePermissions] = useState({});
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [roles, setRoles] = useState([]);
    
    // Check permissions
    const canView = hasPermission(user, 'permissions:view') || hasPermission(user, 'permissions:manage');
    const canCreate = canCreateModule(user, 'permissions') || hasPermission(user, 'permissions:manage');
    const canDelete = canDeleteModule(user, 'permissions') || hasPermission(user, 'permissions:manage');
    const canUpdate = canUpdateModule(user, 'permissions') || hasPermission(user, 'permissions:manage');

    useEffect(() => {
        loadRoleWisePermissions();
        loadRoles();
    }, []);

    const loadRoles = async () => {
        try {
            const token = getToken();
            const res = await axios.post('/api/admin/permissions/roles', {}, {
                headers: { AdminToken: token }
            });
            if (res.data && res.data.status) {
                // Filter out 'user' role - users don't manage permissions
                const filteredRoles = (res.data.data || []).filter(role => role.value !== 'user');
                setRoles(filteredRoles);
            }
        } catch (err) {
            console.error('Failed to load roles', err);
        }
    };

    const loadRoleWisePermissions = async () => {
        setLoading(true);
        setError(null);
        try {
            const token = getToken();
            const res = await axios.post('/api/admin/permissions/grouped-by-role', {}, {
                headers: { AdminToken: token }
            });
            
            if (res.data && res.data.status) {
                setRoleWisePermissions(res.data.data || {});
            } else {
                setRoleWisePermissions({});
                if (res.data && res.data.message) {
                    setError(res.data.message);
                }
            }
        } catch (err) {
            setError('Failed to load role-wise permissions');
            console.error(err);
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

    const handleDelete = async (id) => {
        if (!canDelete) {
            alert('You do not have permission to delete permissions');
            return;
        }
        
        if (!confirm('Are you sure you want to delete this permission? This action cannot be undone.')) return;
        
        try {
            const token = getToken();
            
            const res = await axios.post('/api/admin/permissions/destroy', { id }, {
                headers: { AdminToken: token }
            });
            
            if (res.data && res.data.status) {
                loadRoleWisePermissions();
            } else {
                alert(res.data?.message || 'Failed to delete permission');
            }
        } catch (err) {
            const errorMsg = err.response?.data?.message || 'Failed to delete permission';
            alert(errorMsg);
        }
    };

    const handleRoleToggle = async (permissionId, role, checked) => {
        if (!canUpdate) {
            alert('You do not have permission to update permissions');
            return;
        }
        
        try {
            const token = getToken();
            
            // Get current permission to find its roles from roleWisePermissions
            let currentRoles = [];
            Object.values(roleWisePermissions).forEach(rolePerms => {
                const perm = rolePerms.find(p => p.id === permissionId);
                if (perm) {
                    // Get all roles this permission is assigned to
                    Object.entries(roleWisePermissions).forEach(([r, perms]) => {
                        if (perms.find(p => p.id === permissionId)) {
                            if (!currentRoles.includes(r)) {
                                currentRoles.push(r);
                            }
                        }
                    });
                }
            });
            
            // Update roles array
            let updatedRoles = [...currentRoles];
            if (checked) {
                if (!updatedRoles.includes(role)) {
                    updatedRoles.push(role);
                }
            } else {
                updatedRoles = updatedRoles.filter(r => r !== role);
            }
            
            // Update via API
            const res = await axios.post('/api/admin/permissions/update-roles', {
                id: permissionId,
                roles: updatedRoles
            }, {
                headers: { AdminToken: token }
            });
            
            if (res.data && res.data.status) {
                loadRoleWisePermissions();
            } else {
                alert(res.data?.message || 'Failed to update roles');
            }
        } catch (err) {
            const errorMsg = err.response?.data?.message || 'Failed to update roles';
            alert(errorMsg);
        }
    };


    return (
        <AdminLayout>
            <Head title="Admin - Permissions" />
            <div className="p-4 sm:p-6 lg:p-8">
                <div className="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <Heading level={1}>Permissions Management</Heading>
                    {canCreate && (
                        <Link href={`/admin/permissions/bulk-create`}>
                            <Button>Create by Module</Button>
                        </Link>
                    )}
                </div>

                {/* Permissions Display */}
                {loading ? (
                    <Card>
                        <div className="text-center py-12">
                            <Text>Loading permissions...</Text>
                        </div>
                    </Card>
                ) : error ? (
                    <Card>
                        <div className="text-center py-12 text-red-600">
                            <Text>{error}</Text>
                        </div>
                    </Card>
                ) : Object.keys(roleWisePermissions).length === 0 ? (
                    <Card>
                        <div className="text-center py-12">
                            <Text muted>No permissions found</Text>
                        </div>
                    </Card>
                ) : (
                    <div className="space-y-6">
                        {Object.entries(roleWisePermissions).map(([role, rolePermissions]) => {
                            const roleLabel = roles.find(r => r.value === role)?.label || role.charAt(0).toUpperCase() + role.slice(1);
                            return (
                                <Card key={role} className="overflow-hidden">
                                    <div className="bg-indigo-50 px-4 py-3 border-b border-indigo-200 flex items-center justify-between">
                                        <div>
                                            <Heading level={3} className="text-lg font-semibold text-gray-900">
                                                {roleLabel} Role
                                            </Heading>
                                            <Text className="text-sm text-gray-600 mt-1">
                                                {rolePermissions.length} permission(s)
                                            </Text>
                                        </div>
                                        {canCreate && (
                                            <Link href={`/admin/permissions/bulk-create?role=${role}`}>
                                                <Button size="sm" variant="outline">Add Permissions</Button>
                                            </Link>
                                        )}
                                    </div>
                                    <div className="p-4">
                                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                            {rolePermissions.map((permission) => (
                                                <div key={permission.id} className="border border-gray-200 rounded-lg p-3 hover:bg-gray-50">
                                                    <div className="flex items-start justify-between">
                                                        <div className="flex-1">
                                                            <div className="font-medium text-gray-900">{permission.name}</div>
                                                            <div className="text-xs text-gray-500 mt-1">
                                                                Module: {permission.module || '-'} | Action: {permission.action || '-'}
                                                            </div>
                                                            {permission.description && (
                                                                <div className="text-xs text-gray-600 mt-1 line-clamp-2">
                                                                    {permission.description}
                                                                </div>
                                                            )}
                                                        </div>
                                                        {(canUpdate || canDelete) && (
                                                            <div className="flex gap-1 ml-2">
                                                                {canUpdate && (
                                                                    <Button
                                                                        variant="outline"
                                                                        size="sm"
                                                                        onClick={() => handleRoleToggle(permission.id, role, false)}
                                                                        className="text-red-600 hover:bg-red-50"
                                                                        title="Remove from this role"
                                                                    >
                                                                        Remove
                                                                    </Button>
                                                                )}
                                                                {canDelete && (
                                                                    <Button
                                                                        variant="outline"
                                                                        size="sm"
                                                                        onClick={() => handleDelete(permission.id)}
                                                                        className="text-red-600 hover:bg-red-50"
                                                                        title="Delete permission"
                                                                    >
                                                                        Delete
                                                                    </Button>
                                                                )}
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </Card>
                            );
                        })}
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}

