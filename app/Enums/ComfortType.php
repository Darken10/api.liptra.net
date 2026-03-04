<?php

declare(strict_types=1);

namespace App\Enums;

enum ComfortType: string
{
    case Vip = 'vip';
    case Classic = 'classique';
    case Ordinary = 'ordinaire';

    public function label(): string
    {
        return match ($this) {
            self::Vip => 'VIP',
            self::Classic => 'Classique',
            self::Ordinary => 'Ordinaire',
        };
    }
}
