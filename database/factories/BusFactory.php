<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ComfortType;
use App\Models\Bus;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bus>
 */
final class BusFactory extends Factory
{
    protected $model = Bus::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'registration_number' => fake()->numerify('BF-####-##'),
            'brand' => fake()->randomElement(['Mercedes', 'Toyota', 'Yutong', 'Golden Dragon', 'King Long']),
            'model' => fake()->word(),
            'total_seats' => fake()->randomElement([30, 45, 50, 60, 70]),
            'comfort_type' => fake()->randomElement(ComfortType::cases()),
            'manufacture_year' => fake()->numberBetween(2015, 2025),
            'color' => fake()->colorName(),
            'has_air_conditioning' => fake()->boolean(60),
            'has_wifi' => fake()->boolean(30),
            'has_usb_charging' => fake()->boolean(40),
            'has_toilet' => fake()->boolean(20),
            'photo' => null,
            'mileage' => fake()->numberBetween(10000, 500000),
            'is_active' => true,
        ];
    }

    public function vip(): static
    {
        return $this->state(fn (array $attributes): array => [
            'comfort_type' => ComfortType::Vip,
            'has_air_conditioning' => true,
            'has_wifi' => true,
            'has_usb_charging' => true,
            'total_seats' => 30,
        ]);
    }

    public function ordinary(): static
    {
        return $this->state(fn (array $attributes): array => [
            'comfort_type' => ComfortType::Ordinary,
            'has_air_conditioning' => false,
            'has_wifi' => false,
            'has_usb_charging' => false,
            'total_seats' => 70,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
