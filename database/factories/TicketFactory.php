<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PassengerRelation;
use App\Enums\TicketStatus;
use App\Models\Booking;
use App\Models\Ticket;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Ticket>
 */
final class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'trip_id' => Trip::factory(),
            'ticket_number' => mb_strtoupper(Str::random(8)),
            'validation_code' => (string) fake()->numberBetween(100000, 999999),
            'qr_code_data' => Str::uuid()->toString(),
            'seat_number' => (string) fake()->numberBetween(1, 50),
            'passenger_firstname' => fake()->firstName(),
            'passenger_lastname' => fake()->lastName(),
            'passenger_phone' => '+226' . fake()->numerify('7#######'),
            'passenger_email' => fake()->optional()->safeEmail(),
            'passenger_relation' => PassengerRelation::Self,
            'status' => TicketStatus::Pending,
            'validated_by' => null,
            'validated_at' => null,
            'boarded_by' => null,
            'boarded_at' => null,
            'baggage_checked' => false,
            'baggage_checked_by' => null,
            'baggage_checked_at' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TicketStatus::Paid,
        ]);
    }

    public function validated(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TicketStatus::Validated,
            'validated_at' => now(),
        ]);
    }

    public function boarded(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TicketStatus::Boarded,
            'boarded_at' => now(),
        ]);
    }

    public function forSomeoneElse(): static
    {
        return $this->state(fn (array $attributes): array => [
            'passenger_relation' => fake()->randomElement([
                PassengerRelation::Spouse,
                PassengerRelation::Child,
                PassengerRelation::Friend,
                PassengerRelation::Other,
            ]),
        ]);
    }
}
