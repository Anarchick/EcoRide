<?php
namespace App\Enum;

enum TravelStateEnum: int
{
    case CANCELLED = 0;
    case COMPLETED = 1;
    case PENDING = 2;
    case FULL = 3;
    case IN_PROGRESS = 4;
}