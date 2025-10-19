<?php

namespace App\Service;

use App\Document\GeocodingCache;
use App\Model\GeolocationData;
use App\Repository\GeocodingCacheRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Service for geocoding city names to GPS coordinates
 * Uses Nominatim API with MongoDB caching
 */
class GeocodingService
{
    private const CACHE_DURATION_MONTHS = 3;
    private const RATE_LIMIT_DELAY_SECONDS = 1; // Nominatim requires max 1 req/sec

    public function __construct(
        private readonly HttpClientInterface $nominatimClient,
        private readonly DocumentManager $dm,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Geocode a city name to GPS coordinates
     * Returns cached result if available, otherwise calls Nominatim API
     */
    public function geocode(string $cityName): ?GeolocationData
    {
        $normalizedCity = $this->normalizeCityName($cityName);

        // Step 1: Check MongoDB cache
        $cached = $this->findInCache($normalizedCity);

        if ($cached !== null) {
            $this->logger->info('Geocoding cache hit', [
                'city' => $normalizedCity,
                'latitude' => $cached->latitude,
                'longitude' => $cached->longitude
            ]);
            return $cached;
        }

        // Step 2: Call Nominatim API with rate limiting
        $this->logger->info('Geocoding cache miss, calling Nominatim API', [
            'city' => $normalizedCity
        ]);

        sleep(self::RATE_LIMIT_DELAY_SECONDS);

        try {
            $coordinates = $this->callNominatimApi($normalizedCity);
            
            if ($coordinates === null) {
                $this->logger->warning('Geocoding failed: no results found', [
                    'city' => $normalizedCity
                ]);
                return null;
            }

            // Step 3: Save to cache
            $this->saveToCache($normalizedCity, $coordinates);

            return $coordinates;

        } catch (\Exception $e) {
            $this->logger->error('Geocoding API error', [
                'city' => $normalizedCity,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    private function normalizeCityName(string $cityName): string
    {
        return mb_strtolower(trim($cityName));
    }

    private function findInCache(string $normalizedCity): ?GeolocationData
    {
        /** @var GeocodingCacheRepository $repository */
        $repository = $this->dm->getRepository(GeocodingCache::class);
        $cached = $repository->findOneValidCache($normalizedCity);

        if ($cached === null) {
            return null;
        }

        return new GeolocationData(
            latitude: $cached->getLatitude(),
            longitude: $cached->getLongitude()
        );
    }

    /**
     * Call Nominatim API to geocode a city
     * 
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     */
    private function callNominatimApi(string $normalizedCity): ?GeolocationData
    {
        $response = $this->nominatimClient->request('GET', '/search', [
            'query' => [
                'q' => $normalizedCity . ', France',
                'format' => 'json',
                'limit' => 1,
                'addressdetails' => 1
            ]
        ]);

        $data = $response->toArray();

        if (empty($data)) {
            return null;
        }

        return new GeolocationData(
            latitude: (float) $data[0]['lat'],
            longitude: (float) $data[0]['lon']
        );
    }

    /**
     * Save geocoding result to MongoDB cache
     */
    private function saveToCache(string $normalizedCity, GeolocationData $coordinates): void
    {
        try {
            $cache = new GeocodingCache();
            $cache->setCityName($normalizedCity);
            $cache->setLatitude($coordinates->latitude);
            $cache->setLongitude($coordinates->longitude);
            
            $expiresAt = new \DateTimeImmutable(sprintf('+%d months', self::CACHE_DURATION_MONTHS));
            $cache->setExpiresAt($expiresAt);

            $this->dm->persist($cache);
            $this->dm->flush();

            $this->logger->info('Geocoding result cached', [
                'city' => $normalizedCity,
                'latitude' => $coordinates->latitude,
                'longitude' => $coordinates->longitude,
                'expires_at' => $expiresAt->format('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            // Log error but don't fail the geocoding operation
            $this->logger->error('Failed to cache geocoding result', [
                'city' => $normalizedCity,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * @return int Number of deleted entries
     */
    public function clearExpiredCache(): int
    {
        try {
            /** @var GeocodingCacheRepository $repository */
            $repository = $this->dm->getRepository(GeocodingCache::class);
            $count = $repository->countExpiredEntries();

            if ($count > 0) {
                $this->dm->createQueryBuilder(GeocodingCache::class)
                    ->remove()
                    ->field('expiresAt')->lt(new \DateTime())
                    ->getQuery()
                    ->execute();

                $this->logger->info('Cleared expired geocoding cache', [
                    'count' => $count
                ]);
            }

            return $count;

        } catch (\Exception $e) {
            $this->logger->error('Failed to clear expired cache', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }
}
