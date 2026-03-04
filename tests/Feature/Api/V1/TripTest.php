<?php

declare(strict_types=1);

use App\Enums\ComfortType;
use App\Enums\TripStatus;
use App\Models\Bus;
use App\Models\City;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Route as RouteModel;
use App\Models\Station;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createTripSetup(): array
{
    $company = Company::factory()->create();
    $departure = City::factory()->create(['name' => 'Ouagadougou']);
    $arrival = City::factory()->create(['name' => 'Bobo-Dioulasso']);
    $departureStation = Station::factory()->create(['city_id' => $departure->id, 'company_id' => $company->id]);
    $arrivalStation = Station::factory()->create(['city_id' => $arrival->id, 'company_id' => $company->id]);
    $route = RouteModel::factory()->create([
        'company_id' => $company->id,
        'departure_city_id' => $departure->id,
        'arrival_city_id' => $arrival->id,
    ]);
    $bus = Bus::factory()->create(['company_id' => $company->id, 'total_seats' => 50]);
    $driver = Driver::factory()->create(['company_id' => $company->id]);

    return compact('company', 'departure', 'arrival', 'departureStation', 'arrivalStation', 'route', 'bus', 'driver');
}

describe('Trip Search', function (): void {
    it('lists upcoming available trips', function (): void {
        $setup = createTripSetup();

        Trip::factory()->count(3)->create([
            'company_id' => $setup['company']->id,
            'route_id' => $setup['route']->id,
            'bus_id' => $setup['bus']->id,
            'driver_id' => $setup['driver']->id,
            'departure_station_id' => $setup['departureStation']->id,
            'arrival_station_id' => $setup['arrivalStation']->id,
            'departure_at' => now()->addDays(2),
            'status' => TripStatus::Scheduled,
            'available_seats' => 30,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/trips');

        $response->assertSuccessful()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => ['id', 'departure_at', 'price', 'available_seats', 'status'],
                    ],
                ],
            ]);
    });

    it('filters trips by departure and arrival city', function (): void {
        $setup = createTripSetup();

        Trip::factory()->create([
            'company_id' => $setup['company']->id,
            'route_id' => $setup['route']->id,
            'bus_id' => $setup['bus']->id,
            'driver_id' => $setup['driver']->id,
            'departure_station_id' => $setup['departureStation']->id,
            'arrival_station_id' => $setup['arrivalStation']->id,
            'departure_at' => now()->addDays(2),
            'status' => TripStatus::Scheduled,
            'available_seats' => 30,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/trips?' . http_build_query([
            'departure_city_id' => $setup['departure']->id,
            'arrival_city_id' => $setup['arrival']->id,
        ]));

        $response->assertSuccessful();

        $data = $response->json('data.data');
        expect($data)->toHaveCount(1);
    });

    it('shows a single trip', function (): void {
        $setup = createTripSetup();

        $trip = Trip::factory()->create([
            'company_id' => $setup['company']->id,
            'route_id' => $setup['route']->id,
            'bus_id' => $setup['bus']->id,
            'driver_id' => $setup['driver']->id,
            'departure_station_id' => $setup['departureStation']->id,
            'arrival_station_id' => $setup['arrivalStation']->id,
            'departure_at' => now()->addDays(2),
            'status' => TripStatus::Scheduled,
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/v1/trips/{$trip->id}");

        $response->assertSuccessful()
            ->assertJsonPath('data.id', $trip->id);
    });

    it('returns not found for non-existent trip', function (): void {
        $response = $this->getJson('/api/v1/trips/non-existent-uuid');

        $response->assertNotFound();
    });
});

describe('Trip Management', function (): void {
    it('allows admin to create trip', function (): void {
        $setup = createTripSetup();
        $user = User::factory()->create();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $user->assignRole('admin');

        $response = $this->actingAs($user)->postJson('/api/v1/trips', [
            'route_id' => $setup['route']->id,
            'bus_id' => $setup['bus']->id,
            'driver_id' => $setup['driver']->id,
            'departure_station_id' => $setup['departureStation']->id,
            'arrival_station_id' => $setup['arrivalStation']->id,
            'departure_at' => now()->addDays(3)->toISOString(),
            'price' => 5000,
            'available_seats' => 40,
        ]);

        $response->assertStatus(201);
    });

    it('forbids regular user from creating trip', function (): void {
        $setup = createTripSetup();
        $user = User::factory()->create();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $user->assignRole('user');

        $response = $this->actingAs($user)->postJson('/api/v1/trips', [
            'route_id' => $setup['route']->id,
            'bus_id' => $setup['bus']->id,
            'driver_id' => $setup['driver']->id,
            'departure_station_id' => $setup['departureStation']->id,
            'arrival_station_id' => $setup['arrivalStation']->id,
            'departure_at' => now()->addDays(3)->toISOString(),
            'price' => 5000,
            'available_seats' => 40,
        ]);

        $response->assertForbidden();
    });
});
