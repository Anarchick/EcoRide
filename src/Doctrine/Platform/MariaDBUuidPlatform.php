<?php
namespace App\Doctrine\Platform;

use Doctrine\DBAL\Platforms\MariaDb1010Platform;
use Doctrine\DBAL\Types\Type;

/**
 * Force the use of the native UUID type of MariaDB
 */
class MariaDBUuidPlatform extends MariaDb1010Platform
{
    public function hasNativeGuidType(): bool
    {
        return true;
    }

    public function getGuidTypeDeclarationSQL(array $column): string
    {
        return 'UUID'; 
    }

    protected function initializeDoctrineTypeMappings(): void
    {
        parent::initializeDoctrineTypeMappings();
        $this->doctrineTypeMapping['uuid'] = 'uuid';
    }

    public function hasDoctrineTypeMappingFor($dbType): bool
    {
        return isset($this->doctrineTypeMapping[strtolower($dbType)]) || parent::hasDoctrineTypeMappingFor($dbType);
    }

    public function getDoctrineTypeMapping($dbType): string
    {
        if (strtolower($dbType) === 'uuid') {
            return 'uuid';
        }
        return parent::getDoctrineTypeMapping($dbType);
    }
}