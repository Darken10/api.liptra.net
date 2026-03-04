<?php

declare(strict_types=1);

use App\Models\City;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Cities', function (): void {
    it('lists active cities', function (): void {
        City::factory()->count(3)->create(['is_active' => true]);
        City::factory()->create(['is_active' => false]);

        $response = $this->getJson('/api/v1/cities');

        $response->assertSuccessful()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'name', 'region'],
                ],
            ])
            ->assertJsonCount(3, 'data');
    });

    it('returns empty array when no cities exist', function (): void {
        $response = $this->getJson('/api/v1/cities');

        $response->assertSuccessful()
            ->assertJsonCount(0, 'data');
    });
});
