<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DriverFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $company_id
 * @property string $firstname
 * @property string $lastname
 * @property string $phone
 * @property string $license_number
 * @property string|null $license_type
 * @property Carbon|null $license_expiry
 * @property string|null $photo
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class Driver extends Model
{
    /** @use HasFactory<DriverFactory> */
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'company_id',
        'firstname',
        'lastname',
        'phone',
        'license_number',
        'license_type',
        'license_expiry',
        'photo',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'license_expiry' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->firstname} {$this->lastname}";
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return HasMany<Trip, $this>
     */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }
}
