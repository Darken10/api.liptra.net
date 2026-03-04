<?php

declare(strict_types=1);

use App\Enums\PassengerRelation;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\TicketStatus;
use App\Enums\TripStatus;
use App\Models\Booking;
use App\Models\Bus;
use App\Models\City;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Route as RouteModel;
use App\Models\Station;
use App\Models\Trip;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createBookingTripSetup(): Trip
{
    $company = Company::factory()->create();
    $departure = City::factory()->create();
    $arrival = City::factory()->create();
    $departureStation = Station::factory()->create(['city_id' => $departure->id, 'company_id' => $company->id]);
    $arrivalStation = Station::factory()->create(['city_id' => $arrival->id, 'company_id' => $company->id]);
    $route = RouteModel::factory()->create([
        'company_id' => $company->id,
        'departure_city_id' => $departure->id,
        'arrival_city_id' => $arrival->id,
    ]);
    $bus = Bus::factory()->create(['company_id' => $company->id, 'total_seats' => 50]);
    $driver = Driver::factory()->create(['company_id' => $company->id]);

    return Trip::factory()->create([
        'company_id' => $company->id,
        'route_id' => $route->id,
        'bus_id' => $bus->id,
        'driver_id' => $driver->id,
        'departure_station_id' => $departureStation->id,
        'arrival_station_id' => $arrivalStation->id,
        'departure_at' => now()->addDays(2),
        'price' => 5000,
        'available_seats' => 30,
        'status' => TripStatus::Scheduled,
        'is_active' => true,
    ]);
}

describe('Booking Creation', function (): void {
    it('creates a booking with passengers', function (): void {
        $trip = createBookingTripSetup();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/bookings', [
            'trip_id' => $trip->id,
            'passengers' => [
                [
                    'passenger_firstname' => 'Amadou',
                    'passenger_lastname' => 'Ouédraogo',
                    'passenger_phone' => '+22670123456',
                    'passenger_relation' => PassengerRelation::Self->value,
                ],
            ],
            'payment_method' => PaymentMethod::OrangeMoney->value,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'booking_reference',
                    'total_amount',
                    'payment_status',
                    'tickets',
                ],
            ]);

        $this->assertDatabaseHas('bookings', [
            'user_id' => $user->id,
            'trip_id' => $trip->id,
            'total_amount' => 5000,
        ]);
    });

    it('creates multiple tickets for multiple passengers', function (): void {
        $trip = createBookingTripSetup();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/bookings', [
            'trip_id' => $trip->id,
            'passengers' => [
                [
                    'passenger_firstname' => 'Amadou',
                    'passenger_lastname' => 'Ouédraogo',
                    'passenger_phone' => '+22670123456',
                    'passenger_relation' => PassengerRelation::Self->value,
                ],
                [
                    'passenger_firstname' => 'Fatimata',
                    'passenger_lastname' => 'Ouédraogo',
                    'passenger_phone' => '+22670123457',
                    'passenger_relation' => PassengerRelation::Spouse->value,
                ],
            ],
            'payment_method' => PaymentMethod::MoovMoney->value,
        ]);

        $response->assertStatus(201);

        $bookingId = $response->json('data.id');
        $this->assertDatabaseCount('tickets', 2);
        expect($response->json('data.total_amount'))->toBe(10000);
    });

    it('rejects booking if not enough seats', function (): void {
        $trip = createBookingTripSetup();
        $trip->update(['available_seats' => 1]);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/bookings', [
            'trip_id' => $trip->id,
            'passengers' => [
                [
                    'passenger_firstname' => 'Amadou',
                    'passenger_lastname' => 'Ouédraogo',
                    'passenger_phone' => '+22670123456',
                    'passenger_relation' => PassengerRelation::Self->value,
                ],
                [
                    'passenger_firstname' => 'Fatimata',
                    'passenger_lastname' => 'Ouédraogo',
                    'passenger_phone' => '+22670123457',
                    'passenger_relation' => PassengerRelation::Spouse->value,
                ],
            ],
            'payment_method' => PaymentMethod::OrangeMoney->value,
        ]);

        $response->assertStatus(400);
    });

    it('requires authentication', function (): void {
        $trip = createBookingTripSetup();

        $response = $this->postJson('/api/v1/bookings', [
            'trip_id' => $trip->id,
            'passengers' => [
                [
                    'passenger_firstname' => 'Amadou',
                    'passenger_lastname' => 'Ouédraogo',
                    'passenger_phone' => '+22670123456',
                    'passenger_relation' => PassengerRelation::Self->value,
                ],
            ],
            'payment_method' => PaymentMethod::OrangeMoney->value,
        ]);

        $response->assertUnauthorized();
    });
});

describe('Booking Listing', function (): void {
    it('lists my bookings', function (): void {
        $user = User::factory()->create();
        $trip = createBookingTripSetup();

        Booking::factory()->count(3)->create([
            'user_id' => $user->id,
            'trip_id' => $trip->id,
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/bookings');

        $response->assertSuccessful();
        expect($response->json('data.data'))->toHaveCount(3);
    });

    it('does not show other users bookings', function (): void {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $trip = createBookingTripSetup();

        Booking::factory()->create(['user_id' => $otherUser->id, 'trip_id' => $trip->id]);

        $response = $this->actingAs($user)->getJson('/api/v1/bookings');

        $response->assertSuccessful();
        expect($response->json('data.data'))->toHaveCount(0);
    });
});

describe('Booking Cancellation', function (): void {
    it('cancels a booking', function (): void {
        $user = User::factory()->create();
        $trip = createBookingTripSetup();

        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'trip_id' => $trip->id,
            'payment_status' => PaymentStatus::Completed,
        ]);

        $response = $this->actingAs($user)->postJson("/api/v1/bookings/{$booking->id}/cancel");

        $response->assertSuccessful();
    });
});
