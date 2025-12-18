<?php

namespace App\Repository;

use App\Entity\PlatformCommission;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlatformCommission>
 */
class PlatformCommissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlatformCommission::class);
    }

    /**
     * Get 
     * 
     */
    public function getSumByDay(DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        return $this->createQueryBuilder('t')
            ->select("DATE(t.createdAt) AS period, SUM(t.credits) AS totalCredits")
            ->where('t.type = :type')
            ->setParameter('type', 'earn')
            ->groupBy('period')
            ->orderBy('period', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
