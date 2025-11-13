<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class Permission extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'module',
        'action',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the roles that have this permission
     */
    public function getRolesAttribute()
    {
        return DB::table('role_permissions')
            ->where('permission_id', $this->id)
            ->pluck('role')
            ->toArray();
    }

    /**
     * Get the users that have this permission
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_permissions')
                    ->withTimestamps();
    }

    /**
     * Check if a role has this permission
     */
    public function hasRole(string $role): bool
    {
        return \DB::table('role_permissions')
            ->where('permission_id', $this->id)
            ->where('role', $role)
            ->exists();
    }

    /**
     * Assign this permission to a role
     */
    public function assignToRole(string $role): void
    {
        if (!$this->hasRole($role)) {
            \DB::table('role_permissions')->insert([
                'permission_id' => $this->id,
                'role' => $role,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Remove this permission from a role
     */
    public function removeFromRole(string $role): void
    {
        \DB::table('role_permissions')
            ->where('permission_id', $this->id)
            ->where('role', $role)
            ->delete();
    }

    /**
     * Get full permission name (module.action)
     */
    public function getFullNameAttribute(): string
    {
        if ($this->module && $this->action) {
            return $this->module . '.' . $this->action;
        }
        return $this->name;
    }
}

