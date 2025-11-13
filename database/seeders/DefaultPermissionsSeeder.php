<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Enums\UserRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DefaultPermissionsSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if permissions already exist
        if (Permission::count() > 0) {
            $this->command->info('Permissions table already has data. Skipping DefaultPermissionsSeeder.');
            return;
        }

        // Define default permissions
        $permissions = [
            // Products
            ['name' => 'View Products', 'module' => 'products', 'action' => 'view', 'description' => 'Can view products list'],
            ['name' => 'Create Products', 'module' => 'products', 'action' => 'create', 'description' => 'Can create new products'],
            ['name' => 'Edit Products', 'module' => 'products', 'action' => 'update', 'description' => 'Can edit existing products'],
            ['name' => 'Delete Products', 'module' => 'products', 'action' => 'delete', 'description' => 'Can delete products'],
            
            // Categories
            ['name' => 'View Categories', 'module' => 'categories', 'action' => 'view', 'description' => 'Can view categories list'],
            ['name' => 'Create Categories', 'module' => 'categories', 'action' => 'create', 'description' => 'Can create new categories'],
            ['name' => 'Edit Categories', 'module' => 'categories', 'action' => 'update', 'description' => 'Can edit existing categories'],
            ['name' => 'Delete Categories', 'module' => 'categories', 'action' => 'delete', 'description' => 'Can delete categories'],
            
            // Users
            ['name' => 'View Users', 'module' => 'users', 'action' => 'view', 'description' => 'Can view users list'],
            ['name' => 'Create Users', 'module' => 'users', 'action' => 'create', 'description' => 'Can create new users'],
            ['name' => 'Edit Users', 'module' => 'users', 'action' => 'update', 'description' => 'Can edit existing users'],
            ['name' => 'Delete Users', 'module' => 'users', 'action' => 'delete', 'description' => 'Can delete users'],
            
            // Permissions
            ['name' => 'View Permissions', 'module' => 'permissions', 'action' => 'view', 'description' => 'Can view permissions list'],
            ['name' => 'Create Permissions', 'module' => 'permissions', 'action' => 'create', 'description' => 'Can create new permissions'],
            ['name' => 'Edit Permissions', 'module' => 'permissions', 'action' => 'update', 'description' => 'Can edit existing permissions'],
            ['name' => 'Delete Permissions', 'module' => 'permissions', 'action' => 'delete', 'description' => 'Can delete permissions'],
            ['name' => 'Manage Permissions', 'module' => 'permissions', 'action' => 'manage', 'description' => 'Can manage all permissions'],
            
            // Dashboard
            ['name' => 'View Dashboard', 'module' => 'dashboard', 'action' => 'view', 'description' => 'Can view admin dashboard'],
            ['name' => 'View Statistics', 'module' => 'dashboard', 'action' => 'statistics', 'description' => 'Can view dashboard statistics'],
            
            // Orders
            ['name' => 'View Orders', 'module' => 'orders', 'action' => 'view', 'description' => 'Can view orders list'],
            ['name' => 'Create Orders', 'module' => 'orders', 'action' => 'create', 'description' => 'Can create new orders'],
            ['name' => 'Edit Orders', 'module' => 'orders', 'action' => 'update', 'description' => 'Can edit existing orders'],
            ['name' => 'Delete Orders', 'module' => 'orders', 'action' => 'delete', 'description' => 'Can delete orders'],
        ];

        // Create permissions
        $createdPermissions = [];
        foreach ($permissions as $permissionData) {
            // Ensure slug is generated if not provided
            if (empty($permissionData['slug'])) {
                $permissionData['slug'] = \Illuminate\Support\Str::slug($permissionData['name']);
            }
            $permission = Permission::create($permissionData);
            $createdPermissions[] = $permission;
        }

        // Assign all permissions to super_admin role (super admin has all permissions by default)
        $superAdminRole = UserRole::SUPER_ADMIN->value;
        foreach ($createdPermissions as $permission) {
            $permission->assignToRole($superAdminRole);
        }

        // Assign permissions to admin role (most permissions except user management)
        $adminRole = UserRole::ADMIN->value;
        $adminPermissions = array_filter($createdPermissions, function($permission) {
            return !in_array($permission->module, ['users', 'permissions']);
        });
        foreach ($adminPermissions as $permission) {
            $permission->assignToRole($adminRole);
        }

        // Assign view permissions to sales role
        $salesRole = UserRole::SALES->value;
        $salesPermissions = array_filter($createdPermissions, function($permission) {
            return in_array($permission->action, ['view']) && 
                   in_array($permission->module, ['products', 'categories', 'orders', 'dashboard']);
        });
        foreach ($salesPermissions as $permission) {
            $permission->assignToRole($salesRole);
        }
        // Sales can also create and update orders
        $salesCreateUpdate = array_filter($createdPermissions, function($permission) {
            return $permission->module === 'orders' && in_array($permission->action, ['create', 'update']);
        });
        foreach ($salesCreateUpdate as $permission) {
            $permission->assignToRole($salesRole);
        }

        // Assign view permissions to marketing role
        $marketingRole = UserRole::MARKETING->value;
        $marketingPermissions = array_filter($createdPermissions, function($permission) {
            return in_array($permission->action, ['view']) && 
                   in_array($permission->module, ['products', 'categories', 'dashboard']);
        });
        foreach ($marketingPermissions as $permission) {
            $permission->assignToRole($marketingRole);
        }
        // Marketing can also create and update products/categories
        $marketingCreateUpdate = array_filter($createdPermissions, function($permission) {
            return in_array($permission->module, ['products', 'categories']) && 
                   in_array($permission->action, ['create', 'update']);
        });
        foreach ($marketingCreateUpdate as $permission) {
            $permission->assignToRole($marketingRole);
        }

        // Assign view permissions to developer role (read-only access)
        $developerRole = UserRole::DEVELOPER->value;
        $developerPermissions = array_filter($createdPermissions, function($permission) {
            return in_array($permission->action, ['view']);
        });
        foreach ($developerPermissions as $permission) {
            $permission->assignToRole($developerRole);
        }

        // Assign view permissions to tester role (read-only access)
        $testerRole = UserRole::TESTER->value;
        $testerPermissions = array_filter($createdPermissions, function($permission) {
            return in_array($permission->action, ['view']);
        });
        foreach ($testerPermissions as $permission) {
            $permission->assignToRole($testerRole);
        }

        $this->command->info('Default permissions created and assigned to roles successfully!');
        $this->command->info('Total permissions created: ' . count($createdPermissions));
        $this->command->info('Super Admin: All permissions (' . count($createdPermissions) . ')');
        $this->command->info('Admin: ' . count($adminPermissions) . ' permissions');
        $this->command->info('Sales: ' . (count($salesPermissions) + count($salesCreateUpdate)) . ' permissions');
        $this->command->info('Marketing: ' . (count($marketingPermissions) + count($marketingCreateUpdate)) . ' permissions');
        $this->command->info('Developer: ' . count($developerPermissions) . ' permissions');
        $this->command->info('Tester: ' . count($testerPermissions) . ' permissions');
    }
}
