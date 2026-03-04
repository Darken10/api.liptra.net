<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\SearchTripsRequest;
use App\Http\Requests\Api\V1\StoreTripRequest;
use App\Http\Resources\TripResource;
use App\Models\Trip;
use App\Services\TripService;
use Illuminate\Http\JsonResponse;

final class TripController extends ApiController
{
    public function __construct(
        private TripService $tripService,
    ) {}

    public function index(SearchTripsRequest $request): JsonResponse
    {
        $trips = $this->tripService->searchTrips(
            $request->validated(),
            (int) $request->input('per_page', 15),
        );

        return $this->success(TripResource::collection($trips)->response()->getData(true));
    }

    public function show(string $id): JsonResponse
    {
        $trip = $this->tripService->findTrip($id);

        if (! $trip) {
            return $this->notFound('Voyage non trouvé');
        }

        return $this->success(new TripResource($trip));
    }

    public function store(StoreTripRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['company_id'] = $request->user()->companies()->first()?->id;

        if (! $data['company_id']) {
            return $this->forbidden('Vous devez être associé à une compagnie');
        }

        $trip = $this->tripService->createTrip($data);

        return $this->created(new TripResource($trip->load(['company', 'route.departureCity', 'route.arrivalCity', 'bus', 'driver'])), 'Voyage créé avec succès');
    }

    public function update(StoreTripRequest $request, string $id): JsonResponse
    {
        $trip = Trip::query()->find($id);

        if (! $trip) {
            return $this->notFound('Voyage non trouvé');
        }

        $trip = $this->tripService->updateTrip($trip, $request->validated());

        return $this->success(new TripResource($trip->load(['company', 'route.departureCity', 'route.arrivalCity', 'bus', 'driver'])), 'Voyage mis à jour');
    }

    public function cancel(string $id): JsonResponse
    {
        $trip = Trip::query()->find($id);

        if (! $trip) {
            return $this->notFound('Voyage non trouvé');
        }

        $trip = $this->tripService->cancelTrip($trip);

        return $this->success(new TripResource($trip), 'Voyage annulé');
    }
}
