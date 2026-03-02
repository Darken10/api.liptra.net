<?php

declare(strict_types=1);

use App\Enums\Gender;
use App\Enums\Status;
use App\Models\User;

test('user has uuid primary key', function () {
    $user = User::factory()->create();

    expect($user->id)
        ->toBeString()
        ->toHaveLength(36)
        ->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
});

test('user can be created with new fields', function () {
    $user = User::factory()->create([
        'firstname' => 'John',
        'lastname' => 'Doe',
        'gender' => Gender::Male,
        'status' => Status::Active,
        'phone' => '+33612345678',
        'phone_indication' => 'FR',
    ]);

    expect($user->firstname)->toBe('John');
    expect($user->lastname)->toBe('Doe');
    expect($user->gender)->toBe(Gender::Male);
    expect($user->phone)->toBe('+33612345678');
    expect($user->phone_indication)->toBe('FR');
    expect($user->status)->toBe(Status::Active);
});

test('user fields are nullable', function () {
    $user = User::factory()->create([
        'firstname' => null,
        'lastname' => null,
        'gender' => null,
        'phone' => null,
        'phone_indication' => null,
    ]);

    expect($user->firstname)->toBeNull();
    expect($user->lastname)->toBeNull();
    expect($user->gender)->toBeNull();
    expect($user->phone)->toBeNull();
    expect($user->phone_indication)->toBeNull();
    expect($user->status)->toBeNull();
});

test('gender enum is cast correctly', function () {
    $user = User::factory()->create(['gender' => Gender::Female]);

    expect($user->gender)->toBeInstanceOf(Gender::class);
    expect($user->gender->value)->toBe('female');
});

test('status enum is cast correctly', function () {
    $user = User::factory()->create(['status' => Status::Banned]);

    expect($user->status)->toBeInstanceOf(Status::class);
    expect($user->status->value)->toBe('banned');
});

test('user can have roles and permissions', function () {
    $user = User::factory()->create();

    // Check that the user has the HasRoles trait
    expect(method_exists($user, 'roles'))->toBeTrue();
    expect(method_exists($user, 'permissions'))->toBeTrue();
    expect(method_exists($user, 'assignRole'))->toBeTrue();
    expect(method_exists($user, 'givePermissionTo'))->toBeTrue();
});
