<?php

namespace App\Repository;

use App\Entity\Car;
use App\Entity\Travel;
use App\Enum\TravelStateEnum;
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

    /**
     * Find all Travel associated to a Car that are not completed or cancelled
     * 
     * @param Car $car
     * @return array<Travel> Returns an array of Travel objects
     */
    public function findActiveTravelsFromCar(Car $car): array
    {
        return $this->getEntityManager()->getRepository(Travel::class)
            ->createQueryBuilder('t')
            ->andWhere('t.car = :car')
            ->andWhere('t.state BETWEEN :pending AND :inProgress')
            ->setParameter('car', $car)
            ->setParameter('pending', TravelStateEnum::PENDING->value)
            ->setParameter('inProgress', TravelStateEnum::IN_PROGRESS->value)
            ->orderBy('t.departure', 'ASC')
            ->getQuery()
            ->getResult()
        ;
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
