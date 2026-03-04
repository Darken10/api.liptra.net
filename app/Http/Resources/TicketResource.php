<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Ticket
 */
final class TicketResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_number' => $this->ticket_number,
            'validation_code' => $this->validation_code,
            'qr_code_data' => $this->qr_code_data,
            'seat_number' => $this->seat_number,
            'passenger_firstname' => $this->passenger_firstname,
            'passenger_lastname' => $this->passenger_lastname,
            'passenger_full_name' => $this->passenger_full_name,
            'passenger_phone' => $this->passenger_phone,
            'passenger_email' => $this->passenger_email,
            'passenger_relation' => $this->passenger_relation->value,
            'passenger_relation_label' => $this->passenger_relation->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'validated_at' => $this->validated_at?->toIso8601String(),
            'boarded_at' => $this->boarded_at?->toIso8601String(),
            'baggage_checked' => $this->baggage_checked,
            'baggage_checked_at' => $this->baggage_checked_at?->toIso8601String(),
            'trip' => new TripResource($this->whenLoaded('trip')),
            'booking' => new BookingResource($this->whenLoaded('booking')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
