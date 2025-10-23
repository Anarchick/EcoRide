<?php

namespace App\Tests\Service;

use App\Document\GeocodingCache;
use App\Model\GeolocationData;
use App\Repository\GeocodingCacheRepository;
use App\Service\GeocodingService;
use Doctrine\ODM\MongoDB\DocumentManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeocodingServiceTest extends TestCase
{
    private DocumentManager&MockObject $documentManager;
    private GeocodingCacheRepository&MockObject $repository;
    private LoggerInterface&MockObject $logger;

    protected function setUp(): void
    {
        $this->documentManager = $this->createMock(DocumentManager::class);
        $this->repository = $this->createMock(GeocodingCacheRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->documentManager
            ->method('getRepository')
            ->with(GeocodingCache::class)
            ->willReturn($this->repository);
    }

    private function createCachedEntry(string $cityName, float $lat, float $lon): GeocodingCache
    {
        $cache = new GeocodingCache();
        $cache->setCityName($cityName);
        $cache->setLatitude($lat);
        $cache->setLongitude($lon);
        $cache->setExpiresAt(new \DateTime('+1 month'));
        
        return $cache;
    }

    #[DataProvider('cachedCitiesProvider')]
    public function testGeocodeReturnsCachedResult(
        string $cityInput,
        string $normalizedCity,
        float $expectedLat,
        float $expectedLon
    ): void {
        $cachedEntry = $this->createCachedEntry($normalizedCity, $expectedLat, $expectedLon);

        $this->repository
            ->expects($this->once())
            ->method('findOneValidCache')
            ->with($normalizedCity)
            ->willReturn($cachedEntry);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->never())->method('request');

        $service = new GeocodingService($httpClient, $this->documentManager, $this->logger);
        $result = $service->geocode($cityInput);

        $this->assertInstanceOf(GeolocationData::class, $result);
        $this->assertEquals($expectedLat, $result->latitude);
        $this->assertEquals($expectedLon, $result->longitude);
    }

    public static function cachedCitiesProvider(): array
    {
        return [
            'Paris (exact)' => ['Paris', 'paris', 48.8566, 2.3522],
            'Lyon (lowercase)' => ['lyon', 'lyon', 45.7640, 4.8357],
            'Toulouse (avec espaces et majuscules)' => ['  TOULOUSE  ', 'toulouse', 43.6047, 1.4442],
        ];
    }

    #[DataProvider('apiResponseProvider')]
    public function testGeocodeCallsApiWhenCacheMiss(
        string $cityName,
        array $apiResponse,
        ?float $expectedLat,
        ?float $expectedLon
    ): void {
        $this->repository
            ->method('findOneValidCache')
            ->willReturn(null);

        $mockResponse = new MockResponse(json_encode($apiResponse));
        $httpClient = new MockHttpClient($mockResponse);

        if ($expectedLat !== null) {
            $this->documentManager->expects($this->once())->method('persist');
            $this->documentManager->expects($this->once())->method('flush');
        } else {
            $this->documentManager->expects($this->never())->method('persist');
        }

        $service = new GeocodingService($httpClient, $this->documentManager, $this->logger);
        $result = $service->geocode($cityName);

        if ($expectedLat === null) {
            $this->assertNull($result);
        } else {
            $this->assertInstanceOf(GeolocationData::class, $result);
            $this->assertEquals($expectedLat, $result->latitude);
            $this->assertEquals($expectedLon, $result->longitude);
        }
    }

    public static function apiResponseProvider(): array
    {
        return [
            'Lyon trouvée' => [
                'Lyon',
                [['lat' => '45.7640', 'lon' => '4.8357', 'display_name' => 'Lyon, France']],
                45.7640,
                4.8357
            ],
            'Ville inconnue (résultat vide)' => [
                'UnknownCity',
                [],
                null,
                null
            ],
            'Marseille trouvée' => [
                'Marseille',
                [['lat' => '43.2965', 'lon' => '5.3698']],
                43.2965,
                5.3698
            ],
        ];
    }

    public function testGeocodeHandlesApiException(): void
    {
        $this->repository->method('findOneValidCache')->willReturn(null);

        $mockResponse = new MockResponse('', ['http_code' => 500]);
        $httpClient = new MockHttpClient($mockResponse);

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Geocoding API error',
                $this->callback(fn($context) => isset($context['city']) && $context['city'] === 'paris')
            );

        $service = new GeocodingService($httpClient, $this->documentManager, $this->logger);
        $result = $service->geocode('Paris');

        $this->assertNull($result);
    }

    public function testGeocodeSavesToCacheWithCorrectExpiration(): void
    {
        $this->repository->method('findOneValidCache')->willReturn(null);

        $mockResponse = new MockResponse(json_encode([
            ['lat' => '43.2965', 'lon' => '5.3698']
        ]));
        $httpClient = new MockHttpClient($mockResponse);

        $capturedCache = null;
        $this->documentManager
            ->expects($this->once())
            ->method('persist')
            ->willReturnCallback(function ($cache) use (&$capturedCache) {
                $capturedCache = $cache;
            });

        $this->documentManager->expects($this->once())->method('flush');

        $service = new GeocodingService($httpClient, $this->documentManager, $this->logger);
        $service->geocode('Marseille');

        $this->assertInstanceOf(GeocodingCache::class, $capturedCache);
        
        /** @var GeocodingCache $capturedCache */
        $this->assertEquals('marseille', $capturedCache->getCityName());
        $this->assertEquals(43.2965, $capturedCache->getLatitude());
        $this->assertEquals(5.3698, $capturedCache->getLongitude());
        
        $expectedExpiration = new \DateTime('+3 months');
        $actualExpiration = $capturedCache->getExpiresAt();
        $diff = $expectedExpiration->diff($actualExpiration);
        
        $this->assertLessThan(2, $diff->days, 'Expiration should be approximately 3 months');
    }

    public function testClearExpiredCacheReturnsCount(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('countExpiredEntries')
            ->willReturn(5);

        $this->documentManager
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->with(GeocodingCache::class)
            ->willThrowException(new \RuntimeException('MongoDB not available in unit tests'));

        $httpClient = $this->createMock(HttpClientInterface::class);
        $service = new GeocodingService($httpClient, $this->documentManager, $this->logger);

        $count = $service->clearExpiredCache();

        $this->assertEquals(0, $count);
    }
}
