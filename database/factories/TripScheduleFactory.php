<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TripScheduleType;
use App\Models\Bus;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Route;
use App\Models\Station;
use App\Models\TripSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TripSchedule>
 */
final class TripScheduleFactory extends Factory
{
    protected $model = TripSchedule::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'route_id' => Route::factory(),
            'bus_id' => Bus::factory(),
            'driver_id' => Driver::factory(),
            'departure_station_id' => Station::factory(),
            'arrival_station_id' => Station::factory(),
            'schedule_type' => TripScheduleType::Daily,
            'departure_times' => ['08:00', '12:00'],
            'days_of_week' => null,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(3)->toDateString(),
            'one_time_departure_at' => null,
            'estimated_duration_minutes' => fake()->numberBetween(120, 480),
            'price' => fake()->randomElement([3000, 5000, 7500, 10000, 15000]),
            'notes' => null,
            'is_active' => true,
        ];
    }

    public function oneTime(): static
    {
        return $this->state(fn (array $attributes): array => [
            'schedule_type' => TripScheduleType::OneTime,
            'departure_times' => ['08:00'],
            'one_time_departure_at' => now()->addDays(3)->setHour(8)->setMinute(0),
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => null,
            'days_of_week' => null,
        ]);
    }

    public function daily(): static
    {
        return $this->state(fn (array $attributes): array => [
            'schedule_type' => TripScheduleType::Daily,
            'departure_times' => ['08:00', '10:00', '14:00'],
            'one_time_departure_at' => null,
            'days_of_week' => null,
        ]);
    }

    public function weekly(): static
    {
        return $this->state(fn (array $attributes): array => [
            'schedule_type' => TripScheduleType::Weekly,
            'departure_times' => ['05:00'],
            'days_of_week' => [1, 3, 5], // Monday, Wednesday, Friday
            'one_time_departure_at' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
