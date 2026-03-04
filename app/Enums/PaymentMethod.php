<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    case OrangeMoney = 'orange_money';
    case MoovMoney = 'moov_money';
    case CorisMoneyPlus = 'coris_money_plus';
    case Wave = 'wave';
    case Cash = 'cash';
    case Card = 'card';

    public function label(): string
    {
        return match ($this) {
            self::OrangeMoney => 'Orange Money',
            self::MoovMoney => 'Moov Money',
            self::CorisMoneyPlus => 'Coris Money+',
            self::Wave => 'Wave',
            self::Cash => 'Espèces',
            self::Card => 'Carte bancaire',
        };
    }
}
