<?php

namespace App\Tests\Service;

use App\Document\RouteCache;
use App\Model\GeolocationData;
use App\Model\RouteData;
use App\Repository\RouteCacheRepository;
use App\Service\RoutingService;
use Doctrine\ODM\MongoDB\DocumentManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RoutingServiceTest extends TestCase
{
    private DocumentManager&MockObject $documentManager;
    private RouteCacheRepository&MockObject $repository;
    private LoggerInterface&MockObject $logger;

    protected function setUp(): void
    {
        $this->documentManager = $this->createMock(DocumentManager::class);
        $this->repository = $this->createMock(RouteCacheRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->documentManager
            ->method('getRepository')
            ->with(RouteCache::class)
            ->willReturn($this->repository);
    }

    /**
     * Crée un RouteCache pour les tests
     */
    private function createCachedRoute(
        string $departureCity,
        string $arrivalCity,
        float $distanceKm,
        int $durationMinutes
    ): RouteCache {
        $cache = new RouteCache();
        $cache->setDepartureCity($departureCity);
        $cache->setArrivalCity($arrivalCity);
        $cache->setGeometry([
            ['lat' => 48.8566, 'lon' => 2.3522],
            ['lat' => 47.0, 'lon' => 3.0],
            ['lat' => 45.7640, 'lon' => 4.8357],
        ]);
        $cache->setDistanceKm($distanceKm);
        $cache->setDurationMinutes($durationMinutes);
        $cache->setExpiresAt(new \DateTime('+3 months'));

        return $cache;
    }

    /**
     * Crée une réponse OSRM mock
     */
    private function createOSRMResponse(
        array $geometry,
        float $distanceMeters,
        float $durationSeconds
    ): array {
        return [
            'routes' => [
                [
                    'geometry' => [
                        'coordinates' => $geometry,
                        'type' => 'LineString'
                    ],
                    'distance' => $distanceMeters,
                    'duration' => $durationSeconds,
                ]
            ]
        ];
    }

    public function testGetRouteReturnsCachedResult(): void
    {
        $parisCoords = new GeolocationData(48.8566, 2.3522);
        $lyonCoords = new GeolocationData(45.7640, 4.8357);

        $cachedRoute = $this->createCachedRoute('paris', 'lyon', 392.5, 240);

        $this->repository
            ->expects($this->once())
            ->method('findByCities')
            ->with('Paris', 'Lyon')
            ->willReturn($cachedRoute);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->never())->method('request');

        $service = new RoutingService($httpClient, $this->documentManager, $this->logger);
        $result = $service->getRoute($parisCoords, $lyonCoords, 'Paris', 'Lyon');

        $this->assertInstanceOf(RouteData::class, $result);
        $this->assertEquals(392.5, $result->distanceKm);
        $this->assertEquals(240, $result->durationMinutes);
        $this->assertCount(3, $result->getPoints());
    }

    public function testGetRouteCallsOSRMApiWhenCacheMiss(): void
    {
        $parisCoords = new GeolocationData(48.8566, 2.3522);
        $lyonCoords = new GeolocationData(45.7640, 4.8357);

        $this->repository
            ->method('findByCities')
            ->willReturn(null);

        $osrmResponse = $this->createOSRMResponse(
            [
                [2.3522, 48.8566],  // [lon, lat] format GeoJSON
                [3.0, 47.0],
                [4.8357, 45.7640]
            ],
            392500,  // 392.5 km en mètres
            14400    // 240 minutes en secondes
        );

        $mockResponse = new MockResponse(json_encode($osrmResponse));
        $httpClient = new MockHttpClient($mockResponse);

        $this->documentManager->expects($this->once())->method('persist');
        $this->documentManager->expects($this->once())->method('flush');

        $service = new RoutingService($httpClient, $this->documentManager, $this->logger);
        $result = $service->getRoute($parisCoords, $lyonCoords, 'Paris', 'Lyon');

        $this->assertInstanceOf(RouteData::class, $result);
        $this->assertEquals(392.5, $result->distanceKm);
        $this->assertEquals(240, $result->durationMinutes);
        $this->assertCount(3, $result->getPoints());
    }

    /**
     * @param array<mixed> $osrmResponse Réponse OSRM simulée
     * @param bool $shouldReturnNull Si null est attendu
     */
    #[DataProvider('osrmFailureProvider')]
    public function testGetRouteHandlesOSRMFailures(
        array $osrmResponse,
        int $httpCode,
        bool $shouldReturnNull,
        string $description
    ): void {
        $parisCoords = new GeolocationData(48.8566, 2.3522);
        $lyonCoords = new GeolocationData(45.7640, 4.8357);

        $this->repository->method('findByCities')->willReturn(null);

        $mockResponse = new MockResponse(
            json_encode($osrmResponse),
            ['http_code' => $httpCode]
        );
        $httpClient = new MockHttpClient($mockResponse);

        $this->documentManager->expects($this->never())->method('persist');

        $service = new RoutingService($httpClient, $this->documentManager, $this->logger);
        $result = $service->getRoute($parisCoords, $lyonCoords, 'Paris', 'Lyon');

        if ($shouldReturnNull) {
            $this->assertNull($result, $description);
        } else {
            $this->assertNotNull($result, $description);
        }
    }

    public static function osrmFailureProvider(): array
    {
        return [
            'HTTP 500 error' => [
                [],
                500,
                true,
                'Should return null on HTTP 500'
            ],
            'Empty routes array' => [
                ['routes' => []],
                200,
                true,
                'Should return null when no routes found'
            ],
            'Missing geometry' => [
                [
                    'routes' => [
                        ['distance' => 100000, 'duration' => 3600]
                    ]
                ],
                200,
                true,
                'Should return null when geometry is missing'
            ],
            'Empty geometry coordinates' => [
                [
                    'routes' => [
                        [
                            'geometry' => ['coordinates' => []],
                            'distance' => 100000,
                            'duration' => 3600
                        ]
                    ]
                ],
                200,
                true,
                'Should return null when geometry coordinates are empty'
            ],
        ];
    }

    public function testGetRouteSavesToCacheWithCorrectData(): void
    {
        $parisCoords = new GeolocationData(48.8566, 2.3522);
        $lyonCoords = new GeolocationData(45.7640, 4.8357);

        $this->repository->method('findByCities')->willReturn(null);

        $osrmResponse = $this->createOSRMResponse(
            [[2.3522, 48.8566], [4.8357, 45.7640]],
            150000,  // 150 km
            7200     // 120 min
        );

        $mockResponse = new MockResponse(json_encode($osrmResponse));
        $httpClient = new MockHttpClient($mockResponse);

        $capturedCache = null;
        $this->documentManager
            ->expects($this->once())
            ->method('persist')
            ->willReturnCallback(function ($cache) use (&$capturedCache) {
                $capturedCache = $cache;
            });

        $this->documentManager->expects($this->once())->method('flush');

        $service = new RoutingService($httpClient, $this->documentManager, $this->logger);
        $service->getRoute($parisCoords, $lyonCoords, 'Paris', 'Lyon');

        $this->assertInstanceOf(RouteCache::class, $capturedCache);
        
        /** @var RouteCache $capturedCache */
        $this->assertEquals('paris', $capturedCache->getDepartureCity());
        $this->assertEquals('lyon', $capturedCache->getArrivalCity());
        $this->assertEquals(150.0, $capturedCache->getDistanceKm());
        $this->assertEquals(120, $capturedCache->getDurationMinutes());
        $this->assertCount(2, $capturedCache->getGeometry());
    }

    /**
     * @param float $distanceMeters Distance en mètres
     * @param float $durationSeconds Durée en secondes
     * @param float $expectedKm Distance attendue en km
     * @param int $expectedMinutes Durée attendue en minutes
     */
    #[DataProvider('conversionProvider')]
    public function testGetRouteConvertsUnitsCorrectly(
        float $distanceMeters,
        float $durationSeconds,
        float $expectedKm,
        int $expectedMinutes
    ): void {
        $parisCoords = new GeolocationData(48.8566, 2.3522);
        $lyonCoords = new GeolocationData(45.7640, 4.8357);

        $this->repository->method('findByCities')->willReturn(null);

        $osrmResponse = $this->createOSRMResponse(
            [[2.3522, 48.8566], [4.8357, 45.7640]],
            $distanceMeters,
            $durationSeconds
        );

        $mockResponse = new MockResponse(json_encode($osrmResponse));
        $httpClient = new MockHttpClient($mockResponse);

        $this->documentManager->method('persist');
        $this->documentManager->method('flush');

        $service = new RoutingService($httpClient, $this->documentManager, $this->logger);
        $result = $service->getRoute($parisCoords, $lyonCoords, 'Paris', 'Lyon');

        $this->assertEquals($expectedKm, $result->distanceKm);
        $this->assertEquals($expectedMinutes, $result->durationMinutes);
    }

    public static function conversionProvider(): array
    {
        return [
            '100km, 60min' => [100000, 3600, 100.0, 60],
            '392.5km, 240min' => [392500, 14400, 392.5, 240],
            '1.5km, 2.5min (rounds up)' => [1500, 150, 1.5, 3],  // ceil(150/60) = 3
            '0.123km, 0.5min' => [123, 30, 0.12, 1],  // round(123/1000, 2) = 0.12, ceil(30/60) = 1
        ];
    }

    public function testGetRouteHandlesException(): void
    {
        $parisCoords = new GeolocationData(48.8566, 2.3522);
        $lyonCoords = new GeolocationData(45.7640, 4.8357);

        $this->repository->method('findByCities')->willReturn(null);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient
            ->method('request')
            ->willThrowException(new \RuntimeException('Network error'));

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Failed to get route',
                $this->callback(fn($context) => 
                    $context['departure'] === 'Paris' &&
                    $context['arrival'] === 'Lyon' &&
                    str_contains($context['error'], 'Network error')
                )
            );

        $service = new RoutingService($httpClient, $this->documentManager, $this->logger);
        $result = $service->getRoute($parisCoords, $lyonCoords, 'Paris', 'Lyon');

        $this->assertNull($result);
    }

    public function testClearExpiredCacheReturnsDeletedCount(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('countExpiredEntries')
            ->willReturn(5);

        $this->repository
            ->expects($this->once())
            ->method('deleteExpiredEntries')
            ->willReturn(5);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $service = new RoutingService($httpClient, $this->documentManager, $this->logger);

        $count = $service->clearExpiredCache();

        $this->assertEquals(5, $count);
    }

    public function testClearExpiredCacheReturnsZeroWhenNoExpiredEntries(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('countExpiredEntries')
            ->willReturn(0);

        $this->repository
            ->expects($this->never())
            ->method('deleteExpiredEntries');

        $httpClient = $this->createMock(HttpClientInterface::class);
        $service = new RoutingService($httpClient, $this->documentManager, $this->logger);

        $count = $service->clearExpiredCache();

        $this->assertEquals(0, $count);
    }
}
