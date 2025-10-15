<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class AdminPermission extends Model
{
    protected $fillable = [
        'name', 'slug', 'module', 'action', 'description', 'is_active', 'is_system', 'uuid'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($permission) {
            if (empty($permission->uuid)) {
                $permission->uuid = Str::uuid();
            }
            if (empty($permission->slug)) {
                $permission->slug = Str::slug($permission->name);
            }
        });
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(AdminRole::class, 'admin_role_permissions');
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    /**
     * Get default admin permissions
     */
    public static function getDefaultPermissions()
    {
        return [
            // Product permissions
            ['name' => 'Products List', 'slug' => 'products.list', 'module' => 'products', 'action' => 'list'],
            ['name' => 'Products Create', 'slug' => 'products.create', 'module' => 'products', 'action' => 'create'],
            ['name' => 'Products Update', 'slug' => 'products.update', 'module' => 'products', 'action' => 'update'],
            ['name' => 'Products Delete', 'slug' => 'products.delete', 'module' => 'products', 'action' => 'delete'],
            ['name' => 'Products View', 'slug' => 'products.view', 'module' => 'products', 'action' => 'view'],

            // Category permissions
            ['name' => 'Categories List', 'slug' => 'categories.list', 'module' => 'categories', 'action' => 'list'],
            ['name' => 'Categories Create', 'slug' => 'categories.create', 'module' => 'categories', 'action' => 'create'],
            ['name' => 'Categories Update', 'slug' => 'categories.update', 'module' => 'categories', 'action' => 'update'],
            ['name' => 'Categories Delete', 'slug' => 'categories.delete', 'module' => 'categories', 'action' => 'delete'],

            // Order permissions
            ['name' => 'Orders List', 'slug' => 'orders.list', 'module' => 'orders', 'action' => 'list'],
            ['name' => 'Orders View', 'slug' => 'orders.view', 'module' => 'orders', 'action' => 'view'],
            ['name' => 'Orders Update', 'slug' => 'orders.update', 'module' => 'orders', 'action' => 'update'],
            ['name' => 'Orders Delete', 'slug' => 'orders.delete', 'module' => 'orders', 'action' => 'delete'],
            ['name' => 'Orders Ship', 'slug' => 'orders.ship', 'module' => 'orders', 'action' => 'ship'],
            ['name' => 'Orders Cancel', 'slug' => 'orders.cancel', 'module' => 'orders', 'action' => 'cancel'],

            // User permissions
            ['name' => 'Users List', 'slug' => 'users.list', 'module' => 'users', 'action' => 'list'],
            ['name' => 'Users Create', 'slug' => 'users.create', 'module' => 'users', 'action' => 'create'],
            ['name' => 'Users Update', 'slug' => 'users.update', 'module' => 'users', 'action' => 'update'],
            ['name' => 'Users Delete', 'slug' => 'users.delete', 'module' => 'users', 'action' => 'delete'],

            // Discount permissions
            ['name' => 'Discounts List', 'slug' => 'discounts.list', 'module' => 'discounts', 'action' => 'list'],
            ['name' => 'Discounts Create', 'slug' => 'discounts.create', 'module' => 'discounts', 'action' => 'create'],
            ['name' => 'Discounts Update', 'slug' => 'discounts.update', 'module' => 'discounts', 'action' => 'update'],
            ['name' => 'Discounts Delete', 'slug' => 'discounts.delete', 'module' => 'discounts', 'action' => 'delete'],

            // Coupon permissions
            ['name' => 'Coupons List', 'slug' => 'coupons.list', 'module' => 'coupons', 'action' => 'list'],
            ['name' => 'Coupons Create', 'slug' => 'coupons.create', 'module' => 'coupons', 'action' => 'create'],
            ['name' => 'Coupons Update', 'slug' => 'coupons.update', 'module' => 'coupons', 'action' => 'update'],
            ['name' => 'Coupons Delete', 'slug' => 'coupons.delete', 'module' => 'coupons', 'action' => 'delete'],

            // Location permissions
            ['name' => 'Locations List', 'slug' => 'locations.list', 'module' => 'locations', 'action' => 'list'],
            ['name' => 'Locations Create', 'slug' => 'locations.create', 'module' => 'locations', 'action' => 'create'],
            ['name' => 'Locations Update', 'slug' => 'locations.update', 'module' => 'locations', 'action' => 'update'],
            ['name' => 'Locations Delete', 'slug' => 'locations.delete', 'module' => 'locations', 'action' => 'delete'],

            // Delivery permissions
            ['name' => 'Delivery Manage', 'slug' => 'delivery.manage', 'module' => 'delivery', 'action' => 'manage'],
            ['name' => 'Delivery Schedule', 'slug' => 'delivery.schedule', 'module' => 'delivery', 'action' => 'schedule'],
            ['name' => 'Delivery Cancel', 'slug' => 'delivery.cancel', 'module' => 'delivery', 'action' => 'cancel'],

            // Return permissions
            ['name' => 'Returns List', 'slug' => 'returns.list', 'module' => 'returns', 'action' => 'list'],
            ['name' => 'Returns Process', 'slug' => 'returns.process', 'module' => 'returns', 'action' => 'process'],
            ['name' => 'Returns Approve', 'slug' => 'returns.approve', 'module' => 'returns', 'action' => 'approve'],

            // System permissions
            ['name' => 'System Settings', 'slug' => 'system.settings', 'module' => 'system', 'action' => 'settings'],
            ['name' => 'Admin Roles', 'slug' => 'admin.roles', 'module' => 'admin', 'action' => 'roles'],
            ['name' => 'Admin Permissions', 'slug' => 'admin.permissions', 'module' => 'admin', 'action' => 'permissions'],
            ['name' => 'Reports View', 'slug' => 'reports.view', 'module' => 'reports', 'action' => 'view'],
        ];
    }
}