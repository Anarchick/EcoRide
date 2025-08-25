<?php
namespace App\Enum;

enum TravelStateEnum: string
{
    case PENDING = 'pending';
    case FULL = 'full';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}