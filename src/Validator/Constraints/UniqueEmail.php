<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class UniqueEmail extends Constraint
{
    public string $message = 'Cet email est déjà utilisé.';

    public function getTargets(): string
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
