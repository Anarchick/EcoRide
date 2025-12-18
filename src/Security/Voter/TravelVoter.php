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
    public const START = 'TRAVEL_START';
    public const COMPLETE = 'TRAVEL_COMPLETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::REMOVE, self::UNBOOK, self::START, self::COMPLETE])
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

        $isDriver = $user->isDriver();
        $isCarpooler = $subject->isCarpooler($user);
        $isModerator = $user->isModerator();

        return match ($attribute) {
            self::EDIT => $isDriver || $isModerator,
            self::REMOVE => $isModerator,
            self::UNBOOK => $isCarpooler && !$isDriver && $subject->getState()->isStarted() === false,
            self::START => $isDriver,
            self::COMPLETE => $isDriver,
            default => false,
        };
    }
}
