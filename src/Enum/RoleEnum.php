<?php
namespace App\Enum;

enum RoleEnum: string
{
    case USER = 'user';
    case DRIVER = 'driver';
    case MODERATOR = 'moderator';
    case ADMIN = 'admin';

    /**
     * Check if the role is a user or driver.
     */
    public function isUser(): bool
    {
        return $this === self::USER || $this === self::DRIVER;
    }

    /**
     * Check if the role is a moderator or admin.
     */
    public function isEmployee(): bool
    {
        return $this === self::MODERATOR || $this === self::ADMIN;
    }
}