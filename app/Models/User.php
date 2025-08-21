<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Helper\UserTokenTraits;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, UserTokenTraits;

    protected $fillable = [
        'name', 'email', 'mobile', 'password', 'role', 'is_registered', 'is_active', 'is_admin'
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
    ];

    public function userToken()
    {
        return $this->hasOne(UserToken::class);
    }
}