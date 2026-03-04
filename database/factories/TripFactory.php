<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TripStatus;
use App\Models\Bus;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Route;
use App\Models\Station;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trip>
 */
final class TripFactory extends Factory
{
    protected $model = Trip::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $departureAt = fake()->dateTimeBetween('+1 day', '+30 days');
        $durationMinutes = fake()->numberBetween(120, 600);

        return [
            'company_id' => Company::factory(),
            'route_id' => Route::factory(),
            'bus_id' => Bus::factory(),
            'driver_id' => Driver::factory(),
            'departure_station_id' => Station::factory(),
            'arrival_station_id' => Station::factory(),
            'departure_at' => $departureAt,
            'estimated_arrival_at' => (clone \DateTimeImmutable::createFromMutable($departureAt))->modify("+{$durationMinutes} minutes"),
            'actual_departure_at' => null,
            'actual_arrival_at' => null,
            'price' => fake()->randomElement([3000, 5000, 7500, 10000, 12500, 15000, 20000]),
            'available_seats' => fake()->numberBetween(10, 50),
            'status' => TripStatus::Scheduled,
            'notes' => null,
            'is_active' => true,
        ];
    }

    public function departed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TripStatus::Departed,
            'departure_at' => now()->subHours(2),
            'actual_departure_at' => now()->subHours(2),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TripStatus::Cancelled,
        ]);
    }

    public function full(): static
    {
        return $this->state(fn (array $attributes): array => [
            'available_seats' => 0,
        ]);
    }

    public function past(): static
    {
        return $this->state(fn (array $attributes): array => [
            'departure_at' => fake()->dateTimeBetween('-30 days', '-1 day'),
            'status' => TripStatus::Arrived,
        ]);
    }
}
