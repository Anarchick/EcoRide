<?php

namespace App\Model;

/**
 * Data Transfer Object for geolocation coordinates
 * Encapsulates latitude and longitude values
 */
readonly class GeolocationData
{
    public function __construct(
        public float $latitude,
        public float $longitude
    ) {
        if ($latitude < -90 || $latitude > 90) {
            throw new \InvalidArgumentException(
                sprintf('Invalid latitude value: %f. Must be between -90 and 90.', $latitude)
            );
        }

        if ($longitude < -180 || $longitude > 180) {
            throw new \InvalidArgumentException(
                sprintf('Invalid longitude value: %f. Must be between -180 and 180.', $longitude)
            );
        }
    }

    /**
     * Create from array with 'lat' and 'lon' keys
     * 
     * @param array{lat: float, lon: float} $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['lat']) || !isset($data['lon'])) {
            throw new \InvalidArgumentException(
                'Array must contain both "lat" and "lon" keys'
            );
        }

        return new self(
            latitude: (float) $data['lat'],
            longitude: (float) $data['lon']
        );
    }

    /**
     * @return array{lat: float, lon: float}
     */
    public function toArray(): array
    {
        return [
            'lat' => $this->latitude,
            'lon' => $this->longitude,
        ];
    }

    public function toString(): string
    {
        return sprintf('%.4f, %.4f', $this->latitude, $this->longitude);
    }

    /**
     * Calculate distance to another point using Haversine formula
     */
    public function distanceTo(GeolocationData $other): float
    {
        $earthRadius = 6371; // km

        $latFrom = deg2rad($this->latitude);
        $lonFrom = deg2rad($this->longitude);
        $latTo = deg2rad($other->latitude);
        $lonTo = deg2rad($other->longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) ** 2 +
             cos($latFrom) * cos($latTo) *
             sin($lonDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
