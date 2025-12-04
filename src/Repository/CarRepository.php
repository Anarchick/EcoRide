<?php

namespace App\Repository;

use App\Entity\Car;
use App\Repository\Trait\UuidFinderTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Car>
 */
class CarRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Car::class);
    }

    use UuidFinderTrait;

    /**
     * Find a car by its plate using hash lookup (optimized for encrypted field)
     * 
     * @param string $plate The plate to search for
     * @return Car|null
     */
    public function findOneByPlate(string $plate): ?Car
    {
        $plateHash = hash('sha256', strtoupper($plate));
        
        return $this->findOneBy(['plateHash' => $plateHash]);
    }

//    /**
//     * @return Car[] Returns an array of Car objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Car
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
