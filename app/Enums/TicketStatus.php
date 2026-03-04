<?php

declare(strict_types=1);

namespace App\Enums;

enum TicketStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Validated = 'validated';
    case Boarded = 'boarded';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Paid => 'Payé',
            self::Validated => 'Validé',
            self::Boarded => 'Embarqué',
            self::Cancelled => 'Annulé',
            self::Expired => 'Expiré',
            self::Refunded => 'Remboursé',
        };
    }
}
