<?php

namespace App\DataFixtures;

use App\Entity\Brand;
use App\Entity\Car;
use App\Entity\Model;
use App\Entity\User;
use App\Enum\ColorEnum;
use App\Enum\FuelTypeEnum;
use App\Enum\RoleEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Faker\Provider\FakeCar;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class DriverFixtures extends Fixture implements DependentFixtureInterface
{

    private Generator $faker;

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
        $this->faker = Factory::create('fr_FR');
        $this->faker->addProvider(new FakeCar($this->faker));
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            BrandModelFixtures::class,
        ];
    }

    // Add a car and a driver role to each user
    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < UserFixtures::BATCH_SIZE; $i++) {
            $user = $this->getReference('user_' . $i, User::class);
            $modelId = $this->faker->numberBetween(0, BrandModelFixtures::BATCH_SIZE - 1);
            $brand = $this->getReference('brand_' . $modelId, Brand::class);
            $model = $this->getReference('brand_model_' . $modelId, Model::class);

            $car = (new Car())
                ->setBrand($brand)
                ->setModel($model)
                ->setColor(ColorEnum::cases()[array_rand(ColorEnum::cases())])
                ->setFuelType(FuelTypeEnum::cases()[array_rand(FuelTypeEnum::cases())])
                ->setPlate($this->faker->vehicleRegistration('[A-Z]{2}-[0-9]{3}-[A-Z]{2}'))
                ->setTotalSeats($this->faker->numberBetween(2, 7))
                ->addUser($user);
            $this->addReference('car_' . $i, $car);

            $user->addCar($car)->addRole(RoleEnum::DRIVER);
            $manager->persist($car);
        }

        $manager->flush();
    }
}
