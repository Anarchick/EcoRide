<?php

namespace App\DataFixtures;

use App\Entity\Car;
use App\Entity\Travel;
use App\Entity\TravelPreference;
use App\Entity\User;
use App\Enum\LuggageSizeEnum;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;

class TravelFixtures extends Fixture implements DependentFixtureInterface
{

    private const CITIES = [
        "Paris",
        "Lyon",
        // "Marseille",
        // "Toulouse",
        // "Nice",
        // "Nantes",
        // "Strasbourg",
        // "Montpellier",
        // "Bordeaux",
        // "Lille",
    ];

    private Generator $faker;

    public function __construct() {
        $this->faker = Factory::create('fr_FR');
    }

    public function getDependencies(): array
    {
        return [
            DriverFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < UserFixtures::BATCH_SIZE; $i++) {
            $departure = self::CITIES[array_rand(self::CITIES)];
            // arrival without departure
            $arrival = self::CITIES[array_rand(self::CITIES)];
            while ($arrival === $departure) {
                $arrival = self::CITIES[array_rand(self::CITIES)];
            }

            $user = $this->getReference('user_' . $i, User::class);
            /** @var Car $car */
            $car = $user->getCars()->first();
            $passengersmax = $car->getTotalSeats() -1;

            // create between 0 and 1 travel by day between -31 days and +31 days
            for ($day = -31; $day < 31; $day++) {

                // Skip some days randomly
                if ($this->faker->boolean(15)) {
                    continue;
                }

                $travel = new Travel();
                $travelPreference = new TravelPreference();

                $hour = $this->faker->numberBetween(0, 23);
                $minutes = $this->faker->numberBetween(0, 59);
                $dateTime = (new DateTimeImmutable())
                    ->modify($day . ' days')
                    ->setTime($hour, $minutes);

                $travel->setDeparture($departure)
                    ->setArrival($arrival)
                    ->setDate($dateTime)
                    ->setCost($this->faker->numberBetween(350, 500))
                    ->setPassengersMax($this->faker->numberBetween(1, $passengersmax))
                    ->setCar($car)
                    ->setDriver($user)
                    ->setDistance($this->faker->numberBetween(450, 530))
                    ->setDuration($this->faker->numberBetween(60*4, 60*5))
                    ->setTravelPreference($travelPreference);

                $travelPreference->setLuggageSize(LuggageSizeEnum::cases()[array_rand(LuggageSizeEnum::cases())])
                    ->setComment($this->faker->paragraph())
                    ->setTravel($travel);

                $manager->persist($travel);
                $manager->persist($travelPreference);
            }
        }

        $manager->flush();
    }
}
