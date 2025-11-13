// @ts-nocheck
import React, { useEffect, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import axios from 'axios';
import AdminLayout from '../../../Layouts/AdminLayout';
import Card from '../../../Components/ui/Card';
import Button from '../../../Components/ui/Button';
import { Heading, Text } from '../../../Components/ui/Typography';

export default function PermissionBulkForm() {
    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState({});
    const [generalError, setGeneralError] = useState('');
    const [roles, setRoles] = useState([]);
    const [modules, setModules] = useState([]);
    
    const [formData, setFormData] = useState({
        moduleActions: {}, // { module: [actions] }
        roles: [],
    });
    const [selectedRoleFromUrl, setSelectedRoleFromUrl] = useState(null);
    const [existingPermissions, setExistingPermissions] = useState({}); // { module: [actions] }

    // Standard actions available
    const standardActions = [
        { value: 'view', label: 'View' },
        { value: 'create', label: 'Create' },
        { value: 'update', label: 'Update' },
        { value: 'delete', label: 'Delete' },
        { value: 'manage', label: 'Manage' },
        { value: 'statistics', label: 'Statistics' },
    ];

    useEffect(() => {
        const initialize = async () => {
            // Load modules first
            await loadModules();
            // Then load roles (which will trigger loading existing permissions if role param exists)
            await loadRoles();
        };
        initialize();
    }, []);
    
    useEffect(() => {
        // Check if role is passed in URL after component mounts
        if (typeof window !== 'undefined') {
            const urlParams = new URLSearchParams(window.location.search);
            const roleParam = urlParams.get('role');
            if (roleParam) {
                setSelectedRoleFromUrl(roleParam);
            }
        }
    }, []);

    // Load existing permissions when role is selected and modules are loaded
    useEffect(() => {
        if (selectedRoleFromUrl && modules.length > 0) {
            loadExistingPermissionsForRole(selectedRoleFromUrl);
        }
    }, [selectedRoleFromUrl, modules]);

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
                
                // Check URL for role parameter and pre-select it
                if (typeof window !== 'undefined') {
                    const urlParams = new URLSearchParams(window.location.search);
                    const roleParam = urlParams.get('role');
                    if (roleParam) {
                        const roleExists = filteredRoles.find(r => r.value === roleParam);
                        if (roleExists) {
                            setSelectedRoleFromUrl(roleParam);
                            setFormData(prev => ({
                                ...prev,
                                roles: [roleParam]
                            }));
                            // Existing permissions will be loaded via useEffect when modules are ready
                        }
                    }
                }
            }
        } catch (err) {
            console.error('Failed to load roles', err);
        }
    };

    const loadExistingPermissionsForRole = async (role: string) => {
        try {
            const token = getToken();
            const res = await axios.post('/api/admin/permissions/grouped-by-role', {}, {
                headers: { AdminToken: token }
            });
            
            if (res.data && res.data.status) {
                const rolePermissions = res.data.data[role] || [];
                
                // Group by module and collect actions
                const moduleActionsMap = {};
                rolePermissions.forEach(permission => {
                    if (permission.module && permission.action) {
                        // Only include modules that exist in the modules list
                        if (modules.includes(permission.module)) {
                            if (!moduleActionsMap[permission.module]) {
                                moduleActionsMap[permission.module] = [];
                            }
                            if (!moduleActionsMap[permission.module].includes(permission.action)) {
                                moduleActionsMap[permission.module].push(permission.action);
                            }
                        }
                    }
                });
                
                setExistingPermissions(moduleActionsMap);
                
                // Pre-check existing permissions in formData
                // Merge with existing formData to preserve any manual selections
                setFormData(prev => ({
                    ...prev,
                    moduleActions: {
                        ...prev.moduleActions,
                        ...moduleActionsMap
                    }
                }));
            }
        } catch (err) {
            console.error('Failed to load existing permissions', err);
        }
    };

    const loadModules = async () => {
        try {
            const token = getToken();
            const res = await axios.post('/api/admin/permissions/modules', {}, {
                headers: { AdminToken: token }
            });
            if (res.data && res.data.status) {
                const modulesList = res.data.data || [];
                setModules(modulesList);
                return modulesList;
            }
            return [];
        } catch (err) {
            console.error('Failed to load modules', err);
            return [];
        }
    };

    const getToken = () => {
        if (typeof window === 'undefined') return '';
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('token') || localStorage.getItem('admin_token') || '';
    };

    const handleModuleActionChange = (moduleValue: string, actionValue: string, checked: boolean) => {
        setFormData(prev => {
            const moduleActions = { ...prev.moduleActions };
            if (!moduleActions[moduleValue]) {
                moduleActions[moduleValue] = [];
            }
            
            if (checked) {
                if (!moduleActions[moduleValue].includes(actionValue)) {
                    moduleActions[moduleValue] = [...moduleActions[moduleValue], actionValue];
                }
            } else {
                moduleActions[moduleValue] = moduleActions[moduleValue].filter(a => a !== actionValue);
            }
            
            // Remove module if no actions selected
            if (moduleActions[moduleValue].length === 0) {
                delete moduleActions[moduleValue];
            }
            
            return { ...prev, moduleActions };
        });
    };

    const handleSelectAllActionsForModule = (moduleValue: string, checked: boolean) => {
        setFormData(prev => {
            const moduleActions = { ...prev.moduleActions };
            if (checked) {
                moduleActions[moduleValue] = standardActions.map(a => a.value);
            } else {
                delete moduleActions[moduleValue];
            }
            return { ...prev, moduleActions };
        });
    };

    const handleRoleChange = (roleValue: string, checked: boolean) => {
        setFormData(prev => {
            const roles = prev.roles || [];
            if (checked) {
                return { ...prev, roles: [...roles, roleValue] };
            } else {
                return { ...prev, roles: roles.filter(r => r !== roleValue) };
            }
        });
    };


    const handleSelectAllRoles = (checked: boolean) => {
        if (checked) {
            setFormData(prev => ({
                ...prev,
                roles: roles.map(r => r.value)
            }));
        } else {
            setFormData(prev => ({
                ...prev,
                roles: []
            }));
        }
    };

    const validateForm = () => {
        const validationErrors = {};
        
        const selectedModules = Object.keys(formData.moduleActions || {});
        if (selectedModules.length === 0) {
            validationErrors.moduleActions = 'At least one module with actions must be selected.';
        }
        
        // Check if at least one module has at least one action
        const hasActions = selectedModules.some(module => 
            formData.moduleActions[module] && formData.moduleActions[module].length > 0
        );
        
        if (!hasActions) {
            validationErrors.moduleActions = 'At least one action must be selected for at least one module.';
        }
        
        return validationErrors;
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setErrors({});
        setGeneralError('');

        const validationErrors = validateForm();
        if (Object.keys(validationErrors).length > 0) {
            setErrors(validationErrors);
            setGeneralError('Please fill in all required fields correctly.');
            setLoading(false);
            return;
        }

        try {
            const token = getToken();
            
            // Create permissions for each module with its specific actions
            const results = [];
            for (const [module, actions] of Object.entries(formData.moduleActions)) {
                if (actions && actions.length > 0) {
                    const data = {
                        module: module,
                        actions: actions,
                        roles: formData.roles,
                    };

                    const res = await axios.post('/api/admin/permissions/bulk-create', data, {
                        headers: { AdminToken: token }
                    });
                    results.push(res.data);
                }
            }

            // Check if all requests succeeded
            const allSuccess = results.every(r => r && r.status);
            
            if (allSuccess) {
                const totalCreated = results.reduce((sum, r) => sum + (r.data?.count || 0), 0);
                const tokenQuery = token ? `?token=${token}` : '';
                router.visit(`/admin/permissions${tokenQuery}`);
            } else {
                const errorData = results.find(r => r && !r.status)?.data?.errors || {};
                setErrors(errorData);
                setGeneralError('Some permissions failed to create. Please try again.');
            }
        } catch (err) {
            if (err.response?.data?.data?.errors) {
                const errorData = err.response.data.data.errors;
                setErrors(errorData);
                setGeneralError(err.response?.data?.message || 'Validation failed. Please check the errors below.');
            } else {
                setGeneralError(err.message || 'Failed to create permissions. Please try again.');
            }
        } finally {
            setLoading(false);
        }
    };

    const tokenParam = getToken();
    const tokenQuery = tokenParam ? `?token=${tokenParam}` : '';

    return (
        <AdminLayout>
            <Head title="Create Permissions by Module" />
            <div className="p-4 sm:p-6 lg:p-8">
                <div className="mb-6">
                    <Link href={`/admin/permissions${tokenQuery}`} className="text-primary-600 hover:text-primary-700 mb-4 inline-block">
                        ← Back to Permissions
                    </Link>
                    <Heading level={1}>Create Permissions by Module</Heading>
                    <Text muted className="mt-2">Create multiple permissions for a module at once by selecting actions</Text>
                    {selectedRoleFromUrl && roles.find(r => r.value === selectedRoleFromUrl) && (
                        <div className="mt-3 inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800">
                            <span>Adding permissions for: <strong>{roles.find(r => r.value === selectedRoleFromUrl)?.label || selectedRoleFromUrl}</strong></span>
                        </div>
                    )}
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
                            <Card>
                                <Heading level={2} className="mb-4">Modules & Actions</Heading>
                                
                                {errors.moduleActions && (
                                    <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded">
                                        <p className="text-sm text-red-600">{errors.moduleActions}</p>
                                    </div>
                                )}
                                
                                {modules.length === 0 ? (
                                    <div className="text-center py-8 text-gray-500">
                                        <Text className="text-sm">No modules found.</Text>
                                    </div>
                                ) : (
                                    <div className="space-y-4">
                                        {modules.map((module) => {
                                            const moduleActions = formData.moduleActions[module] || [];
                                            const existingModuleActions = existingPermissions[module] || [];
                                            const allSelected = moduleActions.length === standardActions.length;
                                            const hasExisting = existingModuleActions.length > 0;
                                            
                                            return (
                                                <div key={module} className={`overflow-hidden ${hasExisting ? 'border-2 border-blue-200' : 'border border-gray-200'} rounded-lg`}>
                                                    {/* Module Header */}
                                                    <div className={`px-4 py-2.5 flex items-center justify-between ${hasExisting ? 'bg-blue-50' : 'bg-green-100'}`}>
                                                        <div className="flex items-center gap-2">
                                                            {hasExisting && (
                                                                <span className="text-blue-600">
                                                                    <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                                        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                                                    </svg>
                                                                </span>
                                                            )}
                                                            <Heading level={3} className="text-sm font-semibold text-gray-900">
                                                                {module.charAt(0).toUpperCase() + module.slice(1)}
                                                            </Heading>
                                                            {hasExisting && (
                                                                <span className="text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full font-medium">
                                                                    {existingModuleActions.length} checked
                                                                </span>
                                                            )}
                                                        </div>
                                                        <label className="flex items-center space-x-2 cursor-pointer">
                                                            <input
                                                                type="checkbox"
                                                                checked={allSelected}
                                                                onChange={(e) => handleSelectAllActionsForModule(module, e.target.checked)}
                                                                className="w-4 h-4 rounded border-gray-300 text-green-600 focus:ring-green-500"
                                                            />
                                                            <span className="text-xs text-gray-700 font-medium">Select All</span>
                                                        </label>
                                                    </div>
                                                    
                                                    {/* Actions Grid */}
                                                    <div className="bg-gray-50 p-4">
                                                        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                                                            {standardActions.map((action) => {
                                                                const isChecked = moduleActions.includes(action.value);
                                                                const isExisting = existingModuleActions.includes(action.value);
                                                                
                                                                return (
                                                                    <label 
                                                                        key={action.value} 
                                                                        className={`flex items-center space-x-2 cursor-pointer ${
                                                                            isExisting ? 'bg-blue-50 rounded px-2 py-1' : ''
                                                                        }`}
                                                                    >
                                                                        <input
                                                                            type="checkbox"
                                                                            checked={isChecked}
                                                                            onChange={(e) => handleModuleActionChange(module, action.value, e.target.checked)}
                                                                            className="w-4 h-4 rounded border-gray-300 text-green-600 focus:ring-green-500"
                                                                        />
                                                                        <span className="text-sm text-gray-700 flex items-center gap-1">
                                                                            {action.label}
                                                                            {isExisting && (
                                                                                <span className="text-xs text-blue-600" title="Already exists for this role">
                                                                                    ✓
                                                                                </span>
                                                                            )}
                                                                        </span>
                                                                    </label>
                                                                );
                                                            })}
                                                        </div>
                                                        
                                                        <div className="mt-3 pt-2 border-t border-gray-200">
                                                            <Text className="text-xs text-gray-500">
                                                                {moduleActions.length > 0 ? (
                                                                    <>
                                                                        <span className="font-medium">{moduleActions.length}</span> action(s) selected
                                                                        {hasExisting && (
                                                                            <span className="ml-2 text-blue-600 font-medium">
                                                                                ({existingModuleActions.filter(a => moduleActions.includes(a)).length} already checked)
                                                                            </span>
                                                                        )}
                                                                    </>
                                                                ) : hasExisting ? (
                                                                    <span className="text-blue-600 font-medium">
                                                                        {existingModuleActions.length} permission(s) already exist for this role
                                                                    </span>
                                                                ) : (
                                                                    <span className="text-gray-400">No actions selected</span>
                                                                )}
                                                            </Text>
                                                        </div>
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                )}
                            </Card>
                        </div>

                        {/* Sidebar */}
                        <div className="space-y-6">
                            {/* Role Assignments */}
                            <Card>
                                <div className="flex items-center justify-between mb-4">
                                    <Heading level={2}>Assign to Roles</Heading>
                                    <label className="flex items-center space-x-2 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={formData.roles.length === roles.length && roles.length > 0}
                                            onChange={(e) => handleSelectAllRoles(e.target.checked)}
                                            className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                        <span className="text-xs text-gray-600">Select All</span>
                                    </label>
                                </div>
                                <div className="space-y-3">
                                    {roles.map((role) => (
                                        <label key={role.value} className="flex items-center space-x-2 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                checked={formData.roles?.includes(role.value) || false}
                                                onChange={(e) => handleRoleChange(role.value, e.target.checked)}
                                                className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                            />
                                            <span className="text-sm text-gray-700">{role.label}</span>
                                        </label>
                                    ))}
                                    {roles.length === 0 && (
                                        <Text muted className="text-sm">No roles available</Text>
                                    )}
                                </div>
                            </Card>

                            {/* Actions */}
                            <Card>
                                <div className="space-y-3">
                                    <Button type="submit" block disabled={loading}>
                                        {loading ? 'Creating...' : (() => {
                                            const totalPermissions = Object.values(formData.moduleActions || {}).reduce(
                                                (sum, actions) => sum + (actions?.length || 0), 0
                                            );
                                            return `Create ${totalPermissions} Permission(s)`;
                                        })()}
                                    </Button>
                                    <Link href={`/admin/permissions${tokenQuery}`}>
                                        <Button variant="outline" block>Cancel</Button>
                                    </Link>
                                    {(() => {
                                        const moduleCount = Object.keys(formData.moduleActions || {}).length;
                                        const totalPermissions = Object.values(formData.moduleActions || {}).reduce(
                                            (sum, actions) => sum + (actions?.length || 0), 0
                                        );
                                        return moduleCount > 0 && totalPermissions > 0 ? (
                                            <div className="text-xs text-gray-500 pt-2 border-t">
                                                <p className="mb-1">
                                                    {moduleCount} module(s) with selected actions
                                                </p>
                                                <p className="font-medium">
                                                    Total: {totalPermissions} permission(s)
                                                </p>
                                            </div>
                                        ) : null;
                                    })()}
                                </div>
                            </Card>
                        </div>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}

