// @ts-nocheck
/**
 * Permission checking utilities
 * 
 * This file provides helper functions to check user permissions in the frontend.
 * Permissions are passed from the backend via Inertia props.
 */

/**
 * Check if user has a specific permission
 * @param user - User object from Inertia props (auth.user)
 * @param permission - Permission string in format "module:action" (e.g., "products:view")
 * @returns boolean - true if user has permission, false otherwise
 */
export function hasPermission(user, permission) {
    if (!user) return false;
    
    // Super admin has all permissions
    if (user.role === 'super_admin' || (user.is_admin && user.role === 'super_admin')) {
        return true;
    }
    
    // Check if user has the specific permission
    const permissions = user.permissions || [];
    return permissions.includes(permission);
}

/**
 * Check if user has any of the specified permissions
 * @param user - User object from Inertia props (auth.user)
 * @param permissions - Array of permission strings
 * @returns boolean - true if user has at least one permission
 */
export function hasAnyPermission(user, permissions) {
    if (!user) return false;
    
    // Super admin has all permissions
    if (user.role === 'super_admin' || (user.is_admin && user.role === 'super_admin')) {
        return true;
    }
    
    const userPermissions = user.permissions || [];
    return permissions.some(perm => userPermissions.includes(perm));
}

/**
 * Check if user has all of the specified permissions
 * @param user - User object from Inertia props (auth.user)
 * @param permissions - Array of permission strings
 * @returns boolean - true if user has all permissions
 */
export function hasAllPermissions(user, permissions) {
    if (!user) return false;
    
    // Super admin has all permissions
    if (user.role === 'super_admin' || (user.is_admin && user.role === 'super_admin')) {
        return true;
    }
    
    const userPermissions = user.permissions || [];
    return permissions.every(perm => userPermissions.includes(perm));
}

/**
 * Check if user can view a module (has view permission)
 * @param user - User object from Inertia props (auth.user)
 * @param module - Module name (e.g., "products", "categories")
 * @returns boolean - true if user can view the module
 */
export function canViewModule(user, module) {
    return hasPermission(user, `${module}:view`);
}

/**
 * Check if user can create in a module
 * @param user - User object from Inertia props (auth.user)
 * @param module - Module name
 * @returns boolean - true if user can create
 */
export function canCreateModule(user, module) {
    return hasPermission(user, `${module}:create`);
}

/**
 * Check if user can update in a module
 * @param user - User object from Inertia props (auth.user)
 * @param module - Module name
 * @returns boolean - true if user can update
 */
export function canUpdateModule(user, module) {
    return hasPermission(user, `${module}:update`);
}

/**
 * Check if user can delete in a module
 * @param user - User object from Inertia props (auth.user)
 * @param module - Module name
 * @returns boolean - true if user can delete
 */
export function canDeleteModule(user, module) {
    return hasPermission(user, `${module}:delete`);
}
