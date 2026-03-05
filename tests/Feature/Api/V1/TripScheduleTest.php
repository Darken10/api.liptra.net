<?php

declare(strict_types=1);

use App\Models\Bus;
use App\Models\City;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Route as RouteModel;
use App\Models\Station;
use App\Models\Trip;
use App\Models\TripSchedule;
use App\Models\User;
use App\Services\TripScheduleService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createScheduleSetup(): array
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

function createAdminUser(Company $company): User
{
    test()->seed(RolePermissionSeeder::class);
    app()[Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    $user = User::factory()->create();
    $user->assignRole('admin');
    $company->users()->attach($user->id, ['role' => 'admin']);

    return $user;
}

describe('TripScheduleService', function (): void {
    it('generates a one-time trip', function (): void {
        $setup = createScheduleSetup();
        $schedule = TripSchedule::factory()->oneTime()->create([
            'company_id' => $setup['company']->id,
            'route_id' => $setup['route']->id,
            'bus_id' => $setup['bus']->id,
            'driver_id' => $setup['driver']->id,
            'departure_station_id' => $setup['departureStation']->id,
            'arrival_station_id' => $setup['arrivalStation']->id,
        ]);

        $service = new TripScheduleService;
        $trips = $service->generateTrips($schedule);

        expect($trips)->toHaveCount(1);
        expect($trips[0]->trip_schedule_id)->toBe($schedule->id);
        expect(Trip::query()->where('trip_schedule_id', $schedule->id)->count())->toBe(1);
    });

    it('generates daily trips for a date range', function (): void {
        $setup = createScheduleSetup();
        $schedule = TripSchedule::factory()->daily()->create([
            'company_id' => $setup['company']->id,
            'route_id' => $setup['route']->id,
            'bus_id' => $setup['bus']->id,
            'driver_id' => $setup['driver']->id,
            'departure_station_id' => $setup['departureStation']->id,
            'arrival_station_id' => $setup['arrivalStation']->id,
            'departure_times' => ['08:00', '14:00'],
            'start_date' => today()->toDateString(),
            'end_date' => today()->addDays(30)->toDateString(),
        ]);

        $from = today()->addDay();
        $to = today()->addDays(3);

        $service = new TripScheduleService;
        $trips = $service->generateTrips($schedule, $from, $to);

        // 3 days × 2 times = 6 trips
        expect($trips)->toHaveCount(6);
    });

    it('generates weekly trips only on specified days', function (): void {
        $setup = createScheduleSetup();

        // Find the next Monday
        $startMonday = today()->next(Carbon\Carbon::MONDAY);

        $schedule = TripSchedule::factory()->weekly()->create([
            'company_id' => $setup['company']->id,
            'route_id' => $setup['route']->id,
            'bus_id' => $setup['bus']->id,
            'driver_id' => $setup['driver']->id,
            'departure_station_id' => $setup['departureStation']->id,
            'arrival_station_id' => $setup['arrivalStation']->id,
            'departure_times' => ['05:00'],
            'days_of_week' => [1], // Monday only
            'start_date' => $startMonday->toDateString(),
            'end_date' => $startMonday->copy()->addWeeks(2)->toDateString(),
        ]);

        $service = new TripScheduleService;
        $trips = $service->generateTrips($schedule, $startMonday, $startMonday->copy()->addWeeks(2));

        // 3 Mondays × 1 time = 3 trips
        expect($trips)->toHaveCount(3);

        foreach ($trips as $trip) {
            expect($trip->departure_at->dayOfWeekIso)->toBe(1);
        }
    });

    it('does not generate trips for inactive schedules', function (): void {
        $setup = createScheduleSetup();
        $schedule = TripSchedule::factory()->daily()->inactive()->create([
            'company_id' => $setup['company']->id,
            'route_id' => $setup['route']->id,
            'bus_id' => $setup['bus']->id,
            'driver_id' => $setup['driver']->id,
            'departure_station_id' => $setup['departureStation']->id,
            'arrival_station_id' => $setup['arrivalStation']->id,
        ]);

        $service = new TripScheduleService;
        $trips = $service->generateTrips($schedule);

        expect($trips)->toHaveCount(0);
    });

    it('does not duplicate trips on repeated generation', function (): void {
        $setup = createScheduleSetup();
        $schedule = TripSchedule::factory()->daily()->create([
            'company_id' => $setup['company']->id,
            'route_id' => $setup['route']->id,
            'bus_id' => $setup['bus']->id,
            'driver_id' => $setup['driver']->id,
            'departure_station_id' => $setup['departureStation']->id,
            'arrival_station_id' => $setup['arrivalStation']->id,
            'departure_times' => ['08:00'],
            'start_date' => today()->toDateString(),
            'end_date' => today()->addDays(10)->toDateString(),
        ]);

        $from = today()->addDay();
        $to = today()->addDays(3);

        $service = new TripScheduleService;
        $first = $service->generateTrips($schedule, $from, $to);
        $second = $service->generateTrips($schedule, $from, $to);

        expect($first)->toHaveCount(3);
        expect($second)->toHaveCount(0);
        expect(Trip::query()->where('trip_schedule_id', $schedule->id)->count())->toBe(3);
    });

    it('sets available seats from bus total seats', function (): void {
        $setup = createScheduleSetup();
        $schedule = TripSchedule::factory()->oneTime()->create([
            'company_id' => $setup['company']->id,
            'route_id' => $setup['route']->id,
            'bus_id' => $setup['bus']->id,
            'driver_id' => $setup['driver']->id,
            'departure_station_id' => $setup['departureStation']->id,
            'arrival_station_id' => $setup['arrivalStation']->id,
        ]);

        $service = new TripScheduleService;
        $trips = $service->generateTrips($schedule);

        expect($trips[0]->available_seats)->toBe(50);
    });
});

describe('Trip Schedule Admin API', function (): void {
    it('lists trip schedules', function (): void {
        $setup = createScheduleSetup();
        $user = createAdminUser($setup['company']);

        TripSchedule::factory()->count(3)->create([
            'company_id' => $setup['company']->id,
            'route_id' => $setup['route']->id,
            'bus_id' => $setup['bus']->id,
            'driver_id' => $setup['driver']->id,
            'departure_station_id' => $setup['departureStation']->id,
            'arrival_station_id' => $setup['arrivalStation']->id,
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/admin/trip-schedules');

        $response->assertSuccessful();
        $response->assertJsonPath('data.data', fn ($data) => count($data) === 3);
    });

    it('creates a daily trip schedule', function (): void {
        $setup = createScheduleSetup();
        $user = createAdminUser($setup['company']);

        $response = $this->actingAs($user)->postJson('/api/v1/admin/trip-schedules', [
            'company_id' => $setup['company']->id,
            'route_id' => $setup['route']->id,
            'bus_id' => $setup['bus']->id,
            'driver_id' => $setup['driver']->id,
            'departure_station_id' => $setup['departureStation']->id,
            'arrival_station_id' => $setup['arrivalStation']->id,
            'schedule_type' => 'daily',
            'departure_times' => ['08:00', '12:00'],
            'start_date' => today()->addDay()->toDateString(),
            'end_date' => today()->addMonth()->toDateString(),
            'price' => 5000,
            'estimated_duration_minutes' => 240,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('trip_schedules', [
            'schedule_type' => 'daily',
            'price' => 5000,
        ]);
    });

    it('creates a weekly schedule and validates days_of_week required', function (): void {
        $setup = createScheduleSetup();
        $user = createAdminUser($setup['company']);

        $response = $this->actingAs($user)->postJson('/api/v1/admin/trip-schedules', [
            'company_id' => $setup['company']->id,
            'route_id' => $setup['route']->id,
            'bus_id' => $setup['bus']->id,
            'driver_id' => $setup['driver']->id,
            'departure_station_id' => $setup['departureStation']->id,
            'arrival_station_id' => $setup['arrivalStation']->id,
            'schedule_type' => 'weekly',
            'departure_times' => ['05:00'],
            'days_of_week' => [],
            'start_date' => today()->addDay()->toDateString(),
            'price' => 7500,
        ]);

        $response->assertStatus(422);
    });

    it('updates a trip schedule', function (): void {
        $setup = createScheduleSetup();
        $user = createAdminUser($setup['company']);

        $schedule = TripSchedule::factory()->daily()->create([
            'company_id' => $setup['company']->id,
            'route_id' => $setup['route']->id,
            'bus_id' => $setup['bus']->id,
            'driver_id' => $setup['driver']->id,
            'departure_station_id' => $setup['departureStation']->id,
            'arrival_station_id' => $setup['arrivalStation']->id,
            'price' => 5000,
        ]);

        $response = $this->actingAs($user)->putJson("/api/v1/admin/trip-schedules/{$schedule->id}", [
            'price' => 7500,
        ]);

        $response->assertSuccessful();
        expect($schedule->fresh()->price)->toBe(7500);
    });

    it('deletes a trip schedule', function (): void {
        $setup = createScheduleSetup();
        $user = createAdminUser($setup['company']);

        $schedule = TripSchedule::factory()->create([
            'company_id' => $setup['company']->id,
            'route_id' => $setup['route']->id,
            'bus_id' => $setup['bus']->id,
            'driver_id' => $setup['driver']->id,
            'departure_station_id' => $setup['departureStation']->id,
            'arrival_station_id' => $setup['arrivalStation']->id,
        ]);

        $response = $this->actingAs($user)->deleteJson("/api/v1/admin/trip-schedules/{$schedule->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('trip_schedules', ['id' => $schedule->id]);
    });

    it('forbids unauthenticated user from managing schedules', function (): void {
        $response = $this->getJson('/api/v1/admin/trip-schedules');

        $response->assertUnauthorized();
    });

    it('forbids regular user from creating schedules', function (): void {
        test()->seed(RolePermissionSeeder::class);
        app()[Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $user = User::factory()->create();
        $user->assignRole('user');

        $response = $this->actingAs($user)->postJson('/api/v1/admin/trip-schedules', []);

        $response->assertForbidden();
    });

    it('triggers manual trip generation', function (): void {
        $setup = createScheduleSetup();
        $user = createAdminUser($setup['company']);

        $schedule = TripSchedule::factory()->daily()->create([
            'company_id' => $setup['company']->id,
            'route_id' => $setup['route']->id,
            'bus_id' => $setup['bus']->id,
            'driver_id' => $setup['driver']->id,
            'departure_station_id' => $setup['departureStation']->id,
            'arrival_station_id' => $setup['arrivalStation']->id,
            'departure_times' => ['08:00'],
            'start_date' => today()->toDateString(),
            'end_date' => today()->addMonth()->toDateString(),
        ]);

        $response = $this->actingAs($user)->postJson("/api/v1/admin/trip-schedules/{$schedule->id}/generate", [
            'days_ahead' => 3,
        ]);

        $response->assertSuccessful();
        $response->assertJsonPath('data.generated_count', fn ($count) => $count > 0);
    });
});
