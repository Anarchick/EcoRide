<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Enum\RoleEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ModeratorFixtures extends Fixture
{
    public const BATCH_SIZE = 10;
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

        for ($i=0; $i < self::BATCH_SIZE; $i++) {
            $user = new User();
            $user->setFirstName($this->faker->firstName())
                ->setLastName($this->faker->lastName())
                ->setUsername('moderator_' . $i)
                ->setPhone(sprintf('+33 6 %02d %02d %02d %02d', $i, $i, $i, $i))
                ->setEmail('moderator' . $i . '@ecoride.com')
                ->setPassword($this->passwordHasher->hashPassword($user, $password))
                ->setBio($this->faker->paragraph())
                ->addRole(RoleEnum::MODERATOR);

            $manager->persist($user);
            $this->addReference('moderator_' . $i, $user);
        }

        // Add a specific user for tests
        $user = new User();
        $user->setFirstName('Jules')
            ->setLastName('Verne')
            ->setUsername('Jules.Verne')
            ->setPhone('+33 6 12 34 56 79')
            ->setEmail('modo.jv@ecoride.com')
            ->setPassword($this->passwordHasher->hashPassword($user, $password))
            ->setBio($this->faker->paragraph())
            ->addRole(RoleEnum::MODERATOR);
        $manager->persist($user);

        $manager->flush();
    }
}
