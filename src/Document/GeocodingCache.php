<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use App\Repository\GeocodingCacheRepository;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * MongoDB document for caching geocoding results
 * Stores city name to GPS coordinates mapping with TTL expiration
 */
#[MongoDB\Document(db: 'ecoride', repositoryClass: GeocodingCacheRepository::class, collection: 'geocoding_cache')]
#[MongoDB\Index(keys: ['cityName' => 1], options: ['unique' => true])]
#[MongoDB\Index(keys: ['expiresAt' => 1], options: ['expireAfterSeconds' => 0])]
class GeocodingCache
{
    #[MongoDB\Id]
    private string $id;

    #[MongoDB\Field(type: 'string')]
    #[Assert\NotBlank()]
    #[Assert\Length(
        min: 2,
        max: 100
    )]
    private string $cityName;

    #[MongoDB\Field(type: 'float')]
    #[Assert\NotBlank()]
    #[Assert\Range(
        min: -90,
        max: 90
    )]
    private float $latitude;

    #[MongoDB\Field(type: 'float')]
    #[Assert\NotBlank()]
    #[Assert\Range(
        min: -180,
        max: 180
    )]
    private float $longitude;

    #[MongoDB\Field(type: 'date')]
    private \DateTimeInterface $createdAt;

    #[MongoDB\Field(type: 'date')]
    private \DateTimeInterface $expiresAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCityName(): string
    {
        return $this->cityName;
    }

    public function setCityName(string $cityName): static
    {
        $this->cityName = $cityName;
        return $this;
    }

    public function getLatitude(): float
    {
        return $this->latitude;
    }

    public function setLatitude(float $latitude): static
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): float
    {
        return $this->longitude;
    }

    public function setLongitude(float $longitude): static
    {
        $this->longitude = $longitude;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getExpiresAt(): \DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeInterface $expiresAt): static
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }
}