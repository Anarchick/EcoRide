<?php

namespace App\Tests\Model;

use App\Model\GeolocationData;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class GeolocationDataTest extends TestCase
{
    #[DataProvider('validCoordinatesProvider')]
    public function testConstructorWithValidCoordinates(float $lat, float $lon): void
    {
        $geolocation = new GeolocationData($lat, $lon);
        $this->assertEquals($lat, $geolocation->latitude);
        $this->assertEquals($lon, $geolocation->longitude);
    }

    public static function validCoordinatesProvider(): array
    {
        return [
            'Paris' => [48.8566, 2.3522],
            'Lyon' => [45.7640, 4.8357],
            'Min boundaries' => [-90.0, -180.0],
            'Max boundaries' => [90.0, 180.0],
            'Equator' => [0.0, 0.0],
        ];
    }

    #[DataProvider('invalidCoordinatesProvider')]
    public function testConstructorThrowsExceptionForInvalidCoordinates(
        float $lat,
        float $lon,
        string $expectedMessage
    ): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);
        new GeolocationData($lat, $lon);
    }

    public static function invalidCoordinatesProvider(): array
    {
        return [
            'Latitude too large' => [91.0, 2.3522, 'Invalid latitude value'],
            'Latitude too small' => [-91.0, 2.3522, 'Invalid latitude value'],
            'Longitude too large' => [48.8566, 181.0, 'Invalid longitude value'],
            'Longitude too small' => [48.8566, -181.0, 'Invalid longitude value'],
        ];
    }

    public function testFromArrayWithValidData(): void
    {
        $data = ['lat' => 45.7640, 'lon' => 4.8357];
        $geolocation = GeolocationData::fromArray($data);

        $this->assertEquals(45.7640, $geolocation->latitude);
        $this->assertEquals(4.8357, $geolocation->longitude);
    }

    #[DataProvider('invalidArrayDataProvider')]
    public function testFromArrayThrowsExceptionForInvalidData(array $data): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Array must contain both "lat" and "lon" keys');
        GeolocationData::fromArray($data);
    }

    public static function invalidArrayDataProvider(): array
    {
        return [
            'Missing lat' => [['lon' => 4.8357]],
            'Missing lon' => [['lat' => 45.7640]],
            'Empty array' => [[]],
        ];
    }

    public function testToArrayReturnsCorrectFormat(): void
    {
        $geolocation = new GeolocationData(43.2965, 5.3698);
        $array = $geolocation->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('lat', $array);
        $this->assertArrayHasKey('lon', $array);
        $this->assertEquals(43.2965, $array['lat']);
        $this->assertEquals(5.3698, $array['lon']);
    }

    #[DataProvider('toStringFormatProvider')]
    public function testToStringFormatsCorrectly(float $lat, float $lon, string $expected): void
    {
        $geolocation = new GeolocationData($lat, $lon);
        $this->assertEquals($expected, $geolocation->toString());
    }

    public static function toStringFormatProvider(): array
    {
        return [
            'Paris 4 decimals' => [48.8566, 2.3522, '48.8566, 2.3522'],
            'Rounded down' => [48.856614, 2.3522219, '48.8566, 2.3522'],
            'Negative coords' => [-33.8688, 151.2093, '-33.8688, 151.2093'],
        ];
    }

    /**
     * Test Haversine distance calculation
     */
    #[DataProvider('distanceCalculationProvider')]
    public function testDistanceToCalculatesCorrectly(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2,
        float $minDistance,
        float $maxDistance,
        string $description
    ): void {
        $point1 = new GeolocationData($lat1, $lon1);
        $point2 = new GeolocationData($lat2, $lon2);
        $distance = $point1->distanceTo($point2);

        $this->assertGreaterThanOrEqual($minDistance, $distance, "$description: Distance too small");
        $this->assertLessThanOrEqual($maxDistance, $distance, "$description: Distance too large");
    }

    public static function distanceCalculationProvider(): array
    {
        return [
            // Description, lat1, lon1, lat2, lon2, min, max
            'Paris-Lyon (~392 km)' => [48.8566, 2.3522, 45.7640, 4.8357, 387, 397, 'Paris to Lyon'],
            'Paris-Marseille (~660 km)' => [48.8566, 2.3522, 43.2965, 5.3698, 655, 665, 'Paris to Marseille'],
            'Toulouse-Bordeaux (~212 km)' => [43.6047, 1.4442, 44.8378, -0.5792, 207, 217, 'Toulouse to Bordeaux'],
            'Same point (0 km)' => [48.8566, 2.3522, 48.8566, 2.3522, 0, 0.01, 'Zero distance'],
            'Very close points (~0.11 km)' => [48.8566, 2.3522, 48.8576, 2.3522, 0.10, 0.15, 'Adjacent points'],
        ];
    }

    public function testGeolocationDataIsReadonly(): void
    {
        $geolocation = new GeolocationData(48.8566, 2.3522);
        $reflection = new \ReflectionClass($geolocation);
        $this->assertTrue($reflection->isReadOnly(), 'GeolocationData class should be readonly');
    }
}
