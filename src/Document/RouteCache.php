<?php

namespace App\Document;

use App\Repository\RouteCacheRepository;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * MongoDB cache for OSRM routes
 * Stores complete polylines with distance and duration to avoid repeated API calls
 */
#[MongoDB\Document(collection: 'route_cache', repositoryClass: RouteCacheRepository::class)]
#[MongoDB\Index(keys: ['departureCity' => 'asc', 'arrivalCity' => 'asc'], options: ['unique' => true])]
#[MongoDB\Index(keys: ['expiresAt' => 'asc'], options: ['expireAfterSeconds' => 0])]
class RouteCache
{
    #[MongoDB\Id]
    private ?string $id = null;

    #[MongoDB\Field(type: 'string')]
    #[Assert\NotBlank()]
    #[Assert\Length(
        min: 2,
        max: 100
    )]
    private string $departureCity;

    #[MongoDB\Field(type: 'string')]
    #[Assert\NotBlank()]
    #[Assert\Length(
        min: 2,
        max: 100
    )]
    private string $arrivalCity;

    /**
     * @var array<int, array{lat: float, lon: float}>
     */
    #[MongoDB\Field(type: 'collection')]
    #[Assert\NotBlank(message: 'La géométrie de l\'itinéraire est obligatoire')]
    #[Assert\Count(
        min: 2,
        minMessage: 'L\'itinéraire doit contenir au moins {{ limit }} points'
    )]
    private array $geometry = [];

    #[MongoDB\Field(type: 'float')]
    #[Assert\NotBlank(message: 'La distance est obligatoire')]
    #[Assert\Positive()]
    #[Assert\Range(
        min: 0.1,
        max: 10000
    )]
    private float $distanceKm;

    #[MongoDB\Field(type: 'int')]
    #[Assert\Range(
        min: 1,
        max: 10080
    )]
    private int $durationMinutes;

    #[MongoDB\Field(type: 'date')]
    private \DateTime $createdAt;

    #[MongoDB\Field(type: 'date')]
    private \DateTime $expiresAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->expiresAt = new \DateTime('+3 months');
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getDepartureCity(): string
    {
        return $this->departureCity;
    }

    public function setDepartureCity(string $departureCity): self
    {
        $this->departureCity = strtolower(trim($departureCity));
        return $this;
    }

    public function getArrivalCity(): string
    {
        return $this->arrivalCity;
    }

    public function setArrivalCity(string $arrivalCity): self
    {
        $this->arrivalCity = strtolower(trim($arrivalCity));
        return $this;
    }

    /**
     * @return array<int, array{lat: float, lon: float}>
     */
    public function getGeometry(): array
    {
        return $this->geometry;
    }

    /**
     * @param array<int, array{lat: float, lon: float}> $geometry
     */
    public function setGeometry(array $geometry): self
    {
        $this->geometry = $geometry;
        return $this;
    }

    public function getDistanceKm(): float
    {
        return $this->distanceKm;
    }

    public function setDistanceKm(float $distanceKm): self
    {
        $this->distanceKm = $distanceKm;
        return $this;
    }

    public function getDurationMinutes(): int
    {
        return $this->durationMinutes;
    }

    public function setDurationMinutes(int $durationMinutes): self
    {
        $this->durationMinutes = $durationMinutes;
        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getExpiresAt(): \DateTime
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTime $expiresAt): self
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    // LOGICAL METHODS

    /**
     * Check if cache entry is still valid
     */
    public function isValid(): bool
    {
        return $this->expiresAt > new \DateTime();
    }
    
}
