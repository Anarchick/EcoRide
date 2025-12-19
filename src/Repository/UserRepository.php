<?php

namespace App\Repository;

use App\Entity\User;
use App\Enum\RoleEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

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
     * Find Users by their role.
     * @return array<User> Returns an array of User objects
     */
    public function findByRole(RoleEnum $role): array
    {
        return $this->createQueryBuilder('u')
            ->innerJoin('u.roles', 'r')
            ->where('r.role = :role')
            ->setParameter('role', $role)
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
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

    /**
     * Find banned users with their ban information
     * @return array<int, array{uuid: string, username: string, createAt: \DateTimeImmutable, reason: string}>
     */
    public function findBannedUsers(int $page): array
    {
        $firstResult = max($page - 1, 0) * 10;
        return $this->createQueryBuilder('u')
            ->select('u.uuid', 'u.username', 'u.avatarUrl', 'ub.createAt AS bannedAt', 'ub.reason')
            ->innerJoin('u.userBan', 'ub')
            ->innerJoin('u.roles', 'r')
            ->where('r.role = :role')
            ->setParameter('role', RoleEnum::BANNED)
            ->orderBy('ub.createAt', 'DESC')
            ->setMaxResults(10)
            ->setFirstResult($firstResult)
            ->getQuery()
            ->getResult()
        ;
    }

    public function countBannedUsers(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.uuid)')
            ->innerJoin('u.roles', 'r')
            ->where('r.role = :role')
            ->setParameter('role', RoleEnum::BANNED)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
