<?php
namespace App\Doctrine\Platform;

use Doctrine\DBAL\Platforms\MariaDBPlatform;

/**
 * Force the use of the native UUID type of MariaDB
 */
class MariaDBUuidPlatform extends MariaDBPlatform
{
    public function hasNativeGuidType(): bool
    {
        return true;
    }

    public function getGuidTypeDeclarationSQL(array $column): string
    {
        return 'UUID'; 
    }
}