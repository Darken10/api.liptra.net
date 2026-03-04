<?php

declare(strict_types=1);

namespace App\Enums;

enum PassengerRelation: string
{
    case Self = 'self';
    case Spouse = 'spouse';
    case Child = 'child';
    case Parent = 'parent';
    case Sibling = 'sibling';
    case Friend = 'friend';
    case Colleague = 'colleague';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Self => 'Moi-même',
            self::Spouse => 'Conjoint(e)',
            self::Child => 'Enfant',
            self::Parent => 'Parent',
            self::Sibling => 'Frère/Sœur',
            self::Friend => 'Ami(e)',
            self::Colleague => 'Collègue',
            self::Other => 'Autre',
        };
    }
}
