<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class UniqueEmail extends Constraint
{
    public const DEFAULT_MESSAGE = 'Cette adresse email est déjà utilisée.';

    public string $message = self::DEFAULT_MESSAGE;

    public function __construct(
        ?string $message = null,
        ?array $groups = null,
        mixed $payload = null
    ) {
        parent::__construct([], $groups, $payload);
        
        $this->message = $message ?? self::DEFAULT_MESSAGE;
    }

    public function getTargets(): string
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
