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
use App\Models\Ticket;
use App\Models\Trip;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createTicketWithBooking(?User $user = null): array
{
    $user = $user ?? User::factory()->create();
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
    $bus = Bus::factory()->create(['company_id' => $company->id]);
    $driver = Driver::factory()->create(['company_id' => $company->id]);

    $trip = Trip::factory()->create([
        'company_id' => $company->id,
        'route_id' => $route->id,
        'bus_id' => $bus->id,
        'driver_id' => $driver->id,
        'departure_station_id' => $departureStation->id,
        'arrival_station_id' => $arrivalStation->id,
        'departure_at' => now()->addDays(2),
        'status' => TripStatus::Scheduled,
        'is_active' => true,
    ]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'trip_id' => $trip->id,
        'payment_status' => PaymentStatus::Completed,
    ]);

    $ticket = Ticket::factory()->create([
        'booking_id' => $booking->id,
        'trip_id' => $trip->id,
        'status' => TicketStatus::Paid,
        'passenger_phone' => '+22670123456',
    ]);

    return compact('user', 'trip', 'booking', 'ticket');
}

describe('Ticket Listing', function (): void {
    it('lists my tickets', function (): void {
        $data = createTicketWithBooking();

        $response = $this->actingAs($data['user'])->getJson('/api/v1/tickets');

        $response->assertSuccessful()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => ['id', 'ticket_number', 'status', 'passenger_full_name'],
                    ],
                ],
            ]);
    });

    it('shows a single ticket', function (): void {
        $data = createTicketWithBooking();

        $response = $this->actingAs($data['user'])->getJson("/api/v1/tickets/{$data['ticket']->id}");

        $response->assertSuccessful()
            ->assertJsonPath('data.id', $data['ticket']->id);
    });
});

describe('Ticket Validation', function (): void {
    it('validates a ticket by QR code', function (): void {
        $data = createTicketWithBooking();
        $agent = User::factory()->create();
        test()->seed(RolePermissionSeeder::class);
        $agent->assignRole('agent');

        $response = $this->actingAs($agent)->postJson('/api/v1/tickets/validate', [
            'qr_code_data' => $data['ticket']->qr_code_data,
        ]);

        $response->assertSuccessful();
        expect($data['ticket']->fresh()->status)->toBe(TicketStatus::Validated);
    });

    it('validates a ticket by code and phone', function (): void {
        $data = createTicketWithBooking();
        $agent = User::factory()->create();
        test()->seed(RolePermissionSeeder::class);
        $agent->assignRole('agent');

        $response = $this->actingAs($agent)->postJson('/api/v1/tickets/validate', [
            'validation_code' => $data['ticket']->validation_code,
            'phone' => $data['ticket']->passenger_phone,
        ]);

        $response->assertSuccessful();
        expect($data['ticket']->fresh()->status)->toBe(TicketStatus::Validated);
    });

    it('forbids regular user from validating tickets', function (): void {
        $data = createTicketWithBooking();
        test()->seed(RolePermissionSeeder::class);
        $data['user']->assignRole('user');

        $response = $this->actingAs($data['user'])->postJson('/api/v1/tickets/validate', [
            'qr_code_data' => $data['ticket']->qr_code_data,
        ]);

        $response->assertForbidden();
    });
});

describe('Ticket Boarding', function (): void {
    it('boards a validated ticket', function (): void {
        $data = createTicketWithBooking();
        $data['ticket']->update(['status' => TicketStatus::Validated]);

        $agent = User::factory()->create();
        test()->seed(RolePermissionSeeder::class);
        $agent->assignRole('agent');

        $response = $this->actingAs($agent)->postJson("/api/v1/tickets/{$data['ticket']->id}/board");

        $response->assertSuccessful();
        expect($data['ticket']->fresh()->status)->toBe(TicketStatus::Boarded);
    });
});

describe('Ticket Baggage', function (): void {
    it('checks baggage for a boarded ticket', function (): void {
        $data = createTicketWithBooking();
        $data['ticket']->update(['status' => TicketStatus::Boarded]);

        $bagagiste = User::factory()->create();
        test()->seed(RolePermissionSeeder::class);
        $bagagiste->assignRole('bagagiste');

        $response = $this->actingAs($bagagiste)->postJson("/api/v1/tickets/{$data['ticket']->id}/baggage");

        $response->assertSuccessful();
        expect($data['ticket']->fresh()->baggage_checked)->toBeTrue();
    });
});

describe('Ticket Find by Number', function (): void {
    it('finds a ticket by its number', function (): void {
        $data = createTicketWithBooking();

        $agent = User::factory()->create();
        test()->seed(RolePermissionSeeder::class);
        $agent->assignRole('agent');

        $response = $this->actingAs($agent)->getJson("/api/v1/tickets/find/{$data['ticket']->ticket_number}");

        $response->assertSuccessful()
            ->assertJsonPath('data.ticket_number', $data['ticket']->ticket_number);
    });
});
