<?php
namespace App\Enum;

enum DateIntervalEnum: string
{
    case DAY = 'day';
    case WEEK = 'week';
    case MONTH = 'month';
    case YEAR = 'year';
}
