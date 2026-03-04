<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Bus;
use App\Models\BusPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusPhoto>
 */
final class BusPhotoFactory extends Factory
{
    protected $model = BusPhoto::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bus_id' => Bus::factory(),
            'path' => 'buses/' . fake()->uuid() . '.jpg',
            'caption' => fake()->optional()->sentence(),
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
