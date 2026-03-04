<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Booking>
 */
final class BookingFactory extends Factory
{
    protected $model = Booking::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'trip_id' => Trip::factory(),
            'booking_reference' => mb_strtoupper(Str::random(8)),
            'total_amount' => fake()->randomElement([3000, 5000, 7500, 10000, 15000]),
            'payment_status' => PaymentStatus::Pending,
            'payment_method' => null,
            'payment_reference' => null,
            'paid_at' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'payment_status' => PaymentStatus::Completed,
            'payment_method' => fake()->randomElement(PaymentMethod::cases()),
            'payment_reference' => 'PAY-' . Str::random(12),
            'paid_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'payment_status' => PaymentStatus::Failed,
        ]);
    }
}
