<?php

namespace App\Service;

use App\Entity\Car;
use App\Entity\Travel;
use App\Entity\TravelPreference;
use App\Entity\User;
use App\Enum\TravelStateEnum;
use App\Model\RouteData;

/**
 * Handles geocoding, route calculation, and data validation
 */
class TravelCreationService
{
    public function __construct(
        private readonly MapService $mapService
    ) {}

    /**
     * @return array{success: bool, data?: array, error?: string}
     */
    public function validateStep1(Travel $travel): array
    {
        $gs = $this->mapService->getGeocodingService();
        
        $departure = $gs->normalizeCityName($travel->getDeparture());
        $arrival = $gs->normalizeCityName($travel->getArrival());
        $departureCoords = $gs->geocode($departure);
        $arrivalCoords = $gs->geocode($arrival);

        if (!$departureCoords || !$arrivalCoords) {
            return [
                'success' => false,
                'error' => 'Impossible de géolocaliser une ou plusieurs villes.'
            ];
        }

        // Calculate route
        $routeData = $this->mapService->getRoutingService()->getRoute(
            $departureCoords,
            $arrivalCoords,
            $departure,
            $arrival
        );

        if (!$routeData) {
            return [
                'success' => false,
                'error' => 'Aucun itinéraire trouvé entre les deux villes.'
            ];
        }

        // Return validated data
        return [
            'success' => true,
            'data' => [
                'departure' => $departure,
                'arrival' => $arrival,
                'duration' => $routeData->getDurationMinutes(),
                'distance' => $routeData->getDistanceKm(),
            ]
        ];
    }

    /**
     * Create a draft travel entity from validated data
     */
    public function createTravel(
        User $driver,
        Car $car,
        array $step1Data,
        \DateTimeInterface $date
    ): Travel
    {
        $travel = new Travel();
        $travel->setDriver($driver)
            ->setCar($car)
            ->setDeparture($step1Data['departure'])
            ->setArrival($step1Data['arrival'])
            ->setDate($date)
            ->setDuration($step1Data['duration'])
            ->setDistance((int) $step1Data['distance'])
            ->setState(TravelStateEnum::PENDING)
            ->setPassengersMax($car->getTotalSeats() - 1)  // Step 2
            ->setCost(PHP_INT_MAX)                         // Step 2
            ->setTravelPreference(new TravelPreference()); // Step 2
        return $travel;
    }
}