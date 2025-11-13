<?php

namespace App\Enums;

enum UserRole: string
{
    case USER = 'user';
    case ADMIN = 'admin';
    case SUPER_ADMIN = 'super_admin';
    case SALES = 'sales';
    case MARKETING = 'marketing';
    case TESTER = 'tester';
    case DEVELOPER = 'developer';

    /**
     * Get all role values as an array
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all role names as an array
     *
     * @return array<string>
     */
    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    /**
     * Check if the role is an admin role
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return in_array($this, [self::ADMIN, self::SUPER_ADMIN]);
    }

    /**
     * Check if the role is super admin
     *
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        return $this === self::SUPER_ADMIN;
    }

    /**
     * Get human-readable label for the role
     *
     * @return string
     */
    public function label(): string
    {
        return match($this) {
            self::USER => 'User',
            self::ADMIN => 'Admin',
            self::SUPER_ADMIN => 'Super Admin',
            self::SALES => 'Sales',
            self::MARKETING => 'Marketing',
            self::TESTER => 'Tester',
            self::DEVELOPER => 'Developer',
        };
    }
}

