<?php
namespace App\Tests\Utils;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;

class TestUtils
{
    public static function createUser(
        string $firstName = 'John',
        string $lastName = 'Doe',
        string $username = 'johndoe',
        string $email = 'johndoe@test.com',
        string $phone = '+33612345678',
        string $password = '2]~4t.C6=pqN23'
    ): User
    {
        $user = new User();
        $user->setFirstName($firstName)
            ->setLastName($lastName)
            ->setUsername($username)
            ->setEmail($email)
            ->setEmailHash(hash('sha256', $email))
            ->setPhone($phone)
            ->setPassword($password);

        return $user;
    }

    public static function purgeDatabase(EntityManagerInterface $em): void
    {
        $purger = new ORMPurger($em);

        $purger->purge();
        $em->clear();
    }

    public static function getUserRepository(EntityManagerInterface $em): UserRepository
    {
        return $em->getRepository(User::class);
    }
}