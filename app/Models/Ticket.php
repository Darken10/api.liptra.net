<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PassengerRelation;
use App\Enums\TicketStatus;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $booking_id
 * @property string $trip_id
 * @property string $ticket_number
 * @property string $validation_code
 * @property string $qr_code_data
 * @property string|null $seat_number
 * @property string $passenger_firstname
 * @property string $passenger_lastname
 * @property string $passenger_phone
 * @property string|null $passenger_email
 * @property PassengerRelation $passenger_relation
 * @property TicketStatus $status
 * @property string|null $validated_by
 * @property Carbon|null $validated_at
 * @property string|null $boarded_by
 * @property Carbon|null $boarded_at
 * @property bool $baggage_checked
 * @property string|null $baggage_checked_by
 * @property Carbon|null $baggage_checked_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'booking_id',
        'trip_id',
        'ticket_number',
        'validation_code',
        'qr_code_data',
        'seat_number',
        'passenger_firstname',
        'passenger_lastname',
        'passenger_phone',
        'passenger_email',
        'passenger_relation',
        'status',
        'validated_by',
        'validated_at',
        'boarded_by',
        'boarded_at',
        'baggage_checked',
        'baggage_checked_by',
        'baggage_checked_at',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'passenger_relation' => PassengerRelation::class,
            'status' => TicketStatus::class,
            'validated_at' => 'datetime',
            'boarded_at' => 'datetime',
            'baggage_checked' => 'boolean',
            'baggage_checked_at' => 'datetime',
        ];
    }

    public function getPassengerFullNameAttribute(): string
    {
        return "{$this->passenger_firstname} {$this->passenger_lastname}";
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return BelongsTo<Trip, $this>
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function boarder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'boarded_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function baggageChecker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'baggage_checked_by');
    }
}
