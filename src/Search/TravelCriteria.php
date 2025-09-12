<?php

namespace App\Search;

use App\Enum\LuggageSizeEnum;
use Symfony\Component\Validator\Constraints as Assert;

class TravelCriteria
{
    #[Assert\Length(max: 45)]
    #[Assert\Regex(pattern: '/^[a-zA-ZÀ-ÿ0-9 \'-]+$/', message: 'Nom de ville invalide')]
    private string $departure;
    #[Assert\Length(max: 45)]
    #[Assert\Regex(pattern: '/^[a-zA-ZÀ-ÿ0-9 \'-]+$/', message: 'Nom de ville invalide')]
    private string $arrival;
    private \DateTimeInterface $date;
    #[Assert\Range(min: 1, max: 10, notInRangeMessage: 'Le nombre de passagers doit être entre {{ min }} et {{ max }}.')]
    private int $passengersMin;
    #[Assert\PositiveOrZero()]
    private float $maxCost;
    private bool $isSmokingAllowed;
    private bool $isPetsAllowed;
    private LuggageSizeEnum $luggageSizeMin;

    public function __construct(
        // Required
        string $departure,
        string $arrival,
        \DateTimeInterface $date,
        int $passengersMin = 1,
        // filters
        float $maxCost = PHP_INT_MAX,
        bool $isSmokingAllowed = false,
        bool $isPetsAllowed = false,
        LuggageSizeEnum $luggageSizeMin = LuggageSizeEnum::NONE
    )
    {
        // TODO: sanitize strings (trim, remove extra spaces, etc.)
        // TODO: validate inputs
        $this->departure = $departure;
        $this->arrival = $arrival;
        $this->date = $date;
        $this->passengersMin = $passengersMin;
        $this->maxCost = $maxCost;
        $this->isSmokingAllowed = $isSmokingAllowed;
        $this->isPetsAllowed = $isPetsAllowed;
        $this->luggageSizeMin = $luggageSizeMin;
    }

    public function getDeparture(): string
    {
        return $this->departure;
    }

    public function getArrival(): string
    {
        return $this->arrival;
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