<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            CitySeeder::class,
        ]);

        $superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'firstname' => 'Super',
            'lastname' => 'Admin',
            'email' => 'admin@liptra.net',
            'phone' => '+22670000000',
            'phone_indication' => 'BF',
        ]);
        $superAdmin->assignRole(Role::SuperAdmin->value);

        $user = User::factory()->create([
            'name' => 'Test User',
            'firstname' => 'Test',
            'lastname' => 'User',
            'email' => 'user@liptra.net',
            'phone' => '+22670000001',
            'phone_indication' => 'BF',
        ]);
        $user->assignRole(Role::User->value);
    }
}
