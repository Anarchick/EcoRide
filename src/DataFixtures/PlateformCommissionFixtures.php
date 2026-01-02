<?php

namespace App\DataFixtures;

use App\Entity\PlatformCommission;
use App\Entity\Travel;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;

class PlateformCommissionFixtures extends Fixture implements DependentFixtureInterface
{
    private Generator $faker;

    public function __construct() {
        $this->faker = Factory::create('fr_FR');
    }

    public function getDependencies(): array
    {
        return [
            TravelFixtures::class,
            CarpoolFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $i = 0;

        while ($i < 2000) {

            if (!$this->hasReference('travel_' .$i, Travel::class)) {
                break;
            }

            /** @var Travel $travel */
            $travel = $this->getReference('travel_' . $i, Travel::class);

            $travelCarpoolers = $travel->getCarpoolers();

            if (count($travelCarpoolers) === 0) {
                $i++;
                continue;
            }

            foreach ($travelCarpoolers as $carpooler) {
                $platformCommission = (new PlatformCommission())
                    ->setCreateAt($travel->getDate())
                    ->setTravel($travel)
                    ->setCarpooler($carpooler)
                    ->setCredits(2)
                    ->setComment(null);
                $manager->persist($platformCommission);
            }

            $manager->flush();
            $i++;
        }
    }
}
