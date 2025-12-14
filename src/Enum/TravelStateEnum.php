<?php
namespace App\Enum;

enum TravelStateEnum: int
{
    case CANCELLED = 0;
    case COMPLETED = 1;
    case PENDING = 2;
    case FULL = 3;
    case IN_PROGRESS = 4;

    public function isStarted(): bool
    {
        return $this === self::IN_PROGRESS || $this === self::COMPLETED || $this === self::CANCELLED;
    }
}