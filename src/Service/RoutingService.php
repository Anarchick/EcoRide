<?php

namespace App\Service;

use App\Document\RouteCache;
use App\Model\GeolocationData;
use App\Model\RouteData;
use App\Repository\RouteCacheRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\UX\Map\Point;

/**
 * Service for routing calculations using OSRM API
 * Handles route calculation with MongoDB caching to reduce API calls
 */
class RoutingService
{
    private const CACHE_TTL_MONTHS = 3;
    
    public function __construct(
        private readonly HttpClientInterface $osrmClient,
        private readonly DocumentManager $documentManager,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Get route between two coordinates
     * Checks cache first, then calls OSRM API if needed
     */
    public function getRoute(
        GeolocationData $departure,
        GeolocationData $arrival,
        string $departureCity,
        string $arrivalCity
    ): ?RouteData {
        $this->logger->info('Getting route', [
            'departure' => $departureCity,
            'arrival' => $arrivalCity
        ]);

        // Step 1: Check MongoDB cache
        /** @var RouteCacheRepository $repository */
        $repository = $this->documentManager->getRepository(RouteCache::class);
        $cachedRoute = $repository->findByCities($departureCity, $arrivalCity);

        if ($cachedRoute !== null) {
            $this->logger->info('Route found in cache', [
                'departure' => $departureCity,
                'arrival' => $arrivalCity
            ]);

            return RouteData::fromCacheGeometry(
                $cachedRoute->getGeometry(),
                $cachedRoute->getDistanceKm(),
                $cachedRoute->getDurationMinutes()
            );
        }

        // Step 2: Call OSRM API
        try {
            $routeData = $this->fetchRouteFromOSRM($departure, $arrival);

            if ($routeData === null) {
                return null;
            }

            // Step 3: Save to cache
            $this->saveToCache($departureCity, $arrivalCity, $routeData);

            $this->logger->info('Route fetched from OSRM and cached', [
                'departure' => $departureCity,
                'arrival' => $arrivalCity,
                'distance' => $routeData->distanceKm,
                'duration' => $routeData->durationMinutes
            ]);

            return $routeData;

        } catch (\Exception $e) {
            $this->logger->error('Failed to get route', [
                'departure' => $departureCity,
                'arrival' => $arrivalCity,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Fetch route from OSRM API
     * 
     * @throws TransportException
     */
    private function fetchRouteFromOSRM(GeolocationData $departure, GeolocationData $arrival): ?RouteData
    {
        // OSRM expects coordinates as: lon,lat (NOT lat,lon!)
        $coordinates = sprintf(
            '%f,%f;%f,%f',
            $departure->longitude,
            $departure->latitude,
            $arrival->longitude,
            $arrival->latitude
        );

        // Call OSRM route service
        // Documentation: http://project-osrm.org/docs/v5.24.0/api/#route-service
        $response = $this->osrmClient->request('GET', '/route/v1/driving/' . $coordinates, [
            'query' => [
                'overview' => 'full',        // Get full geometry
                'geometries' => 'geojson',   // Use GeoJSON format (easier to parse)
                'steps' => 'false',          // We don't need turn-by-turn instructions
            ],
        ]);

        $statusCode = $response->getStatusCode();

        if ($statusCode !== 200) {
            $this->logger->warning('OSRM API returned non-200 status', [
                'status' => $statusCode
            ]);
            return null;
        }

        $data = $response->toArray();

        // Check if route was found
        if (!isset($data['routes']) || empty($data['routes'])) {
            $this->logger->warning('OSRM returned no routes');
            return null;
        }

        $route = $data['routes'][0]; // Use first (best) route

        // Extract geometry (array of [lon, lat] pairs in GeoJSON)
        $geometry = $route['geometry']['coordinates'] ?? [];
        
        if (empty($geometry)) {
            $this->logger->warning('OSRM route has no geometry');
            return null;
        }

        // Convert GeoJSON coordinates [lon, lat] to Point objects [lat, lon]
        $points = array_map(
            fn(array $coord) => new Point($coord[1], $coord[0]), // GeoJSON is [lon, lat], we need [lat, lon]
            $geometry
        );

        // Extract distance (in meters) and duration (in seconds)
        $distanceMeters = $route['distance'] ?? 0;
        $durationSeconds = $route['duration'] ?? 0;

        return new RouteData(
            points: $points,
            distanceKm: round($distanceMeters / 1000, 2), // Convert meters to km
            durationMinutes: (int) ceil($durationSeconds / 60) // Convert seconds to minutes
        );
    }

    private function saveToCache(string $departureCity, string $arrivalCity, RouteData $routeData): void
    {
        $cache = new RouteCache();
        $cache->setDepartureCity($departureCity);
        $cache->setArrivalCity($arrivalCity);
        
        $arrayData = $routeData->toArray();
        $cache->setGeometry($arrayData['geometry']);
        $cache->setDistanceKm($routeData->distanceKm);
        $cache->setDurationMinutes($routeData->durationMinutes);

        $this->documentManager->persist($cache);
        $this->documentManager->flush();
    }

    /**
     * @return int Number of deleted entries
     */
    public function clearExpiredCache(): int
    {
        /** @var RouteCacheRepository $repository */
        $repository = $this->documentManager->getRepository(RouteCache::class);
        
        $count = $repository->countExpiredEntries();
        
        if ($count > 0) {
            $deleted = $repository->deleteExpiredEntries();
            
            $this->logger->info('Cleared expired route cache', [
                'count' => $deleted
            ]);
            
            return $deleted;
        }

        return 0;
    }
}
