<?php

namespace App\Model\Search;

use App\Enum\LuggageSizeEnum;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Validator\Constraints as Assert;

class TravelCriteria
{
    #[Assert\Length(max: 45)]
    #[Assert\Regex(pattern: '/^[a-zA-ZÀ-ÿ0-9 \'-]+$/', message: 'Nom de ville invalide')]
    public ?string $departure;
    #[Assert\Length(max: 45)]
    #[Assert\Regex(pattern: '/^[a-zA-ZÀ-ÿ0-9 \'-]+$/', message: 'Nom de ville invalide')]
    public ?string $arrival;
    #[Assert\Range(
        min: 'today',
        max: '+1 month',
        notInRangeMessage: 'La date doit être comprise entre aujourd\'hui et dans un mois.'
    )]
    public ?\DateTimeInterface $date;
    #[Assert\Range(min: 1, max: 10, notInRangeMessage: 'Le nombre de passagers doit être entre {{ min }} et {{ max }}.')]
    public ?int $minPassengers;
    #[Assert\PositiveOrZero()]
    public ?int $maxCost;
    #[Assert\PositiveOrZero()]
    public ?int $maxDuration;
    #[Assert\Range(min: 0, max: 5, notInRangeMessage: 'Le score doit être entre {{ min }} et {{ max }}.')]
    public ?int $minScore;
    public ?bool $isElectricPreferred;
    public ?bool $isSmokingAllowed;
    public ?bool $isPetsAllowed;
    public ?LuggageSizeEnum $luggageSizeMin = LuggageSizeEnum::NONE;

    public function getDeparture(): string
    {
        return (new AsciiSlugger())->slug($this->departure);
    }

    public function getArrival(): string
    {
        return (new AsciiSlugger())->slug($this->arrival);
    }

    /**
     * Get the date and time of the travel.
     * If the travel date is in the past, return the current date and time.
     */
    public function getDateTime(): \DateTimeInterface
    {
        $now = new \DateTimeImmutable();

        if ($this->date < $now) {
            return $now;
        }

        return $this->date;
    }

    public function getMinPassengers(): int
    {
        return $this->minPassengers ?? 1;
    }

    // Get max duration in hours
    public function getMaxDuration(): int
    {
        return $this->maxDuration ?? PHP_INT_MAX;
    }

    public function getMinScore(): int
    {
        return $this->minScore ?? 0;
    }

    public function getMaxCost(): int
    {
        return $this->maxCost ?? PHP_INT_MAX;
    }

    public function isElectricPreferred(): bool
    {
        return $this->isElectricPreferred ?? false;
    }

    public function isSmokingAllowed(): bool
    {
        return $this->isSmokingAllowed ?? false;
    }

    public function isPetsAllowed(): bool
    {
        return $this->isPetsAllowed ?? false;
    }

    public function getLuggageSizeMin(): LuggageSizeEnum
    {
        return $this->luggageSizeMin ?? LuggageSizeEnum::NONE;
    }
    
}