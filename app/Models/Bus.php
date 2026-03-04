<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ComfortType;
use Database\Factories\BusFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $company_id
 * @property string $registration_number
 * @property string|null $brand
 * @property string|null $model
 * @property int $total_seats
 * @property ComfortType $comfort_type
 * @property int|null $manufacture_year
 * @property string|null $color
 * @property bool $has_air_conditioning
 * @property bool $has_wifi
 * @property bool $has_usb_charging
 * @property bool $has_toilet
 * @property string|null $photo
 * @property int|null $mileage
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class Bus extends Model
{
    /** @use HasFactory<BusFactory> */
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'company_id',
        'registration_number',
        'brand',
        'model',
        'total_seats',
        'comfort_type',
        'manufacture_year',
        'color',
        'has_air_conditioning',
        'has_wifi',
        'has_usb_charging',
        'has_toilet',
        'photo',
        'mileage',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'total_seats' => 'integer',
            'comfort_type' => ComfortType::class,
            'manufacture_year' => 'integer',
            'has_air_conditioning' => 'boolean',
            'has_wifi' => 'boolean',
            'has_usb_charging' => 'boolean',
            'has_toilet' => 'boolean',
            'mileage' => 'integer',
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
     * @return HasMany<BusPhoto, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(BusPhoto::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<Trip, $this>
     */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }
}
