<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Uid\Uuid as Uid;
use Symfony\Component\Validator\Constraints\Uuid;
use Symfony\Component\Validator\Validation;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Find a User by their email (plain text).
     * @return User|null Returns a User object or null
     */
    public function findOneByEmail(string $email): ?User
    {
        $emailHash = hash('sha256', $email);
        return $this->findOneBy(['emailHash' => $emailHash]);
    }

    /**
     * Find a User by their UUID.
     * Accepts both 32-character string (without dashes) and standard UUID format (with dashes).
     * Returns null if the UUID is invalid or no user is found.
     * @return User|null
     */
    public function getUserByUuid(string|Uid $uuid): ?User
    {
        if (is_string($uuid)) {
            if (strlen($uuid) == 32) {
                $uuid = substr($uuid, 0, 8) . '-'
                . substr($uuid, 8, 4) . '-'
                . substr($uuid, 12, 4) . '-'
                . substr($uuid, 16, 4) . '-'
            . substr($uuid, 20);
            }
        }

        $validator = Validation::createValidator();
        $errors = $validator->validate($uuid, new Uuid());

        if (count($errors) > 0) {
            return null;
        }

        return $this->findOneBy(['uuid' => $uuid]);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
