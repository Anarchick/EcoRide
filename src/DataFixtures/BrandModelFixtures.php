<?php

namespace App\DataFixtures;

use App\Entity\Brand;
use App\Entity\Model;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Faker\Provider\FakeCar;

class BrandModelFixtures extends Fixture
{
    public const BATCH_SIZE = 10;
    private Generator $faker;

    public function __construct() {
        $this->faker = Factory::create('fr_FR');
        $this->faker->addProvider(new FakeCar($this->faker));
    }

    // create brands with models
    public function load(ObjectManager $manager): void
    {
        $brandsNames = [];

        while (count($brandsNames) < 10) {
            $brandsNames[] = $this->faker->vehicleBrand();
            $brandsNames = array_unique($brandsNames);
        }

        for ($i=0; $i < self::BATCH_SIZE; $i++) {
            $brand = new Brand();
            $model = new Model();

            $brandName = $brandsNames[$i];
            $brand->setName($brandName)->addModel($model);
            $this->addReference('brand_' . $i, $brand);

            $modelName = $this->faker->vehicleModel();
            $model->setName($modelName)->setBrand($brand);

            $manager->persist($brand);
            $manager->persist($model);
            $this->addReference('brand_model_' . $i, $model);
        }

        $manager->flush();
    }
}
