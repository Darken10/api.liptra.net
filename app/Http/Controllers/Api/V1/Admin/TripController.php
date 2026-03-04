<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\TripResource;
use App\Models\Trip;
use App\Services\TripService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TripController extends ApiController
{
    public function __construct(private TripService $tripService) {}

    public function index(Request $request): JsonResponse
    {
        $query = Trip::query()
            ->with(['company', 'route.departureCity', 'route.arrivalCity', 'bus', 'driver', 'departureStation.city', 'arrivalStation.city']);

        if ($companyId = $request->input('company_id')) {
            $query->where('company_id', $companyId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($date = $request->input('date')) {
            $query->whereDate('departure_at', $date);
        }

        if ($search = $request->input('search')) {
            $query->whereHas('route', function ($q) use ($search): void {
                $q->whereHas('departureCity', fn ($c) => $c->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('arrivalCity', fn ($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        $trips = $query->latest('departure_at')->paginate((int) $request->input('per_page', 15));

        return $this->success(TripResource::collection($trips)->response()->getData(true));
    }

    public function show(Trip $trip): JsonResponse
    {
        $trip->load(['company', 'route.departureCity', 'route.arrivalCity', 'bus', 'driver', 'departureStation.city', 'arrivalStation.city', 'bookings.tickets']);

        return $this->success(new TripResource($trip));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'uuid', 'exists:companies,id'],
            'route_id' => ['required', 'uuid', 'exists:routes,id'],
            'bus_id' => ['required', 'uuid', 'exists:buses,id'],
            'driver_id' => ['required', 'uuid', 'exists:drivers,id'],
            'departure_station_id' => ['required', 'uuid', 'exists:stations,id'],
            'arrival_station_id' => ['required', 'uuid', 'exists:stations,id'],
            'departure_at' => ['required', 'date', 'after:now'],
            'estimated_arrival_at' => ['nullable', 'date', 'after:departure_at'],
            'price' => ['required', 'integer', 'min:0'],
            'available_seats' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $validated['status'] = 'scheduled';

        $trip = Trip::query()->create($validated);
        $trip->load(['company', 'route.departureCity', 'route.arrivalCity', 'bus', 'driver', 'departureStation.city', 'arrivalStation.city']);

        return $this->created(new TripResource($trip));
    }

    public function update(Request $request, Trip $trip): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => ['sometimes', 'uuid', 'exists:companies,id'],
            'route_id' => ['sometimes', 'uuid', 'exists:routes,id'],
            'bus_id' => ['sometimes', 'uuid', 'exists:buses,id'],
            'driver_id' => ['sometimes', 'uuid', 'exists:drivers,id'],
            'departure_station_id' => ['sometimes', 'uuid', 'exists:stations,id'],
            'arrival_station_id' => ['sometimes', 'uuid', 'exists:stations,id'],
            'departure_at' => ['sometimes', 'date'],
            'estimated_arrival_at' => ['nullable', 'date'],
            'price' => ['sometimes', 'integer', 'min:0'],
            'available_seats' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', 'string'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $trip->update($validated);
        $trip->load(['company', 'route.departureCity', 'route.arrivalCity', 'bus', 'driver', 'departureStation.city', 'arrivalStation.city']);

        return $this->success(new TripResource($trip));
    }

    public function cancel(Trip $trip): JsonResponse
    {
        $this->tripService->cancelTrip($trip);
        $trip->load(['company', 'route.departureCity', 'route.arrivalCity', 'bus', 'driver', 'departureStation.city', 'arrivalStation.city']);

        return $this->success(new TripResource($trip), 'Trip cancelled successfully');
    }
}
