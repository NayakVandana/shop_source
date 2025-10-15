<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Helper\UserTokenTraits;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, UserTokenTraits;

    protected $fillable = [
        'name', 'email', 'mobile', 'password', 'role', 'is_registered', 'is_active', 'is_admin', 'uuid',
        'admin_role_id', 'permissions', 'is_super_admin', 'last_login_at', 'last_login_ip'
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_registered' => 'boolean',
        'is_active' => 'boolean',
        'is_admin' => 'boolean',
        'permissions' => 'array',
        'is_super_admin' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($user) {
            if (empty($user->uuid)) {
                $user->uuid = Str::uuid();
            }
        });
    }

    public function userToken()
    {
        return $this->hasOne(UserToken::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function adminRole()
    {
        return $this->belongsTo(AdminRole::class);
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin()
    {
        return $this->is_super_admin || $this->adminRole?->slug === 'super-admin';
    }

    /**
     * Check if user has permission
     */
    public function hasPermission($permission)
    {
        // Super admin has all permissions
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Check custom permissions
        if ($this->permissions && in_array($permission, $this->permissions)) {
            return true;
        }

        // Check role permissions
        if ($this->adminRole) {
            return $this->adminRole->hasPermission($permission);
        }

        return false;
    }

    /**
     * Check if user has any of the given permissions
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
     * Check if user has all of the given permissions
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
     * Check if user can access module
     */
    public function canAccessModule($module)
    {
        $permissions = [
            'products' => ['products.list', 'products.view'],
            'orders' => ['orders.list', 'orders.view'],
            'users' => ['users.list', 'users.view'],
            'categories' => ['categories.list', 'categories.view'],
            'discounts' => ['discounts.list', 'discounts.view'],
            'coupons' => ['coupons.list', 'coupons.view'],
            'locations' => ['locations.list', 'locations.view'],
            'delivery' => ['delivery.manage'],
            'returns' => ['returns.list', 'returns.view'],
            'reports' => ['reports.view'],
            'admin' => ['admin.roles', 'admin.permissions'],
        ];

        if (!isset($permissions[$module])) {
            return false;
        }

        return $this->hasAnyPermission($permissions[$module]);
    }

    /**
     * Get user permissions
     */
    public function getPermissions()
    {
        $permissions = $this->permissions ?? [];

        if ($this->adminRole) {
            $rolePermissions = $this->adminRole->permissions_array;
            $permissions = array_merge($permissions, $rolePermissions);
        }

        return array_unique($permissions);
    }

    /**
     * Update last login
     */
    public function updateLastLogin($ip = null)
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip ?? request()->ip(),
        ]);
    }

    /**
     * Check if user can perform action on resource
     */
    public function canPerformAction($action, $resource = null)
    {
        $permission = $resource ? "{$resource}.{$action}" : $action;
        return $this->hasPermission($permission);
    }
}