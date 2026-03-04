<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Driver;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Driver>
 */
final class DriverFactory extends Factory
{
    protected $model = Driver::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'phone' => '+226' . fake()->numerify('7#######'),
            'license_number' => 'BF-P-' . fake()->numerify('######'),
            'license_type' => fake()->randomElement(['B', 'C', 'D', 'E']),
            'license_expiry' => fake()->dateTimeBetween('+1 month', '+5 years'),
            'photo' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function expiredLicense(): static
    {
        return $this->state(fn (array $attributes): array => [
            'license_expiry' => fake()->dateTimeBetween('-2 years', '-1 day'),
        ]);
    }
}
