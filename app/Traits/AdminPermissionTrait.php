<?php

namespace App\Traits;

trait AdminPermissionTrait
{
    /**
     * Check if current user has permission
     */
    protected function hasPermission($permission)
    {
        $user = request()->user();
        return $user && $user->hasPermission($permission);
    }

    /**
     * Check if current user has any of the given permissions
     */
    protected function hasAnyPermission($permissions)
    {
        $user = request()->user();
        return $user && $user->hasAnyPermission($permissions);
    }

    /**
     * Check if current user has all of the given permissions
     */
    protected function hasAllPermissions($permissions)
    {
        $user = request()->user();
        return $user && $user->hasAllPermissions($permissions);
    }

    /**
     * Check if current user can access module
     */
    protected function canAccessModule($module)
    {
        $user = request()->user();
        return $user && $user->canAccessModule($module);
    }

    /**
     * Check if current user is super admin
     */
    protected function isSuperAdmin()
    {
        $user = request()->user();
        return $user && $user->isSuperAdmin();
    }

    /**
     * Get current user permissions
     */
    protected function getUserPermissions()
    {
        $user = request()->user();
        return $user ? $user->getPermissions() : [];
    }

    /**
     * Check permission and return error if not allowed
     */
    protected function checkPermission($permission, $message = 'Access denied. Insufficient permissions.')
    {
        if (!$this->hasPermission($permission)) {
            return $this->sendJsonResponse(false, $message, [
                'required_permission' => $permission,
                'user_permissions' => $this->getUserPermissions()
            ], 403);
        }
        return null;
    }

    /**
     * Check if user can perform action on resource
     */
    protected function canPerformAction($action, $resource = null)
    {
        $user = request()->user();
        return $user && $user->canPerformAction($action, $resource);
    }

    /**
     * Get permission-based response data
     */
    protected function getPermissionBasedData($data, $permissions = [])
    {
        $user = request()->user();
        if (!$user) {
            return $data;
        }

        $filteredData = $data;

        // Filter data based on permissions
        foreach ($permissions as $permission => $fields) {
            if (!$user->hasPermission($permission)) {
                if (is_array($data)) {
                    foreach ($fields as $field) {
                        unset($filteredData[$field]);
                    }
                } elseif (is_object($data)) {
                    foreach ($fields as $field) {
                        unset($filteredData->$field);
                    }
                }
            }
        }

        return $filteredData;
    }

    /**
     * Add permission info to response
     */
    protected function addPermissionInfo($response)
    {
        $user = request()->user();
        if (!$user) {
            return $response;
        }

        $response['permissions'] = [
            'user_permissions' => $this->getUserPermissions(),
            'is_super_admin' => $this->isSuperAdmin(),
            'role' => $user->adminRole?->name ?? 'No Role',
            'can_access_modules' => [
                'products' => $this->canAccessModule('products'),
                'orders' => $this->canAccessModule('orders'),
                'users' => $this->canAccessModule('users'),
                'categories' => $this->canAccessModule('categories'),
                'discounts' => $this->canAccessModule('discounts'),
                'coupons' => $this->canAccessModule('coupons'),
                'locations' => $this->canAccessModule('locations'),
                'delivery' => $this->canAccessModule('delivery'),
                'returns' => $this->canAccessModule('returns'),
                'reports' => $this->canAccessModule('reports'),
                'admin' => $this->canAccessModule('admin'),
            ]
        ];

        return $response;
    }
}
