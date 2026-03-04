<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<City>
 */
final class CityFactory extends Factory
{
    protected $model = City::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $burkinaCities = [
            ['name' => 'Ouagadougou', 'region' => 'Centre'],
            ['name' => 'Bobo-Dioulasso', 'region' => 'Hauts-Bassins'],
            ['name' => 'Koudougou', 'region' => 'Centre-Ouest'],
            ['name' => 'Banfora', 'region' => 'Cascades'],
            ['name' => 'Ouahigouya', 'region' => 'Nord'],
            ['name' => 'Kaya', 'region' => 'Centre-Nord'],
            ['name' => 'Tenkodogo', 'region' => 'Centre-Est'],
            ['name' => 'Fada N\'Gourma', 'region' => 'Est'],
            ['name' => 'Dédougou', 'region' => 'Boucle du Mouhoun'],
            ['name' => 'Ziniaré', 'region' => 'Plateau-Central'],
        ];

        $city = fake()->randomElement($burkinaCities);

        return [
            'name' => $city['name'],
            'region' => $city['region'],
            'latitude' => fake()->latitude(9.5, 15.0),
            'longitude' => fake()->longitude(-5.5, 2.5),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
