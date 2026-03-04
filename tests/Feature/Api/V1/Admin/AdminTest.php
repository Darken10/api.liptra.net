<?php

declare(strict_types=1);

use App\Models\Announcement;
use App\Models\Bus;
use App\Models\City;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Route;
use App\Models\Station;
use App\Models\Ticket;
use App\Models\Trip;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createAdmin(): User
{
    (new RolePermissionSeeder)->run();
    $user = User::factory()->create();
    $user->assignRole('super-admin');

    return $user;
}

function createRegularUser(): User
{
    (new RolePermissionSeeder)->run();
    $user = User::factory()->create();
    $user->assignRole('user');

    return $user;
}

// ═══════════════════════════════════════════════════════════════════
//  AUTH & AUTHORIZATION
// ═══════════════════════════════════════════════════════════════════

describe('Admin Authorization', function (): void {
    it('denies access to unauthenticated users', function (): void {
        $this->getJson('/api/v1/admin/dashboard')->assertUnauthorized();
    });

    it('denies access to regular users without permissions', function (): void {
        $user = createRegularUser();

        $this->actingAs($user)
            ->getJson('/api/v1/admin/users')
            ->assertForbidden();
    });

    it('allows super-admin access to dashboard', function (): void {
        $admin = createAdmin();

        $this->actingAs($admin)
            ->getJson('/api/v1/admin/dashboard')
            ->assertSuccessful();
    });
});

// ═══════════════════════════════════════════════════════════════════
//  DASHBOARD
// ═══════════════════════════════════════════════════════════════════

describe('Admin Dashboard', function (): void {
    it('returns dashboard stats', function (): void {
        $admin = createAdmin();

        $this->actingAs($admin)
            ->getJson('/api/v1/admin/dashboard')
            ->assertSuccessful()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_users',
                    'total_companies',
                    'total_trips',
                    'total_bookings',
                    'total_revenue',
                    'total_tickets',
                    'recent_bookings',
                    'upcoming_trips',
                ],
            ]);
    });
});

// ═══════════════════════════════════════════════════════════════════
//  USERS CRUD
// ═══════════════════════════════════════════════════════════════════

describe('Admin Users', function (): void {
    it('lists users', function (): void {
        $admin = createAdmin();
        User::factory()->count(3)->create();

        $this->actingAs($admin)
            ->getJson('/api/v1/admin/users')
            ->assertSuccessful()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data',
                    'links',
                    'meta' => ['current_page', 'last_page', 'total'],
                ],
            ]);
    });

    it('shows a single user', function (): void {
        $admin = createAdmin();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->getJson("/api/v1/admin/users/{$user->id}")
            ->assertSuccessful()
            ->assertJsonPath('data.id', $user->id);
    });

    it('creates a new user', function (): void {
        $admin = createAdmin();

        $this->actingAs($admin)
            ->postJson('/api/v1/admin/users', [
                'name' => 'New Admin User',
                'email' => 'newadmin@test.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('users', ['email' => 'newadmin@test.com']);
    });

    it('updates a user', function (): void {
        $admin = createAdmin();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->putJson("/api/v1/admin/users/{$user->id}", [
                'name' => 'Updated Name',
                'email' => $user->email,
            ])
            ->assertSuccessful();

        expect($user->fresh()->name)->toBe('Updated Name');
    });

    it('deletes a user', function (): void {
        $admin = createAdmin();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->deleteJson("/api/v1/admin/users/{$user->id}")
            ->assertSuccessful();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    });

    it('searches users by name or email', function (): void {
        $admin = createAdmin();
        User::factory()->create(['name' => 'John Doe', 'email' => 'john@test.com']);
        User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@test.com']);

        $this->actingAs($admin)
            ->getJson('/api/v1/admin/users?search=john')
            ->assertSuccessful()
            ->assertJsonCount(1, 'data.data');
    });
});

// ═══════════════════════════════════════════════════════════════════
//  COMPANIES CRUD
// ═══════════════════════════════════════════════════════════════════

describe('Admin Companies', function (): void {
    it('lists companies', function (): void {
        $admin = createAdmin();
        Company::factory()->count(3)->create();

        $this->actingAs($admin)
            ->getJson('/api/v1/admin/companies')
            ->assertSuccessful();
    });

    it('creates a company', function (): void {
        $admin = createAdmin();

        $this->actingAs($admin)
            ->postJson('/api/v1/admin/companies', [
                'name' => 'Liptra Transport',
                'phone' => '+22670123456',
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('companies', ['name' => 'Liptra Transport']);
    });

    it('updates a company', function (): void {
        $admin = createAdmin();
        $company = Company::factory()->create();

        $this->actingAs($admin)
            ->putJson("/api/v1/admin/companies/{$company->id}", [
                'name' => 'Updated Company',
                'phone' => $company->phone,
            ])
            ->assertSuccessful();

        expect($company->fresh()->name)->toBe('Updated Company');
    });

    it('deletes a company', function (): void {
        $admin = createAdmin();
        $company = Company::factory()->create();

        $this->actingAs($admin)
            ->deleteJson("/api/v1/admin/companies/{$company->id}")
            ->assertSuccessful();

        $this->assertDatabaseMissing('companies', ['id' => $company->id]);
    });
});

// ═══════════════════════════════════════════════════════════════════
//  CITIES CRUD
// ═══════════════════════════════════════════════════════════════════

describe('Admin Cities', function (): void {
    it('lists cities', function (): void {
        $admin = createAdmin();
        City::factory()->count(3)->create();

        $this->actingAs($admin)
            ->getJson('/api/v1/admin/cities')
            ->assertSuccessful()
            ->assertJsonStructure(['success', 'data' => ['data']]);
    });

    it('creates a city', function (): void {
        $admin = createAdmin();

        $this->actingAs($admin)
            ->postJson('/api/v1/admin/cities', [
                'name' => 'Ouagadougou',
                'region' => 'Centre',
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('cities', ['name' => 'Ouagadougou']);
    });

    it('updates a city', function (): void {
        $admin = createAdmin();
        $city = City::factory()->create();

        $this->actingAs($admin)
            ->putJson("/api/v1/admin/cities/{$city->id}", [
                'name' => 'Updated City',
                'region' => $city->region,
            ])
            ->assertSuccessful();

        expect($city->fresh()->name)->toBe('Updated City');
    });

    it('deletes a city', function (): void {
        $admin = createAdmin();
        $city = City::factory()->create();

        $this->actingAs($admin)
            ->deleteJson("/api/v1/admin/cities/{$city->id}")
            ->assertSuccessful();

        $this->assertDatabaseMissing('cities', ['id' => $city->id]);
    });
});

// ═══════════════════════════════════════════════════════════════════
//  STATIONS CRUD
// ═══════════════════════════════════════════════════════════════════

describe('Admin Stations', function (): void {
    it('lists stations', function (): void {
        $admin = createAdmin();
        Station::factory()->count(2)->create();

        $this->actingAs($admin)
            ->getJson('/api/v1/admin/stations')
            ->assertSuccessful();
    });

    it('creates a station', function (): void {
        $admin = createAdmin();
        $city = City::factory()->create();
        $company = Company::factory()->create();

        $this->actingAs($admin)
            ->postJson('/api/v1/admin/stations', [
                'name' => 'Gare Centrale',
                'city_id' => $city->id,
                'company_id' => $company->id,
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('stations', ['name' => 'Gare Centrale']);
    });
});

// ═══════════════════════════════════════════════════════════════════
//  BUSES CRUD
// ═══════════════════════════════════════════════════════════════════

describe('Admin Buses', function (): void {
    it('lists buses', function (): void {
        $admin = createAdmin();
        Bus::factory()->count(2)->create();

        $this->actingAs($admin)
            ->getJson('/api/v1/admin/buses')
            ->assertSuccessful();
    });

    it('creates a bus', function (): void {
        $admin = createAdmin();
        $company = Company::factory()->create();

        $this->actingAs($admin)
            ->postJson('/api/v1/admin/buses', [
                'registration_number' => 'BUS-001-BF',
                'company_id' => $company->id,
                'total_seats' => 45,
                'comfort_type' => 'classique',
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('buses', ['registration_number' => 'BUS-001-BF']);
    });
});

// ═══════════════════════════════════════════════════════════════════
//  DRIVERS CRUD
// ═══════════════════════════════════════════════════════════════════

describe('Admin Drivers', function (): void {
    it('lists drivers', function (): void {
        $admin = createAdmin();
        Driver::factory()->count(2)->create();

        $this->actingAs($admin)
            ->getJson('/api/v1/admin/drivers')
            ->assertSuccessful();
    });

    it('creates a driver', function (): void {
        $admin = createAdmin();
        $company = Company::factory()->create();

        $this->actingAs($admin)
            ->postJson('/api/v1/admin/drivers', [
                'firstname' => 'Amadou',
                'lastname' => 'Diallo',
                'phone' => '+22670000000',
                'company_id' => $company->id,
                'license_number' => 'DL-12345',
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('drivers', ['license_number' => 'DL-12345']);
    });
});

// ═══════════════════════════════════════════════════════════════════
//  ROUTES CRUD
// ═══════════════════════════════════════════════════════════════════

describe('Admin Routes', function (): void {
    it('lists routes', function (): void {
        $admin = createAdmin();
        Route::factory()->count(2)->create();

        $this->actingAs($admin)
            ->getJson('/api/v1/admin/routes')
            ->assertSuccessful();
    });

    it('creates a route', function (): void {
        $admin = createAdmin();
        $company = Company::factory()->create();
        $city1 = City::factory()->create();
        $city2 = City::factory()->create();

        $this->actingAs($admin)
            ->postJson('/api/v1/admin/routes', [
                'company_id' => $company->id,
                'departure_city_id' => $city1->id,
                'arrival_city_id' => $city2->id,
                'distance_km' => 300,
            ])
            ->assertSuccessful();
    });
});

// ═══════════════════════════════════════════════════════════════════
//  TRIPS
// ═══════════════════════════════════════════════════════════════════

describe('Admin Trips', function (): void {
    it('lists trips', function (): void {
        $admin = createAdmin();
        Trip::factory()->count(2)->create();

        $this->actingAs($admin)
            ->getJson('/api/v1/admin/trips')
            ->assertSuccessful();
    });
});

// ═══════════════════════════════════════════════════════════════════
//  BOOKINGS (read-only)
// ═══════════════════════════════════════════════════════════════════

describe('Admin Bookings', function (): void {
    it('lists bookings', function (): void {
        $admin = createAdmin();

        $this->actingAs($admin)
            ->getJson('/api/v1/admin/bookings')
            ->assertSuccessful();
    });
});

// ═══════════════════════════════════════════════════════════════════
//  TICKETS
// ═══════════════════════════════════════════════════════════════════

describe('Admin Tickets', function (): void {
    it('lists tickets', function (): void {
        $admin = createAdmin();

        $this->actingAs($admin)
            ->getJson('/api/v1/admin/tickets')
            ->assertSuccessful();
    });
});

// ═══════════════════════════════════════════════════════════════════
//  ANNOUNCEMENTS CRUD
// ═══════════════════════════════════════════════════════════════════

describe('Admin Announcements', function (): void {
    it('lists announcements', function (): void {
        $admin = createAdmin();
        Announcement::factory()->count(2)->create();

        $this->actingAs($admin)
            ->getJson('/api/v1/admin/announcements')
            ->assertSuccessful();
    });

    it('creates an announcement', function (): void {
        $admin = createAdmin();
        $company = Company::factory()->create();

        $this->actingAs($admin)
            ->postJson('/api/v1/admin/announcements', [
                'company_id' => $company->id,
                'title' => 'Nouvelle ligne ouverte',
                'content' => 'Nous ouvrons une nouvelle ligne Ouaga-Bobo',
                'is_published' => true,
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('announcements', ['title' => 'Nouvelle ligne ouverte']);
    });

    it('updates an announcement', function (): void {
        $admin = createAdmin();
        $announcement = Announcement::factory()->create();

        $this->actingAs($admin)
            ->putJson("/api/v1/admin/announcements/{$announcement->id}", [
                'title' => 'Updated Title',
                'content' => $announcement->content,
            ])
            ->assertSuccessful();

        expect($announcement->fresh()->title)->toBe('Updated Title');
    });

    it('deletes an announcement', function (): void {
        $admin = createAdmin();
        $announcement = Announcement::factory()->create();

        $this->actingAs($admin)
            ->deleteJson("/api/v1/admin/announcements/{$announcement->id}")
            ->assertSuccessful();

        $this->assertDatabaseMissing('announcements', ['id' => $announcement->id]);
    });
});

// ═══════════════════════════════════════════════════════════════════
//  ROLE-BASED ACCESS (Agent, ChefGare, Bagagiste)
// ═══════════════════════════════════════════════════════════════════

describe('Role-Based Access', function (): void {
    it('allows agent to list tickets', function (): void {
        (new RolePermissionSeeder)->run();
        $agent = User::factory()->create();
        $agent->assignRole('agent');

        $this->actingAs($agent)
            ->getJson('/api/v1/admin/tickets')
            ->assertSuccessful();
    });

    it('denies agent access to user management', function (): void {
        (new RolePermissionSeeder)->run();
        $agent = User::factory()->create();
        $agent->assignRole('agent');

        $this->actingAs($agent)
            ->getJson('/api/v1/admin/users')
            ->assertForbidden();
    });

    it('allows chef-gare to view trips', function (): void {
        (new RolePermissionSeeder)->run();
        $chefGare = User::factory()->create();
        $chefGare->assignRole('chef-gare');

        $this->actingAs($chefGare)
            ->getJson('/api/v1/admin/trips')
            ->assertSuccessful();
    });

    it('allows bagagiste to check baggage', function (): void {
        (new RolePermissionSeeder)->run();
        $bagagiste = User::factory()->create();
        $bagagiste->assignRole('bagagiste');
        $ticket = Ticket::factory()->create(['status' => 'boarded']);

        $this->actingAs($bagagiste)
            ->postJson("/api/v1/admin/tickets/{$ticket->id}/baggage")
            ->assertSuccessful();
    });
});
