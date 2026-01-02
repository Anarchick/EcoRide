<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\Marker;
use Symfony\UX\Map\Point;
use Symfony\UX\Map\InfoWindow;
use Symfony\UX\Map\Polyline;

class MapService
{
    public function __construct(
        private readonly GeocodingService $geocodingService,
        private readonly RoutingService $routingService,
        private readonly LoggerInterface $logger
    ) {}

    public function getGeocodingService(): GeocodingService
    {
        return $this->geocodingService;
    }

    public function getRoutingService(): RoutingService
    {
        return $this->routingService;
    }

    /**
     * Create a Symfony UX Map for displaying a route between two cities
     * Shows markers and OSRM routing polyline with distance/duration
     */
    public function createTravelMap(string $departureCity, string $arrivalCity): ?Map
    {
        $this->logger->info('Creating travel map', [
            'departure' => $departureCity,
            'arrival' => $arrivalCity
        ]);

        // Step 1: Geocode departure city
        $departureCoords = $this->geocodingService->geocode($departureCity);
        if ($departureCoords === null) {
            $this->logger->warning('Failed to geocode departure city', [
                'city' => $departureCity
            ]);
            return null;
        }

        // Step 2: Geocode arrival city
        $arrivalCoords = $this->geocodingService->geocode($arrivalCity);
        if ($arrivalCoords === null) {
            $this->logger->warning('Failed to geocode arrival city', [
                'city' => $arrivalCity
            ]);
            return null;
        }

        // Step 3: Create map centered between both cities
        $centerLat = ($departureCoords->latitude + $arrivalCoords->latitude) / 2;
        $centerLon = ($departureCoords->longitude + $arrivalCoords->longitude) / 2;

        $map = (new Map())
            ->center(new Point($centerLat, $centerLon))
            ->zoom(6); // Will be auto-adjusted by fitBoundsToMarkers

        // Step 4: Get route from OSRM (with MongoDB cache)
        $routeData = $this->routingService->getRoute(
            $departureCoords,
            $arrivalCoords,
            $departureCity,
            $arrivalCity
        );

        // Step 5: Add routing polyline if available
        if ($routeData !== null) {
            $map->addPolyline(new Polyline(
                points: $routeData->getPoints(),
                infoWindow: new InfoWindow(
                    content: sprintf(
                        '<strong>Itinéraire</strong><br>Distance: %s<br>Durée: %s',
                        $routeData->getFormattedDistance(),
                        $routeData->getFormattedDuration()
                    )
                ),
                extra: [
                    'color' => '#3b82f6',
                    'weight' => 4,
                    'opacity' => 0.7,
                ]
            ));

            $this->logger->info('Route polyline added to map', [
                'distance' => $routeData->distanceKm,
                'duration' => $routeData->durationMinutes,
                'points' => $routeData->getPointCount()
            ]);
        } else {
            $this->logger->warning('Failed to get route from OSRM, map will show markers only');
        }

        // Step 6: Add departure marker (green)
        $map->addMarker(new Marker(
            position: new Point($departureCoords->latitude, $departureCoords->longitude),
            title: 'Départ',
            infoWindow: new InfoWindow(
                content: sprintf('<strong>Départ</strong><br>%s', ucfirst($departureCity))
            )
        ));

        // Step 7: Add arrival marker (red)
        $map->addMarker(new Marker(
            position: new Point($arrivalCoords->latitude, $arrivalCoords->longitude),
            title: 'Arrivée',
            infoWindow: new InfoWindow(
                content: sprintf('<strong>Arrivée</strong><br>%s', ucfirst($arrivalCity))
            )
        ));

        $map->fitBoundsToMarkers();

        $this->logger->info('Travel map created successfully', [
            'departure' => $departureCity,
            'arrival' => $arrivalCity
        ]);

        return $map;
    }

}
