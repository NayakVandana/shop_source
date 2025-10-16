<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AdminRole extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'is_active', 'is_system', 'sort_order', 'uuid'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($role) {
            if (empty($role->uuid)) {
                $role->uuid = Str::uuid();
            }
            if (empty($role->slug)) {
                $role->slug = Str::slug($role->name);
            }
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(AdminPermission::class, 'admin_role_permissions');
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    /**
     * Check if role has permission
     */
    public function hasPermission($permission)
    {
        if ($this->is_system && $this->slug === 'super-admin') {
            return true;
        }

        // Check role permissions through many-to-many relationship
        return $this->permissions()->where('slug', $permission)->exists();
    }

    /**
     * Check if role has any of the given permissions
     */
    public function hasAnyPermission($permissions)
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if role has all of the given permissions
     */
    public function hasAllPermissions($permissions)
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get default admin roles
     */
    public static function getDefaultRoles()
    {
        return [
            [
                'name' => 'Super Admin',
                'slug' => 'super-admin',
                'description' => 'Full access to all features and settings',
                'is_system' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Product Manager',
                'slug' => 'product-manager',
                'description' => 'Manage products, categories, and inventory',
                'is_system' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Order Manager',
                'slug' => 'order-manager',
                'description' => 'Manage orders, deliveries, and customer service',
                'is_system' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Content Manager',
                'slug' => 'content-manager',
                'description' => 'Manage content, discounts, and promotions',
                'is_system' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Viewer',
                'slug' => 'viewer',
                'description' => 'View-only access to most features',
                'is_system' => true,
                'sort_order' => 5,
            ],
        ];
    }

    /**
     * Get role permissions as array
     */
    public function getPermissionsArrayAttribute()
    {
        return $this->permissions()->pluck('slug')->toArray();
    }
}