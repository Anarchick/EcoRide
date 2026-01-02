<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class UniquePlate extends Constraint
{
    public string $message = 'Cette plaque d\'immatriculation est déjà enregistrée.';

    public function validatedBy(): string
    {
        return static::class.'Validator';
    }
}
