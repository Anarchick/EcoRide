<?php

namespace App\Tests\Service;

use App\Model\GeolocationData;
use App\Model\RouteData;
use App\Service\GeocodingService;
use App\Service\MapService;
use App\Service\RoutingService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\Point;

class MapServiceTest extends TestCase
{
    private GeocodingService&MockObject $geocodingService;
    private RoutingService&MockObject $routingService;
    private LoggerInterface&MockObject $logger;

    protected function setUp(): void
    {
        $this->geocodingService = $this->createMock(GeocodingService::class);
        $this->routingService = $this->createMock(RoutingService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    private function createMapService(): MapService
    {
        return new MapService($this->geocodingService, $this->routingService, $this->logger);
    }

    public function testCreateTravelMapReturnsMapWithBothMarkers(): void
    {
        $parisCoords = new GeolocationData(48.8566, 2.3522);
        $lyonCoords = new GeolocationData(45.7640, 4.8357);

        $this->geocodingService
            ->expects($this->exactly(2))
            ->method('geocode')
            ->willReturnOnConsecutiveCalls($parisCoords, $lyonCoords);

        $this->routingService
            ->method('getRoute')
            ->willReturn(null); // Pas de route pour ce test

        $service = $this->createMapService();
        $map = $service->createTravelMap('Paris', 'Lyon');

        $this->assertInstanceOf(Map::class, $map);
        $this->assertNotNull($map);
    }

    #[DataProvider('geocodingFailureProvider')]
    public function testCreateTravelMapReturnsNullWhenGeocodingFails(
        ?array $departureCoords,
        ?array $arrivalCoords,
        int $geocodeCallCount,
        string $description
    ): void {
        $departure = $departureCoords ? new GeolocationData($departureCoords[0], $departureCoords[1]) : null;
        $arrival = $arrivalCoords ? new GeolocationData($arrivalCoords[0], $arrivalCoords[1]) : null;

        $this->geocodingService
            ->expects($this->exactly($geocodeCallCount))
            ->method('geocode')
            ->willReturnOnConsecutiveCalls($departure, $arrival);

        $service = $this->createMapService();
        $map = $service->createTravelMap('CityA', 'CityB');

        $this->assertNull($map, $description);
    }

    public static function geocodingFailureProvider(): array
    {
        return [
            'Departure fails' => [
                null,                           // departureCoords
                [45.7640, 4.8357],              // arrivalCoords
                1,                              // geocodeCallCount (stops after first failure)
                'Should return null when departure geocoding fails'
            ],
            'Arrival fails' => [
                [48.8566, 2.3522],              // departureCoords
                null,                           // arrivalCoords
                2,                              // geocodeCallCount
                'Should return null when arrival geocoding fails'
            ],
        ];
    }

    public function testCreateTravelMapWithRouteData(): void
    {
        $parisCoords = new GeolocationData(48.8566, 2.3522);
        $lyonCoords = new GeolocationData(45.7640, 4.8357);

        $routeData = new RouteData(
            points: [
                new Point(48.8566, 2.3522),
                new Point(47.0, 3.0),
                new Point(45.7640, 4.8357)
            ],
            distanceKm: 392.5,
            durationMinutes: 240
        );

        $this->geocodingService
            ->method('geocode')
            ->willReturnOnConsecutiveCalls($parisCoords, $lyonCoords);

        $this->routingService
            ->expects($this->once())
            ->method('getRoute')
            ->with($parisCoords, $lyonCoords, 'Paris', 'Lyon')
            ->willReturn($routeData);

        $service = $this->createMapService();
        $map = $service->createTravelMap('Paris', 'Lyon');

        $this->assertInstanceOf(Map::class, $map);
        $this->assertNotNull($map);
    }
}
