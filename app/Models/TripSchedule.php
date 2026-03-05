<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TripScheduleType;
use Database\Factories\TripScheduleFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $company_id
 * @property string $route_id
 * @property string $bus_id
 * @property string $driver_id
 * @property string $departure_station_id
 * @property string $arrival_station_id
 * @property TripScheduleType $schedule_type
 * @property array<int, string> $departure_times
 * @property array<int, int>|null $days_of_week
 * @property Carbon $start_date
 * @property Carbon|null $end_date
 * @property Carbon|null $one_time_departure_at
 * @property int|null $estimated_duration_minutes
 * @property int $price
 * @property string|null $notes
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class TripSchedule extends Model
{
    /** @use HasFactory<TripScheduleFactory> */
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'company_id',
        'route_id',
        'bus_id',
        'driver_id',
        'departure_station_id',
        'arrival_station_id',
        'schedule_type',
        'departure_times',
        'days_of_week',
        'start_date',
        'end_date',
        'one_time_departure_at',
        'estimated_duration_minutes',
        'price',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'schedule_type' => TripScheduleType::class,
            'departure_times' => 'array',
            'days_of_week' => 'array',
            'start_date' => 'date',
            'end_date' => 'date',
            'one_time_departure_at' => 'datetime',
            'estimated_duration_minutes' => 'integer',
            'price' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<Route, $this>
     */
    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    /**
     * @return BelongsTo<Bus, $this>
     */
    public function bus(): BelongsTo
    {
        return $this->belongsTo(Bus::class);
    }

    /**
     * @return BelongsTo<Driver, $this>
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * @return BelongsTo<Station, $this>
     */
    public function departureStation(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'departure_station_id');
    }

    /**
     * @return BelongsTo<Station, $this>
     */
    public function arrivalStation(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'arrival_station_id');
    }

    /**
     * @return HasMany<Trip, $this>
     */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }
}
