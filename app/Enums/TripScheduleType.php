<?php

declare(strict_types=1);

namespace App\Enums;

enum TripScheduleType: string
{
    case OneTime = 'one_time';
    case Daily = 'daily';
    case Weekly = 'weekly';

    public function label(): string
    {
        return match ($this) {
            self::OneTime => 'Voyage unique',
            self::Daily => 'Quotidien',
            self::Weekly => 'Hebdomadaire',
        };
    }
}
