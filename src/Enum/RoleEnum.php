<?php
namespace App\Enum;

use App\Entity\Role;
use App\Entity\User;

enum RoleEnum: string
{
    case USER = 'ROLE_USER';
    case DRIVER = 'ROLE_DRIVER';
    case MODERATOR = 'ROLE_MODERATOR';
    case ADMIN = 'ROLE_ADMIN';
    case BANNED = 'ROLE_BANNED';

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

    public function toEntity(User $user): Role
    {
        $role = new Role();
        $role->setRole($this);
        $role->setUser($user);
        return $role;
    }
}