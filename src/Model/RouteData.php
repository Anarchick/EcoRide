<?php

namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\UX\Map\Point;

/**
 * Immutable Data Transfer Object for route information
 * Contains OSRM route data ready for use with Symfony UX Map
 */
readonly class RouteData
{

    public function __construct(
        #[Assert\NotBlank()]
        #[Assert\Count(
            min: 2
        )]
        #[Assert\All([
            new Assert\Type(
                type: Point::class
            )
        ])]
        public array $points,
        
        #[Assert\NotBlank()]
        #[Assert\Positive()]
        #[Assert\Range(
            min: 0.1,
            max: 10000
        )]
        public float $distanceKm,
        
        #[Assert\NotBlank()]
        #[Assert\Positive()]
        #[Assert\Range(
            min: 1,
            max: 10080
        )]
        public int $durationMinutes
    ) {
        $this->validate();
    }

    public function getPoints(): array
    {
        return $this->points;
    }

    public function getDistanceKm(): float
    {
        return $this->distanceKm;
    }

    public function getDurationMinutes(): int
    {
        return $this->durationMinutes;
    }

    /**
     * Validate route data integrity
     * 
     * @throws \InvalidArgumentException If validation fails
     */
    private function validate(): void
    {
        if (empty($this->points)) {
            throw new \InvalidArgumentException('Route must contain at least one point');
        }

        foreach ($this->points as $index => $point) {
            if (!$point instanceof Point) {
                throw new \InvalidArgumentException(
                    sprintf('Point at index %d must be instance of Symfony\UX\Map\Point', $index)
                );
            }
        }

        if ($this->distanceKm < 0) {
            throw new \InvalidArgumentException('Distance cannot be negative');
        }

        if ($this->durationMinutes < 0) {
            throw new \InvalidArgumentException('Duration cannot be negative');
        }

        // Check: minimum 2 points for a valid route
        if (count($this->points) < 2) {
            throw new \InvalidArgumentException('Route must contain at least 2 points (start and end)');
        }
    }

    /**
     * Get formatted distance for display (e.g., "392.5 km")
     */
    public function getFormattedDistance(): string
    {
        return number_format($this->distanceKm, 1, ',', ' ') . ' km';
    }

    /**
     * Get formatted duration for display (e.g., "4h 30min")
     */
    public function getFormattedDuration(): string
    {
        $hours = floor($this->durationMinutes / 60);
        $minutes = $this->durationMinutes % 60;

        if ($hours > 0) {
            return sprintf('%dh %02dmin', $hours, $minutes);
        }

        return sprintf('%dmin', $minutes);
    }

    public function getPointCount(): int
    {
        return count($this->points);
    }

    public function getStartPoint(): Point
    {
        return $this->points[0];
    }

    public function getEndPoint(): Point
    {
        return $this->points[array_key_last($this->points)];
    }

    /**
     * Convert to array format suitable for RouteCache
     * 
     * @return array{geometry: array<int, array{lat: float, lon: float}>, distanceKm: float, durationMinutes: int}
     */
    public function toArray(): array
    {
        $geometry = array_map(
            fn(Point $point) => [
                'lat' => $point->latitude,
                'lon' => $point->longitude
            ],
            $this->points
        );

        return [
            'geometry' => $geometry,
            'distanceKm' => $this->distanceKm,
            'durationMinutes' => $this->durationMinutes,
        ];
    }

    /**
     * Create RouteData from RouteCache geometry array
     */
    public static function fromCacheGeometry(array $geometry, float $distanceKm, int $durationMinutes): self
    {
        $points = array_map(
            fn(array $coord) => new Point($coord['lat'], $coord['lon']),
            $geometry
        );

        return new self($points, $distanceKm, $durationMinutes);
    }
}
