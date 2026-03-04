<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\City;
use App\Models\Company;
use App\Models\Station;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Station>
 */
final class StationFactory extends Factory
{
    protected $model = Station::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'city_id' => City::factory(),
            'company_id' => Company::factory(),
            'name' => 'Gare ' . fake()->word(),
            'address' => fake()->address(),
            'phone' => '+226' . fake()->numerify('7#######'),
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
