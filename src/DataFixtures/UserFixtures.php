<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public const BATCH_SIZE = 10;
    private Generator $faker;
    private const AVATARS = [
        "https://res.cloudinary.com/dd7wacbqq/image/upload/v1765568381/ecoride/avatars/user_1f0d6c98-1815-689e-9948-cd45c32788d5.jpg",
        null,
    ];

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
        $this->faker = Factory::create('fr_FR');
    }

    // Add users
    public function load(ObjectManager $manager): void
    {
        $password = $_ENV['FIXTURE_PASSWORD'] ?? null;

        if ($password === 'ChangeMe!' || $password === null) {
            throw new \RuntimeException('The FIXTURE_PASSWORD environment variable is not set.');
        }

        for ($i=0; $i < self::BATCH_SIZE; $i++) { 
            $avatar = self::AVATARS[array_rand(self::AVATARS)];

            $user = new User();
            $user->setFirstName($this->faker->firstName())
                ->setLastName($this->faker->lastName())
                ->setUsername($this->faker->userName())
                ->setPhone('+33 6 12 34 56 ' . sprintf('%02d', $i))
                ->setEmail($i . '@gmail.com')
                ->setPassword($this->passwordHasher->hashPassword($user, $password))
                ->setBio($this->faker->paragraph())
                ->setRatingAverage($this->faker->randomFloat(1, 0, 5))
                ->setAvatarUrl($avatar);
            $manager->persist($user);
            $this->addReference('user_' . $i, $user);
        }

        $manager->flush();
    }
}
