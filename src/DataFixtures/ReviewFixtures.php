<?php

namespace App\DataFixtures;

use App\Entity\Review;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;

class ReviewFixtures extends Fixture implements DependentFixtureInterface
{

    private Generator $faker;

    public function __construct()
    {
        $this->faker = Factory::create('fr_FR');
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }

    // Add some reviews to each user
    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < UserFixtures::BATCH_SIZE; $i++) {
            $author = $this->getReference('user_' . $i, User::class);

            for ($j = 0; $j < UserFixtures::BATCH_SIZE; $j++) {

                if ($i === $j) {
                    continue;
                }

                $user = $this->getReference('user_' . $j, User::class);

                $review = (new Review())
                    ->setAuthor($author)
                    ->setUser($user)
                    ->setRate($this->faker->numberBetween(1, 5))
                    ->setContent($this->faker->boolean(80) ? $this->faker->sentence(30, true) : null);
                $manager->persist($review);
            }

            $manager->flush();
        }
    }
}
