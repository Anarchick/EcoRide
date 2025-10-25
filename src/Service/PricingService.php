<?php
declare(strict_types=1);

namespace App\Service;

use App\Enum\FuelTypeEnum;

class PricingService
{
    private int $estimatedPrice;

    public function __construct(
        private readonly int $distanceKm,
        private readonly FuelTypeEnum $fuelType
    ) {
        $this->estimatedPrice = $this->estimatePrice();
    }

    private function estimatePrice(): int
    {
        return (int) floor($this->distanceKm * $this->getEcoScore($this->fuelType) * 0.5);
    }

    public function getEstimatedPrice(): int
    {
        return $this->estimatedPrice;
    }

    public function getMin(): int
    {
        return (int) floor($this->getEstimatedPrice() * 0.75);
    }

    public function getMax(): int
    {
        return (int) ceil($this->getEstimatedPrice() * 1.25);
    }

    public function getStep(): int
    {
        $estimatedPrice = $this->getEstimatedPrice();

        return match (true) {
            $estimatedPrice < 20 => 1,
            $estimatedPrice < 100 => 5,
            $estimatedPrice < 500 => 10,
            default => 50,
        };
    }

    private function getEcoScore(FuelTypeEnum $fuelType): float
    {
        return match($fuelType) {
            FuelTypeEnum::ELECTRIC, FuelTypeEnum::HYDROGEN => 1,
            FuelTypeEnum::HYBRID => 1.2,
            FuelTypeEnum::BIO_DIESEL, FuelTypeEnum::BIO_ETHANOL => 1.3,
            FuelTypeEnum::GAZ => 1.4,
            FuelTypeEnum::PETROL, FuelTypeEnum::DIESEL => 1.5,
        };
    }
}