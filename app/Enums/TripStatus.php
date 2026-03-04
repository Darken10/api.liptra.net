<?php

declare(strict_types=1);

namespace App\Enums;

enum TripStatus: string
{
    case Scheduled = 'scheduled';
    case Boarding = 'boarding';
    case Departed = 'departed';
    case Arrived = 'arrived';
    case Cancelled = 'cancelled';
    case Delayed = 'delayed';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Programmé',
            self::Boarding => 'Embarquement',
            self::Departed => 'En route',
            self::Arrived => 'Arrivé',
            self::Cancelled => 'Annulé',
            self::Delayed => 'Retardé',
        };
    }
}
