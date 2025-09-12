<?php

namespace App\Repository;

use App\Entity\Travel;
use App\Enum\FuelTypeEnum;
use App\Repository\Trait\UuidFinderTrait;
use App\Sedarch\TravelCriteria;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Travel>
 */
class TravelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Travel::class);
    }

    use UuidFinderTrait;

    /**
     * Get travels by criteria with pagination.
     * Each page contains 10 results.
     * 
     * The result set is ordered by fuel type = Electric first, then others,
     * and by travel date.
     * 
     * @param TravelCriteria $criteria The search criteria
     * @param int $page The page number (1-based)
     * @return array An array of travels matching the criteria
     */
    public function getTravelsByCriteria(TravelCriteria $criteria, int $page): array
    {
        $firstResult = max($page - 1, 0) * 10;
        return $this->createQueryBuilder('t')
            ->select('t.uuid as travelUuid, d.uuid as driverUuid, t.date, t.duration, t.cost, t.passengersMax, COUNT(tu) AS currentPassengers, (t.passengersMax - COUNT(tu)) AS availablePlaces, c.fuelType')
            ->innerJoin('t.driver', 'd')
            ->innerJoin('t.travelPreference', 'tp')
            ->innerJoin('t.car', 'c')
            ->leftJoin('t.carpoolers', 'tu')
            ->where('t.date BETWEEN :dateMin AND :dateMax')
            ->andWhere('t.departure = :departure')
            ->andWhere('t.arrival = :arrival')
            //->andWhere('tp.isPetsAllowed = :isPetsAllowed')
            ->groupBy('t.uuid, d.uuid, t.date, t.duration, t.cost, t.passengersMax, c.fuelType')
            ->having('availablePlaces >= :passengersMin')
            ->orderBy('CASE WHEN c.fuelType = :electric THEN 1 ELSE 0 END', 'DESC')
            ->addOrderBy('t.date', 'ASC')
            ->setMaxResults(10)
            ->setFirstResult($firstResult)
            //->setParameter('isPetsAllowed', true)
            ->setParameter('dateMin', $criteria->getDateTime()->format('Y-m-d H:i:s'))
            ->setParameter('dateMax', $criteria->getDateTime()->format('Y-m-d 23:59:59'))
            ->setParameter('departure', $criteria->getDeparture())
            ->setParameter('arrival', $criteria->getArrival())
            ->setParameter('passengersMin', $criteria->getPassengersMin())
            ->setParameter('electric', FuelTypeEnum::ELECTRIC)
            ->getQuery()
            ->getResult();
    }

}
