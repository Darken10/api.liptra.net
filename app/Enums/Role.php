<?php

declare(strict_types=1);

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'super-admin';
    case Admin = 'admin';
    case ChefGare = 'chef-gare';
    case Agent = 'agent';
    case Bagagiste = 'bagagiste';
    case User = 'user';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Administrateur',
            self::Admin => 'Administrateur Compagnie',
            self::ChefGare => 'Chef de Gare',
            self::Agent => 'Agent',
            self::Bagagiste => 'Bagagiste',
            self::User => 'Utilisateur',
        };
    }
}
