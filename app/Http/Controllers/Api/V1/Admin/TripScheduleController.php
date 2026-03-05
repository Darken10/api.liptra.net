<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\TripScheduleType;
use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\TripScheduleResource;
use App\Models\TripSchedule;
use App\Services\TripScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class TripScheduleController extends ApiController
{
    public function __construct(private TripScheduleService $scheduleService) {}

    public function index(Request $request): JsonResponse
    {
        $query = TripSchedule::query()
            ->with(['company', 'route.departureCity', 'route.arrivalCity', 'bus', 'driver', 'departureStation.city', 'arrivalStation.city'])
            ->withCount('trips');

        if ($companyId = $request->input('company_id')) {
            $query->where('company_id', $companyId);
        }

        if ($type = $request->input('schedule_type')) {
            $query->where('schedule_type', $type);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($search = $request->input('search')) {
            $query->whereHas('route', function ($q) use ($search): void {
                $q->whereHas('departureCity', fn ($c) => $c->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('arrivalCity', fn ($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        $schedules = $query->latest()->paginate((int) $request->input('per_page', 15));

        return $this->success(TripScheduleResource::collection($schedules)->response()->getData(true));
    }

    public function show(TripSchedule $tripSchedule): JsonResponse
    {
        $tripSchedule->load([
            'company', 'route.departureCity', 'route.arrivalCity',
            'bus', 'driver', 'departureStation.city', 'arrivalStation.city',
        ])->loadCount('trips');

        return $this->success(new TripScheduleResource($tripSchedule));
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
            'schedule_type' => ['required', 'string', Rule::in(array_column(TripScheduleType::cases(), 'value'))],
            'departure_times' => ['required', 'array', 'min:1'],
            'departure_times.*' => ['required', 'date_format:H:i'],
            'days_of_week' => ['nullable', 'array'],
            'days_of_week.*' => ['integer', 'between:1,7'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'one_time_departure_at' => ['nullable', 'date', 'after:now'],
            'estimated_duration_minutes' => ['nullable', 'integer', 'min:1'],
            'price' => ['required', 'integer', 'min:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ]);

        if ($validated['schedule_type'] === TripScheduleType::Weekly->value && empty($validated['days_of_week'])) {
            return $this->error('Les jours de la semaine sont requis pour un planning hebdomadaire.', 422);
        }

        $schedule = TripSchedule::query()->create($validated);

        $this->scheduleService->generateTrips($schedule);

        $schedule->load([
            'company', 'route.departureCity', 'route.arrivalCity',
            'bus', 'driver', 'departureStation.city', 'arrivalStation.city',
        ])->loadCount('trips');

        return $this->created(new TripScheduleResource($schedule));
    }

    public function update(Request $request, TripSchedule $tripSchedule): JsonResponse
    {
        $validated = $request->validate([
            'bus_id' => ['sometimes', 'uuid', 'exists:buses,id'],
            'driver_id' => ['sometimes', 'uuid', 'exists:drivers,id'],
            'departure_times' => ['sometimes', 'array', 'min:1'],
            'departure_times.*' => ['required', 'date_format:H:i'],
            'days_of_week' => ['nullable', 'array'],
            'days_of_week.*' => ['integer', 'between:1,7'],
            'end_date' => ['nullable', 'date'],
            'estimated_duration_minutes' => ['nullable', 'integer', 'min:1'],
            'price' => ['sometimes', 'integer', 'min:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ]);

        $tripSchedule->update($validated);

        $tripSchedule->load([
            'company', 'route.departureCity', 'route.arrivalCity',
            'bus', 'driver', 'departureStation.city', 'arrivalStation.city',
        ])->loadCount('trips');

        return $this->success(new TripScheduleResource($tripSchedule));
    }

    public function destroy(TripSchedule $tripSchedule): JsonResponse
    {
        $tripSchedule->delete();

        return $this->noContent();
    }

    public function generateTrips(Request $request, TripSchedule $tripSchedule): JsonResponse
    {
        $validated = $request->validate([
            'days_ahead' => ['sometimes', 'integer', 'min:1', 'max:90'],
        ]);

        $daysAhead = (int) ($validated['days_ahead'] ?? 7);

        $trips = $this->scheduleService->generateTrips(
            $tripSchedule,
            today(),
            today()->addDays($daysAhead),
        );

        return $this->success([
            'generated_count' => count($trips),
            'message' => count($trips).' voyage(s) généré(s)',
        ]);
    }
}
