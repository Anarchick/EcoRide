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

    public function getCreditSum(?DateTimeImmutable $from = null, ?DateTimeImmutable $to = null): int
    {
        $qb = $this->createQueryBuilder('pc')
            ->select('SUM(pc.credits) as credits');

        if ($from !== null && $to !== null) {
            $qb->where('pc.createAt BETWEEN :from AND :to')
                ->setParameter('from', $from)
                ->setParameter('to', $to);
        }
        
        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Get The sum of credits earned by the platform grouped by day.
     * Returns 0 credits for days without commissions.
     * @return array<int, array{date: string, credits: int}>
     */
    public function getSumByDay(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $results = $this->createQueryBuilder('pc')
            ->select("DATE_FORMAT(pc.createAt, '%Y-%m-%d') AS date, SUM(pc.credits) AS credits")
            ->where('pc.createAt BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->getQuery()
            ->getResult();

        // Create a map of existing data
        $dataMap = [];
        foreach ($results as $row) {
            $dataMap[$row['date']] = (int) $row['credits'];
        }

        // Fill missing days with 0 credits
        $output = [];
        $currentDate = $from;
        while ($currentDate <= $to) {
            $dateStr = $currentDate->format('Y-m-d');
            $output[] = [
                'date' => $dateStr,
                'credits' => $dataMap[$dateStr] ?? 0,
            ];
            $currentDate = $currentDate->modify('+1 day');
        }

        return $output;
    }
}
