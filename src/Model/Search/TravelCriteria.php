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
    public int $passengersMin = 1;
    #[Assert\PositiveOrZero()]
    public float $maxCost = PHP_INT_MAX;
    public bool $isSmokingAllowed = false;
    public bool $isPetsAllowed = false;
    public LuggageSizeEnum $luggageSizeMin = LuggageSizeEnum::NONE;

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

    public function getPassengersMin(): int
    {
        return $this->passengersMin;
    }

    public function getMaxCost(): float
    {
        return $this->maxCost;
    }

    public function isSmokingAllowed(): bool
    {
        return $this->isSmokingAllowed;
    }

    public function isPetsAllowed(): bool
    {
        return $this->isPetsAllowed;
    }

    public function getLuggageSizeMin(): LuggageSizeEnum
    {
        return $this->luggageSizeMin;
    }
}