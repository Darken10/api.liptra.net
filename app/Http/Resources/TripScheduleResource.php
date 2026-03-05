<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\TripSchedule
 */
final class TripScheduleResource extends JsonResource
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
            'schedule_type' => $this->schedule_type->value,
            'schedule_type_label' => $this->schedule_type->label(),
            'departure_times' => $this->departure_times,
            'days_of_week' => $this->days_of_week,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'one_time_departure_at' => $this->one_time_departure_at?->toIso8601String(),
            'estimated_duration_minutes' => $this->estimated_duration_minutes,
            'price' => $this->price,
            'price_formatted' => number_format($this->price, 0, ',', ' ').' FCFA',
            'notes' => $this->notes,
            'is_active' => $this->is_active,
            'trips_count' => $this->whenCounted('trips'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
