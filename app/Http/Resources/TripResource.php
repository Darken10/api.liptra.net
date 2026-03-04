<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Trip
 */
final class TripResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company' => new CompanyResource($this->whenLoaded('company')),
            'route' => new RouteResource($this->whenLoaded('route')),
            'bus' => new BusResource($this->whenLoaded('bus')),
            'driver' => new DriverResource($this->whenLoaded('driver')),
            'departure_station' => new StationResource($this->whenLoaded('departureStation')),
            'arrival_station' => new StationResource($this->whenLoaded('arrivalStation')),
            'departure_at' => $this->departure_at?->toIso8601String(),
            'estimated_arrival_at' => $this->estimated_arrival_at?->toIso8601String(),
            'price' => $this->price,
            'price_formatted' => number_format($this->price, 0, ',', ' ') . ' FCFA',
            'available_seats' => $this->available_seats,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'notes' => $this->notes,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
