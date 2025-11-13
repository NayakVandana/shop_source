<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Traits\UserTokenTraits;
use App\Enums\UserRole;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use Notifiable, UserTokenTraits;

    protected $fillable = [
        'name', 'email', 'mobile', 'password', 'role', 'is_registered', 'is_active', 'is_admin', 'uuid',
        'last_login_at', 'last_login_ip'
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

    public function getRouteKeyName()
    {
        return 'uuid';
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
     * Check if user is admin
     */
    public function isAdmin()
    {
        return $this->is_admin || $this->role === 'admin';
    }

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin()
    {
        return $this->is_admin && $this->role === 'super_admin';
    }

    /**
     * Get permissions assigned directly to this user
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')
                    ->withTimestamps();
    }

    /**
     * Get permissions assigned to user's role
     */
    public function rolePermissions()
    {
        if (!$this->role) {
            return collect([]);
        }

        return Permission::whereExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('role_permissions')
                ->whereColumn('role_permissions.permission_id', 'permissions.id')
                ->where('role_permissions.role', $this->role);
        })->get();
    }

    /**
     * Get all permissions for this user (both direct and role-based)
     * Super admin has all permissions by default
     */
    public function getAllPermissions()
    {
        // Super admin has all permissions
        if ($this->isSuperAdmin()) {
            return Permission::all();
        }

        // Get direct user permissions
        $userPermissions = $this->permissions;

        // Get role-based permissions
        $rolePermissions = $this->rolePermissions();

        // Merge and return unique permissions
        return $userPermissions->merge($rolePermissions)->unique('id');
    }

    /**
     * Check if user has a specific permission
     * 
     * IMPORTANT: Super admin ALWAYS has all permissions by default, regardless of database assignments.
     * Other roles get permissions from:
     * 1. Direct user permissions (user_permissions table)
     * 2. Role-based permissions (role_permissions table)
     * 
     * @param string $permission Permission slug or name
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        // Super admin has ALL permissions by default - no database check needed
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Check by slug
        $permissionModel = Permission::where('slug', $permission)
            ->orWhere('name', $permission)
            ->first();

        if (!$permissionModel) {
            return false;
        }

        // Check direct user permissions
        if ($this->permissions()->where('permissions.id', $permissionModel->id)->exists()) {
            return true;
        }

        // Check role permissions
        if ($this->role) {
            return \DB::table('role_permissions')
                ->where('permission_id', $permissionModel->id)
                ->where('role', $this->role)
                ->exists();
        }

        return false;
    }

    /**
     * Check if user has any of the given permissions
     */
    public function hasAnyPermission(array $permissions): bool
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
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Assign permission to user
     */
    public function assignPermission($permission)
    {
        if (is_string($permission)) {
            $permission = Permission::where('slug', $permission)
                ->orWhere('name', $permission)
                ->firstOrFail();
        }

        if (!$this->permissions()->where('permissions.id', $permission->id)->exists()) {
            $this->permissions()->attach($permission->id);
        }
    }

    /**
     * Remove permission from user
     */
    public function removePermission($permission)
    {
        if (is_string($permission)) {
            $permission = Permission::where('slug', $permission)
                ->orWhere('name', $permission)
                ->firstOrFail();
        }

        $this->permissions()->detach($permission->id);
    }
}