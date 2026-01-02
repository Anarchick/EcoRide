<?php

namespace App\Validator;

use App\Entity\Car;
use App\Repository\CarRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniquePlateValidator extends ConstraintValidator
{
    public function __construct(
        private CarRepository $carRepository
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniquePlate) {
            throw new UnexpectedTypeException($constraint, UniquePlate::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        // Get the Car entity being validated
        $car = $this->context->getObject();
        
        if (!$car instanceof Car) {
            return;
        }

        // Search for existing car with same plate (will search in encrypted field)
        $existingCar = $this->carRepository->findOneByPlate($value);

        // If a car exists and it's not the current car being edited
        if ($existingCar) {
            // Try to get current car's UUID (will be null for new entities)
            try {
                $currentUuid = $car->getUuid();
            } catch (\Error) {
                // UUID not initialized yet (new entity), so any existing car is a duplicate
                $currentUuid = null;
            }
            
            // If UUIDs are different or current car is new, it's a duplicate
            if ($existingCar->getUuid() !== $currentUuid) {
                $this->context->buildViolation($constraint->message)
                    ->addViolation();
            }
        }
    }
}
