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
}
