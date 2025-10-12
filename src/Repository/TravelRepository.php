<?php

namespace App\Repository;

use App\Entity\Travel;
use App\Enum\DateIntervalEnum;
use App\Enum\FuelTypeEnum;
use App\Enum\LuggageSizeEnum;
use App\Enum\TravelStateEnum;
use App\Repository\Trait\UuidFinderTrait;
use App\Model\Search\TravelCriteria;
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
        $query = $this->createQueryBuilder('t')
            ->select('t.uuid as travelUuid, t.date, t.duration, t.cost, t.passengersMax, c.fuelType')
            ->addSelect('COUNT(tu) AS currentPassengers, (t.passengersMax - COUNT(tu)) AS availablePlaces')
            ->addSelect('DATE_ADD(t.date, t.duration, \'MINUTE\') AS arrivalDateTime')
            ->addSelect('d.uuid AS driverUuid, d.username AS driverUsername, d.ratingAverage AS driverRating')
            ->innerJoin('t.driver', 'd')
            ->innerJoin('t.travelPreference', 'tp')
            ->innerJoin('t.car', 'c')
            ->leftJoin('t.carpoolers', 'tu')
            ->where('t.date BETWEEN :dateMin AND :dateMax')
            ->andWhere('t.departure = :departure')
            ->andWhere('t.arrival = :arrival')
            ->andWhere('t.state = :state')
            ->groupBy('t.uuid, d.uuid, t.date, t.duration, t.cost, t.passengersMax, c.fuelType')
            ->having('availablePlaces >= :minPassengers')
            ->orderBy('CASE WHEN c.fuelType = :electric THEN 1 ELSE 0 END', 'DESC')
            ->addOrderBy('t.date', 'ASC')
            ->setMaxResults(10)
            ->setFirstResult($firstResult)
            ->setParameter('dateMin', $criteria->getDateTime()->format('Y-m-d H:i:s'))
            ->setParameter('dateMax', $criteria->getDateTime()->format('Y-m-d 23:59:59'))
            ->setParameter('departure', $criteria->getDeparture())
            ->setParameter('arrival', $criteria->getArrival())
            ->setParameter('minPassengers', $criteria->getMinPassengers())
            ->setParameter('state', TravelStateEnum::PENDING)
            ->setParameter('electric', FuelTypeEnum::ELECTRIC);
        
        if ($criteria->isElectricPreferred()) {
            $query->andWhere('c.fuelType = :electric');
        }

        if ($criteria->isSmokingAllowed()) {
            $query->andWhere('tp.isSmokingAllowed = true');
        }

        if ($criteria->isPetsAllowed()) {
            $query->andWhere('tp.isPetsAllowed = true');
        }

        if ($criteria->getMaxCost() < 1000) {
            $query->andWhere('t.cost <= :maxCost')
                ->setParameter('maxCost', $criteria->getMaxCost());
        }

        if ($criteria->getLuggageSizeMin() != LuggageSizeEnum::NONE) {
            $query->andWhere('tp.luggageSize >= :minLuggageSize')
                ->setParameter('minLuggageSize', $criteria->getLuggageSizeMin()->ordinal());
        }

        if ($criteria->getMinScore() > 0) {
            $query->andWhere('d.ratingAverage >= :minScore')
                ->setParameter('minScore', $criteria->getMinScore());
        }

        if ($criteria->getMaxDuration() < 24) {
            $query->andWhere('t.duration <= :maxDuration')
                ->setParameter('maxDuration', $criteria->getMaxDuration() * 60);
        }

        return $query->getQuery()->getResult();
    }

    /**
     * Used for chart.js statistics: get the count of travels by period intervals
     * 
     * @param int $interval The interval step (1, 2, 3...)
     * @param DateIntervalEnum $intervalEnum The type of interval (DAY, MONTH, YEAR)
     * @param \DateTimeInterface $from Start date
     * @param \DateTimeInterface $to End date
     * @return array Array with 'period' and 'count' keys for each interval
     */
    public function getCountsByPeriod(
            int $interval = 1,
            DateIntervalEnum $intervalEnum = DateIntervalEnum::DAY,
            \DateTimeInterface $from,
            \DateTimeInterface $to
    ): array {
        $dateFormat = match($intervalEnum) {
            DateIntervalEnum::DAY => "DATE_FORMAT(t.date, '%Y-%m-%d')",
            //DateIntervalEnum::WEEK => "DATE_FORMAT(t.date, '%Y-%m-%d')", // SQL does not have a week format
            DateIntervalEnum::MONTH => "DATE_FORMAT(t.date, '%Y-%m')",
            DateIntervalEnum::YEAR => "DATE_FORMAT(t.date, '%Y')",
            default => throw new \InvalidArgumentException("Interval not supported: {$intervalEnum->value}")
        };
        // Select amount of travels grouped by the chosen interval
        $qb = $this->createQueryBuilder('t')
            ->select("$dateFormat as period", 'COUNT(t.uuid) as count')
            ->where('t.date BETWEEN :from AND :to')
            ->groupBy('period')
            ->orderBy('period', 'ASC')
            ->setParameter('from', $from)
            ->setParameter('to', $to);

        $results = $qb->getQuery()->getResult();

        // If interval > 1, cannot be done in SQL
        if ($interval > 1) {
            return $this->aggregateByInterval($results, $interval, $intervalEnum, $from, $to);
        }

        return $results;
    }

    /**
     * Aggregate results by custom interval (e.g. every 2 days, every 3 months)
     */
    private function aggregateByInterval(array $results, int $interval, DateIntervalEnum $intervalEnum, \DateTimeInterface $from, \DateTimeInterface $to): array {
        throw new \LogicException('Not implemented yet');
    }
}
