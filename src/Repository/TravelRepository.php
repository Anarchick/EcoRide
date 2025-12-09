<?php

namespace App\Repository;

use App\Entity\Travel;
use App\Entity\User;
use App\Enum\DateIntervalEnum;
use App\Enum\FuelTypeEnum;
use App\Enum\LuggageSizeEnum;
use App\Enum\TravelStateEnum;
use App\Repository\Trait\UuidFinderTrait;
use App\Model\Search\TravelCriteria;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

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

    public function getTravelPassengersCount(string|Uuid|Travel $uuid): int
    {
        if ($uuid instanceof Travel) {
            $uuid = $uuid->getUuid();
        }

        $uuid = $this->toUuid($uuid);

        if (!$uuid) {
            return 0;
        }

        return (int) $this->createQueryBuilder('t')
            ->select('COALESCE(SUM(cp.slots), 0) AS passengersCount')
            ->leftJoin('t.carpoolers', 'cp')
            ->where('t.uuid = :travelUuid')
            ->setParameter('travelUuid', $uuid)
            ->groupBy('t.uuid')
            ->getQuery()
            ->getSingleScalarResult();
    }

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
            // Sum all slots reserved by carpoolers for accurate passenger count
            ->addSelect('COALESCE(SUM(cp.slots), 0) AS currentPassengers')
            ->addSelect('(t.passengersMax - COALESCE(SUM(cp.slots), 0)) AS availablePlaces')
            ->addSelect('DATE_ADD(t.date, t.duration, \'MINUTE\') AS arrivalDateTime')
            ->addSelect('d.uuid AS driverUuid, d.username AS driverUsername, d.ratingAverage AS driverRating')
            ->addSelect('tp.isSmokingAllowed AS isSmokingAllowed, tp.isPetsAllowed AS isPetsAllowed, tp.luggageSize AS luggageSize')
            ->innerJoin('t.driver', 'd')
            ->innerJoin('t.travelPreference', 'tp')
            ->innerJoin('t.car', 'c')
            ->leftJoin('t.carpoolers', 'cp')
            ->where('t.state = :state')
            ->andWhere('t.departure = :departure')
            ->andWhere('t.arrival = :arrival')
            ->andWhere('t.date BETWEEN :dateMin AND :dateMax')
            ->groupBy('t.uuid, d.uuid, t.date, t.duration, t.cost, t.passengersMax, c.fuelType')
            ->having('availablePlaces >= :minPassengers')
            ->orderBy('CASE WHEN c.fuelType = :electric THEN 0 ELSE 1 END', 'ASC')
            ->addOrderBy('t.date', 'ASC')
            ->setMaxResults(10)
            ->setFirstResult($firstResult)
            ->setParameter('state', TravelStateEnum::PENDING)
            ->setParameter('departure', $criteria->getDeparture())
            ->setParameter('arrival', $criteria->getArrival())
            ->setParameter('dateMin', $criteria->getDateTime()->format('Y-m-d H:i:s'))
            ->setParameter('dateMax', $criteria->getDateTime()->format('Y-m-d 23:59:59'))
            ->setParameter('minPassengers', $criteria->getMinPassengers())
            ->setParameter('electric', FuelTypeEnum::ELECTRIC);

        $this->applyFilters($criteria, $query);

        return $query->getQuery()->getResult();
    }

    /**
     * Used if getTravelsByCriteria() is called when no results are found.
     * Get the nearest (date) travels by criteria.
     * 
     * @param TravelCriteria $criteria The search criteria
     * @return DateTimeImmutable|null The nearest travel date matching the criteria, null if no travel found
     */
    public function getNearestTravelDateByCriteria(TravelCriteria $criteria): ?DateTimeImmutable
    {
        $query = $this->createQueryBuilder('t')
            ->select('t.date')
            ->addSelect('COALESCE(SUM(cp.slots), 0) AS usedSlots')
            ->innerJoin('t.driver', 'd')
            ->innerJoin('t.travelPreference', 'tp')
            ->innerJoin('t.car', 'c')
            ->leftJoin('t.carpoolers', 'cp')
            ->where('t.state = :state')
            ->andWhere('t.departure = :departure')
            ->andWhere('t.arrival = :arrival')
            ->andWhere('t.date > :currentDate')
            ->groupBy('t.uuid, t.passengersMax, t.date')
            ->having('(t.passengersMax - usedSlots) >= :minPassengers')
            ->orderBy('t.date', 'ASC')
            ->setMaxResults(1)
            ->setParameter('state', TravelStateEnum::PENDING)
            ->setParameter('departure', $criteria->getDeparture())
            ->setParameter('arrival', $criteria->getArrival())
            ->setParameter('currentDate', $criteria->getDateTime()->format('Y-m-d H:i:s'))
            ->setParameter('minPassengers', $criteria->getMinPassengers());

        $this->applyFilters($criteria, $query);

        $result = $query->getQuery()->getOneOrNullResult();

        return $result ? $result['date'] : null;
    }

    private function applyFilters(TravelCriteria $criteria, QueryBuilder $query): void {
        if ($criteria->isElectricPreferred()) {
            $query->andWhere('c.fuelType = :electricFuelType')
                ->setParameter('electricFuelType', FuelTypeEnum::ELECTRIC);
        }

        if ($criteria->isSmokingAllowed()) {
            $query->andWhere('tp.isSmokingAllowed = true');
        }

        if ($criteria->isPetsAllowed()) {
            $query->andWhere('tp.isPetsAllowed = true');
        }

        // Only apply filters with no default values
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
    }

    /**
     * Count travels matching the criteria (for pagination)
     * 
     * @param TravelCriteria $criteria The search criteria
     * @return int Total number of travels matching the criteria
     */
    public function countTravelsByCriteria(TravelCriteria $criteria): int
    {
        $query = $this->createQueryBuilder('t')
            ->select('t.uuid', 't.passengersMax')
            ->addSelect('COALESCE(SUM(cp.slots), 0) as usedSlots')
            ->innerJoin('t.driver', 'd')
            ->innerJoin('t.travelPreference', 'tp')
            ->innerJoin('t.car', 'c')
            ->leftJoin('t.carpoolers', 'cp')
            ->where('t.state = :state')
            ->andWhere('t.departure = :departure')
            ->andWhere('t.arrival = :arrival')
            ->andWhere('t.date BETWEEN :dateMin AND :dateMax')
            ->groupBy('t.uuid, t.passengersMax')
            ->setParameter('state', TravelStateEnum::PENDING)
            ->setParameter('departure', $criteria->getDeparture())
            ->setParameter('arrival', $criteria->getArrival())
            ->setParameter('dateMin', $criteria->getDateTime()->format('Y-m-d H:i:s'))
            ->setParameter('dateMax', $criteria->getDateTime()->format('Y-m-d 23:59:59'));
        
        $this->applyFilters($criteria, $query);

        // Filter by available places using HAVING clause
        if ($criteria->getMinPassengers() > 0) {
            $query->having('(t.passengersMax - usedSlots) >= :minPassengers')
                ->setParameter('minPassengers', $criteria->getMinPassengers());
        }

        $results = $query->getQuery()->getResult();
        
        return count($results);
    }

    /**
     * Get all carpoolers for a specific travel
     */
    public function getCarpoolers(string|Uuid $uuid): array
    {
        $uuid = $this->toUuid($uuid);

        if (!$uuid) {
            return [];
        }

        return $this->createQueryBuilder('t')
            ->select('tu.uuid as passengerUuid, tu.username as passengerUsername, tu.ratingAverage as passengerRating')
            ->innerJoin('t.carpoolers', 'tu')
            ->where('t.uuid = :travelUuid')
            ->setParameter('travelUuid', $uuid)
            ->orderBy('tu.username', 'ASC')
            ->getQuery()->getResult();
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

    /**
     * Find all Travels involving a specific User as driver or carpooler
     * Ordered by date descending
     * @param User $user
     * @return array<Travel> Returns an array of Travel objects
     */
    public function findTravelsInvolvingUser(User $user, int $page): array
    {
        $firstResult = max($page - 1, 0) * 10;
        return $this->createQueryBuilder('t')
            ->leftJoin('t.carpoolers', 'cp')
            ->where('t.driver = :user')
            ->orWhere('cp = :user')
            ->setParameter('user', $user)
            ->orderBy('t.date', 'DESC')
            ->setMaxResults(10)
            ->setFirstResult($firstResult)
            ->getQuery()
            ->getResult()
        ;
    }

    public function CountTravelsInvolvingUser(User $user): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(DISTINCT t.uuid)')
            ->leftJoin('t.carpoolers', 'cp')
            ->where('t.driver = :user')
            ->orWhere('cp = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
