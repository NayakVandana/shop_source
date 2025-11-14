// @ts-nocheck
/**
 * Modules Configuration
 * 
 * This file contains the modules structure that matches the backend PermissionController.
 * When new models are added to the backend, they should be added here as well.
 * 
 * This is the single source of truth for modules and their actions in the frontend.
 * 
 * Structure: { moduleName: { action: 'Display Label' } }
 */
export const Modules = {
    'products': {
        'view': 'View Products',
        'create': 'Create Products',
        'update': 'Edit Products',
        'delete': 'Delete Products',
    },
    'categories': {
        'view': 'View Categories',
        'create': 'Create Categories',
        'update': 'Edit Categories',
        'delete': 'Delete Categories',
    },
    'users': {
        'view': 'View Users',
        'create': 'Create Users',
        'update': 'Edit Users',
        'delete': 'Delete Users',
    },
    'permissions': {
        'view': 'View Permissions',
        'create': 'Create Permissions',
        'update': 'Edit Permissions',
        'delete': 'Delete Permissions',
        'manage': 'Manage Permissions',
    },
    'dashboard': {
        'view': 'View Dashboard',
        'statistics': 'View Statistics',
    },
    'orders': {
        'view': 'View Orders',
        'create': 'Create Orders',
        'update': 'Edit Orders',
        'delete': 'Delete Orders',
    },
     'account': {
        'view': 'View Orders',
        'create': 'Create Orders',
        'update': 'Edit Orders',
        'delete': 'Delete Orders',
    },
};
