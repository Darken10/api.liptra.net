<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CityFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property string|null $region
 * @property float|null $latitude
 * @property float|null $longitude
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class City extends Model
{
    /** @use HasFactory<CityFactory> */
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'name',
        'region',
        'latitude',
        'longitude',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Station, $this>
     */
    public function stations(): HasMany
    {
        return $this->hasMany(Station::class);
    }

    /**
     * @return HasMany<\App\Models\Route, $this>
     */
    public function departureRoutes(): HasMany
    {
        return $this->hasMany(Route::class, 'departure_city_id');
    }

    /**
     * @return HasMany<\App\Models\Route, $this>
     */
    public function arrivalRoutes(): HasMany
    {
        return $this->hasMany(Route::class, 'arrival_city_id');
    }
}
