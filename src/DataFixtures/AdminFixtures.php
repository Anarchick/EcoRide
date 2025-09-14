<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Enum\RoleEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminFixtures extends Fixture
{

    public const ADMIN = 'admin';
    private Generator $faker;

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
        $this->faker = Factory::create('fr_FR');
    }

    public function load(ObjectManager $manager): void
    {
        $password = $_ENV['FIXTURE_ADMIN_PASSWORD'] ?? null;

        if ($password === 'ChangeMe!' || $password === null) {
            throw new \RuntimeException('The FIXTURE_ADMIN_PASSWORD environment variable is not set.');
        }

        $user = new User();
        $user->setFirstName($this->faker->firstName())
            ->setLastName($this->faker->lastName())
            ->setUsername('admin')
            ->setPhone('+33 6 06 06 06 06')
            ->setEmail('admin@ecoride.com')
            ->setPassword($this->passwordHasher->hashPassword($user, $password))
            ->setBio($this->faker->paragraph())
            ->addRole(RoleEnum::ADMIN);

        $manager->persist($user);
        $this->addReference(self::ADMIN, $user);
        $manager->flush();
    }
}
