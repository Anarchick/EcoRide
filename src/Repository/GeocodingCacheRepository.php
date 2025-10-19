<?php

namespace App\Repository;

use App\Document\GeocodingCache;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

/**
 * @extends DocumentRepository<GeocodingCache>
 */
class GeocodingCacheRepository extends DocumentRepository
{
    /**
     * Find a valid (non-expired) cache entry for a city
     * 
     * @param string $cityName The normalized city name
     * @return GeocodingCache|null The cached entry or null if not found/expired
     */
    public function findOneValidCache(string $cityName): ?GeocodingCache
    {
        return $this->createQueryBuilder()
            ->field('cityName')->equals($cityName)
            ->field('expiresAt')->gte(new \DateTime())
            ->getQuery()
            ->getSingleResult();
    }

    /**
     * Count expired cache entries (for maintenance)
     * 
     * @return int Number of expired entries
     */
    public function countExpiredEntries(): int
    {
        return $this->createQueryBuilder()
            ->field('expiresAt')->lt(new \DateTime())
            ->count()
            ->getQuery()
            ->execute();
    }

    /**
     * Find all cache entries for a specific city (including expired)
     * Useful for debugging and cache analysis
     * 
     * @param string $cityName The normalized city name
     * @return array<GeocodingCache>
     */
    public function findAllForCity(string $cityName): array
    {
        return $this->createQueryBuilder()
            ->field('cityName')->equals($cityName)
            ->sort('createdAt', 'DESC')
            ->getQuery()
            ->execute()
            ->toArray();
    }
}
