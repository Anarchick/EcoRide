<?php

namespace App\Repository;

use App\Document\RouteCache;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

/**
 * @extends DocumentRepository<RouteCache>
 */
class RouteCacheRepository extends DocumentRepository
{
    public function __construct(DocumentManager $dm)
    {
        parent::__construct($dm, $dm->getUnitOfWork(), $dm->getClassMetadata(RouteCache::class));
    }

    /**
     * Find a valid cached route between two cities
     * Cities are normalized to lowercase for case-insensitive lookup
     */
    public function findByCities(string $departureCity, string $arrivalCity): ?RouteCache
    {
        $normalizedDeparture = strtolower(trim($departureCity));
        $normalizedArrival = strtolower(trim($arrivalCity));

        /** @var RouteCache|null $cache */
        $cache = $this->findOneBy([
            'departureCity' => $normalizedDeparture,
            'arrivalCity' => $normalizedArrival,
        ]);

        if ($cache && !$cache->isValid()) {
            return null;
        }

        return $cache;
    }

    /**
     * Find a valid cached route, checking both forward and reverse directions
     * OSRM routes may differ slightly by direction, but for simplicity we can reuse
     */
    public function findBidirectional(string $departureCity, string $arrivalCity, bool $allowReverse = false): ?RouteCache
    {
        // Try forward direction first
        $cache = $this->findByCities($departureCity, $arrivalCity);
        
        if ($cache !== null) {
            return $cache;
        }

        // Try reverse direction if allowed
        if ($allowReverse) {
            return $this->findByCities($arrivalCity, $departureCity);
        }

        return null;
    }

    public function countExpiredEntries(): int
    {
        $now = new \DateTime();

        return $this->createQueryBuilder()
            ->count()
            ->field('expiresAt')->lt($now)
            ->getQuery()
            ->execute();
    }

    /**
     * Remove all expired cache entries
     * 
     * @return int Number of deleted entries
     */
    public function deleteExpiredEntries(): int
    {
        $now = new \DateTime();
        $count = $this->countExpiredEntries();

        if ($count > 0) {
            $this->createQueryBuilder()
                ->remove()
                ->field('expiresAt')->lt($now)
                ->getQuery()
                ->execute();
        }

        return $count;
    }

    /**
     * Get all cached routes for a specific departure city
     * Useful for analytics or cache warming
     * 
     * @return RouteCache[] Array of cached routes
     */
    public function findByDepartureCity(string $departureCity): array
    {
        $normalized = strtolower(trim($departureCity));

        return $this->createQueryBuilder()
            ->field('departureCity')->equals($normalized)
            ->field('expiresAt')->gt(new \DateTime())
            ->getQuery()
            ->execute()
            ->toArray();
    }

    /**
     * Get cache statistics
     * Returns useful metrics for monitoring
     * 
     * @return array{total: int, expired: int, valid: int}
     */
    public function getStatistics(): array
    {
        $total = $this->createQueryBuilder()->count()->getQuery()->execute();
        $expired = $this->countExpiredEntries();
        
        return [
            'total' => $total,
            'expired' => $expired,
            'valid' => $total - $expired,
        ];
    }
}
