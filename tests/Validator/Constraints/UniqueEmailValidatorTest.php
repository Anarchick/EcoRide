<?php

namespace App\Tests\Validator\Constraints;

use App\Repository\UserRepository;
use App\Validator\Constraints\UniqueEmailValidator;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class UniqueEmailValidatorTest extends ConstraintValidatorTestCase
{
    // TODO
    protected function createValidator(): ConstraintValidatorInterface
    {
        $userRepositoryMock = $this->createMock(UserRepository::class);
        return new UniqueEmailValidator($userRepositoryMock);
    }

}