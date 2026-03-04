<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TripStatus;
use App\Models\Trip;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class TripService
{
    /**
     * @param  array{departure_city_id?: string, arrival_city_id?: string, date?: string, company_id?: string, comfort_type?: string}  $filters
     * @return LengthAwarePaginator<Trip>
     */
    public function searchTrips(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Trip::query()
            ->with(['company', 'route.departureCity', 'route.arrivalCity', 'bus', 'driver', 'departureStation', 'arrivalStation'])
            ->where('is_active', true)
            ->where('status', TripStatus::Scheduled)
            ->where('departure_at', '>=', now())
            ->where('available_seats', '>', 0)
            ->when(
                isset($filters['departure_city_id']),
                fn (Builder $q): Builder => $q->whereHas('route', fn (Builder $r): Builder => $r->where('departure_city_id', $filters['departure_city_id']))
            )
            ->when(
                isset($filters['arrival_city_id']),
                fn (Builder $q): Builder => $q->whereHas('route', fn (Builder $r): Builder => $r->where('arrival_city_id', $filters['arrival_city_id']))
            )
            ->when(
                isset($filters['date']),
                fn (Builder $q): Builder => $q->whereDate('departure_at', $filters['date'])
            )
            ->when(
                isset($filters['company_id']),
                fn (Builder $q): Builder => $q->where('company_id', $filters['company_id'])
            )
            ->when(
                isset($filters['comfort_type']),
                fn (Builder $q): Builder => $q->whereHas('bus', fn (Builder $b): Builder => $b->where('comfort_type', $filters['comfort_type']))
            )
            ->orderBy('departure_at')
            ->paginate($perPage);
    }

    public function findTrip(string $id): ?Trip
    {
        return Trip::query()
            ->with(['company', 'route.departureCity', 'route.arrivalCity', 'bus.photos', 'driver', 'departureStation.city', 'arrivalStation.city'])
            ->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createTrip(array $data): Trip
    {
        $data['status'] = $data['status'] ?? TripStatus::Scheduled;
        $data['is_active'] = $data['is_active'] ?? true;

        return Trip::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateTrip(Trip $trip, array $data): Trip
    {
        $trip->update($data);

        return $trip->fresh() ?? $trip;
    }

    public function cancelTrip(Trip $trip): Trip
    {
        $trip->update(['status' => TripStatus::Cancelled]);

        return $trip->fresh() ?? $trip;
    }
}
