<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

final class CitySeeder extends Seeder
{
    /**
     * @var array<int, array{name: string, region: string, latitude: float, longitude: float}>
     */
    private array $cities = [
        ['name' => 'Ouagadougou', 'region' => 'Centre', 'latitude' => 12.3714, 'longitude' => -1.5197],
        ['name' => 'Bobo-Dioulasso', 'region' => 'Hauts-Bassins', 'latitude' => 11.1771, 'longitude' => -4.2979],
        ['name' => 'Koudougou', 'region' => 'Centre-Ouest', 'latitude' => 12.2533, 'longitude' => -2.3628],
        ['name' => 'Banfora', 'region' => 'Cascades', 'latitude' => 10.6308, 'longitude' => -4.7547],
        ['name' => 'Ouahigouya', 'region' => 'Nord', 'latitude' => 13.5824, 'longitude' => -2.4187],
        ['name' => 'Kaya', 'region' => 'Centre-Nord', 'latitude' => 13.0910, 'longitude' => -1.0842],
        ['name' => 'Tenkodogo', 'region' => 'Centre-Est', 'latitude' => 11.7799, 'longitude' => -0.3697],
        ['name' => 'Fada N\'Gourma', 'region' => 'Est', 'latitude' => 12.0606, 'longitude' => 0.3489],
        ['name' => 'Dédougou', 'region' => 'Boucle du Mouhoun', 'latitude' => 12.4631, 'longitude' => -3.4613],
        ['name' => 'Ziniaré', 'region' => 'Plateau-Central', 'latitude' => 12.5833, 'longitude' => -1.3000],
        ['name' => 'Manga', 'region' => 'Centre-Sud', 'latitude' => 11.6667, 'longitude' => -1.0667],
        ['name' => 'Gaoua', 'region' => 'Sud-Ouest', 'latitude' => 10.3250, 'longitude' => -3.1750],
        ['name' => 'Dori', 'region' => 'Sahel', 'latitude' => 14.0333, 'longitude' => -0.0333],
        ['name' => 'Léo', 'region' => 'Centre-Ouest', 'latitude' => 11.1000, 'longitude' => -2.1000],
        ['name' => 'Pô', 'region' => 'Centre-Sud', 'latitude' => 11.1667, 'longitude' => -1.1500],
    ];

    public function run(): void
    {
        foreach ($this->cities as $city) {
            City::query()->updateOrCreate(
                ['name' => $city['name']],
                $city,
            );
        }
    }
}
