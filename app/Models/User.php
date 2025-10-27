<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Traits\UserTokenTraits;
use Illuminate\Support\Str;

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
}