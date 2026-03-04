<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Company>
 */
final class CompanyFactory extends Factory
{
    protected $model = Company::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(4),
            'description' => fake()->paragraph(),
            'logo' => null,
            'email' => fake()->companyEmail(),
            'phone' => '+226' . fake()->numerify('7#######'),
            'phone_secondary' => '+226' . fake()->numerify('7#######'),
            'address' => fake()->address(),
            'city' => 'Ouagadougou',
            'license_number' => 'BF-' . fake()->numerify('####-####'),
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
