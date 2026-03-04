<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TripStatus;
use Database\Factories\TripFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $company_id
 * @property string $route_id
 * @property string $bus_id
 * @property string $driver_id
 * @property string $departure_station_id
 * @property string $arrival_station_id
 * @property Carbon $departure_at
 * @property Carbon|null $estimated_arrival_at
 * @property Carbon|null $actual_departure_at
 * @property Carbon|null $actual_arrival_at
 * @property int $price
 * @property int $available_seats
 * @property TripStatus $status
 * @property string|null $notes
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class Trip extends Model
{
    /** @use HasFactory<TripFactory> */
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'company_id',
        'route_id',
        'bus_id',
        'driver_id',
        'departure_station_id',
        'arrival_station_id',
        'departure_at',
        'estimated_arrival_at',
        'actual_departure_at',
        'actual_arrival_at',
        'price',
        'available_seats',
        'status',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'departure_at' => 'datetime',
            'estimated_arrival_at' => 'datetime',
            'actual_departure_at' => 'datetime',
            'actual_arrival_at' => 'datetime',
            'price' => 'integer',
            'available_seats' => 'integer',
            'status' => TripStatus::class,
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
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * @return HasMany<Ticket, $this>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * @param  Builder<Trip>  $query
     * @return Builder<Trip>
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('departure_at', '>=', now())
            ->where('status', TripStatus::Scheduled)
            ->where('is_active', true)
            ->orderBy('departure_at');
    }

    /**
     * @param  Builder<Trip>  $query
     * @return Builder<Trip>
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('available_seats', '>', 0)
            ->upcoming();
    }
}
