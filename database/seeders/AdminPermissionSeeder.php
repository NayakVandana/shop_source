<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AdminRole;
use App\Models\AdminPermission;
use App\Models\User;
use Illuminate\Support\Str;

class AdminPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create permissions
        $permissions = AdminPermission::getDefaultPermissions();
        foreach ($permissions as $permissionData) {
            AdminPermission::firstOrCreate(
                ['slug' => $permissionData['slug']],
                array_merge($permissionData, ['uuid' => Str::uuid()])
            );
        }

        // Create roles
        $roles = AdminRole::getDefaultRoles();
        foreach ($roles as $roleData) {
            $role = AdminRole::firstOrCreate(
                ['slug' => $roleData['slug']],
                array_merge($roleData, ['uuid' => Str::uuid()])
            );

            // Assign permissions to role
            if ($roleData['slug'] === 'super-admin') {
                // Super admin gets all permissions
                $allPermissions = AdminPermission::all();
                $role->permissions()->sync($allPermissions->pluck('id')->toArray());
            } else {
                // Assign specific permissions based on role
                $permissionSlugs = $roleData['permissions'];
                $permissions = AdminPermission::whereIn('slug', $permissionSlugs)->get();
                $role->permissions()->sync($permissions->pluck('id')->toArray());
            }
        }

        // Create super admin user if not exists
        $superAdmin = User::where('email', 'admin@shop.com')->first();
        if (!$superAdmin) {
            $superAdmin = User::create([
                'name' => 'Super Admin',
                'email' => 'admin@shop.com',
                'password' => bcrypt('password'),
                'is_admin' => true,
                'is_super_admin' => true,
                'is_active' => true,
                'uuid' => Str::uuid(),
            ]);
        } else {
            // Update existing admin to super admin
            $superAdmin->update([
                'is_super_admin' => true,
                'admin_role_id' => AdminRole::where('slug', 'super-admin')->first()?->id,
            ]);
        }

        // Create product manager user
        $productManager = User::where('email', 'product@shop.com')->first();
        if (!$productManager) {
            $productManager = User::create([
                'name' => 'Product Manager',
                'email' => 'product@shop.com',
                'password' => bcrypt('password'),
                'is_admin' => true,
                'is_super_admin' => false,
                'is_active' => true,
                'uuid' => Str::uuid(),
                'admin_role_id' => AdminRole::where('slug', 'product-manager')->first()?->id,
            ]);
        }

        // Create order manager user
        $orderManager = User::where('email', 'order@shop.com')->first();
        if (!$orderManager) {
            $orderManager = User::create([
                'name' => 'Order Manager',
                'email' => 'order@shop.com',
                'password' => bcrypt('password'),
                'is_admin' => true,
                'is_super_admin' => false,
                'is_active' => true,
                'uuid' => Str::uuid(),
                'admin_role_id' => AdminRole::where('slug', 'order-manager')->first()?->id,
            ]);
        }

        // Create viewer user
        $viewer = User::where('email', 'viewer@shop.com')->first();
        if (!$viewer) {
            $viewer = User::create([
                'name' => 'Viewer',
                'email' => 'viewer@shop.com',
                'password' => bcrypt('password'),
                'is_admin' => true,
                'is_super_admin' => false,
                'is_active' => true,
                'uuid' => Str::uuid(),
                'admin_role_id' => AdminRole::where('slug', 'viewer')->first()?->id,
            ]);
        }

        $this->command->info('Admin roles and permissions seeded successfully!');
        $this->command->info('Super Admin: admin@shop.com / password');
        $this->command->info('Product Manager: product@shop.com / password');
        $this->command->info('Order Manager: order@shop.com / password');
        $this->command->info('Viewer: viewer@shop.com / password');
    }
}