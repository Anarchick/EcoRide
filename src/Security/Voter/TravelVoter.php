<?php

namespace App\Security\Voter;

use App\Entity\Travel;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class TravelVoter extends Voter
{
    public const EDIT = 'TRAVEL_EDIT';
    public const REMOVE = 'TRAVEL_REMOVE';
    public const UNBOOK = 'TRAVEL_UNBOOK';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::REMOVE, self::UNBOOK])
            && $subject instanceof Travel;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if (!$subject instanceof Travel) {
            return false;
        }

        if ($user->isModerator()) {
            return true;
        }

        switch ($attribute) {
            case self::EDIT:
                return $subject->getDriver()->getUuid() === $user->getUuid();
                break;
            case self::REMOVE:
                return $subject->getDriver()->getUuid() === $user->getUuid();
                break;
            case self::UNBOOK:
                return $subject->isCarpooler($user)
                        && $subject->getDriver()->getUuid() !== $user->getUuid()
                        && $subject->getState()->isStarted() === false;
        }

        return false;
    }
}
