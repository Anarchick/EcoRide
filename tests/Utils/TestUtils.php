<?php
namespace App\Tests\Utils;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class TestUtils
{

    public function __construct(
        private ContainerInterface $container
    ) {}

    public function createUser(
        string $firstName = 'John',
        string $lastName = 'Doe',
        string $username = 'johndoe',
        string $email = 'johndoe@test.com',
        string $phone = '+33612345678',
        string $password = '2]~4t.C6=pqN23'
    ): User
    {
        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = $this->container->get('security.user_password_hasher');

        $user = new User();
        $user->setFirstName($firstName)
            ->setLastName($lastName)
            ->setUsername($username)
            ->setEmail($email)
            // ->setEmailHash(hash('sha256', $email))
            ->setPhone($phone)
            ->setPassword($passwordHasher->hashPassword($user, $password));

        return $user;
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->container->get('doctrine')->getManager();
    }

    public function getUserRepository(): UserRepository
    {
        return $this->getEntityManager()->getRepository(User::class);
    }

    public function getSecurityTokenStorage(): TokenStorageInterface
    {
        return $this->container->get('security.token_storage');
    }

    public function purgeDatabase(): void
    {
        $em = $this->getEntityManager();
        $purger = new ORMPurger($em);

        $purger->purge();
        $em->clear();
    }

}