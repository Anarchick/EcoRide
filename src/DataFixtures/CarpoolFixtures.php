<?php

namespace App\DataFixtures;

use App\Entity\Travel;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;

class CarpoolFixtures extends Fixture implements DependentFixtureInterface
{
    private Generator $faker;

    public function __construct() {
        $this->faker = Factory::create('fr_FR');
    }

    public function getDependencies(): array
    {
        return [
            TravelFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $j = 0; // travel counter for reference
        /** @var Travel $travel */
        $travel = null;

        do {
            $j++;

            if (!$this->hasReference('travel_' .$j, Travel::class)) {
                break;
            }

            $travel = $this->getReference('travel_' .$j, Travel::class);

            for ($i = 0; $i < UserFixtures::BATCH_SIZE; $i++) {
                /** @var User $user */
                $user = $this->getReference('user_' . $i, User::class);

                if ($travel->getDriver() === $user) {
                    continue; // A driver cannot be a carpooler in their own travel
                }

                if ($this->faker->boolean(100)) {
                    $validatedSlots = $travel->getAvailableSlots() -1;

                    if ($validatedSlots <= 0) {
                        break; // No available slots
                    }

                    $slots = $this->faker->numberBetween(1, $travel->getAvailableSlots());
                    // Simple cost calculation: cost per slot to avoid user not having enough credits
                    $cost = $slots;

                    try {
                        $carpool = $travel->join($user, $slots, $cost);

                        if ($carpool === null) {
                            continue; // Skip if join failed
                        }
                        
                        $manager->persist($carpool);
                    } catch (\InvalidArgumentException $e) {
                        // printf($e->getMessage() . PHP_EOL);
                        continue; // Skip if any validation fails
                    }
                    
                }

            }

            $manager->persist($travel);

        } while ($travel != null);
        
        $manager->flush();
    }
}