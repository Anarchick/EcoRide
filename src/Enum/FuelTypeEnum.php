<?php
namespace App\Enum;

enum FuelTypeEnum: string
{
    case PETROL = 'petrol';
    case DIESEL = 'diesel';
    case BIO_DIESEL = 'bio_diesel';
    case BIO_ETHANOL = 'bio_ethanol';
    case GAZ = 'gaz';
    case ELECTRIC = 'electric';
    case HYDROGEN = 'hydrogen';
    case HYBRID = 'hybrid';

    public function getEcoScore(): int
    {
        return match($this) {
            self::ELECTRIC, self::HYDROGEN => 5,
            self::HYBRID => 4,
            self::BIO_DIESEL, self::BIO_ETHANOL => 3,
            self::GAZ => 2,
            self::PETROL, self::DIESEL => 1,
        };
    }
    
}
