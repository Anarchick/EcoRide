<?php

namespace App\DataFixtures;

use App\Entity\Transaction;
use App\Entity\User;
use App\Enum\CurrencyEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Faker\Provider\FakeCar;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class TransactionFixtures extends Fixture implements DependentFixtureInterface
{

    private Generator $faker;

    public function __construct()
    {
        $this->faker = Factory::create('fr_FR');
        $this->faker->addProvider(new FakeCar($this->faker));
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
    // Add some transactions to each user
    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < UserFixtures::BATCH_SIZE; $i++) {
            $user = $this->getReference('user_' . $i, User::class);
            
            for ($j = 0; $j < $this->faker->numberBetween(1, 5); $j++) {
                $price = $this->faker->numberBetween(5, 50);
                $credits = $price * 10;
                $transaction = (new Transaction())
                    ->setUser($user)
                    ->setCredits($credits)
                    ->setPrice($price)
                    ->setCurrency(CurrencyEnum::EUR)
                    ->setComment($this->faker->boolean(70) ? $this->faker->sentence(6, true) : null);
                $user->addCredits($credits);
                $manager->persist($transaction);
            }

            $manager->persist($user);
            $manager->flush();
        }
    }
}
