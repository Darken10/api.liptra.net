<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TripScheduleType;
use App\Enums\TripStatus;
use App\Models\Bus;
use App\Models\Trip;
use App\Models\TripSchedule;
use Illuminate\Support\Carbon;

final class TripScheduleService
{
    /**
     * Generate trips from a schedule for a given date range.
     *
     * @return array<int, Trip>
     */
    public function generateTrips(TripSchedule $schedule, ?Carbon $fromDate = null, ?Carbon $toDate = null): array
    {
        $fromDate ??= today();
        $toDate ??= today()->addDays(7);

        if (! $schedule->is_active) {
            return [];
        }

        return match ($schedule->schedule_type) {
            TripScheduleType::OneTime => $this->generateOneTimeTrip($schedule),
            TripScheduleType::Daily => $this->generateDailyTrips($schedule, $fromDate, $toDate),
            TripScheduleType::Weekly => $this->generateWeeklyTrips($schedule, $fromDate, $toDate),
        };
    }

    /**
     * @return array<int, Trip>
     */
    private function generateOneTimeTrip(TripSchedule $schedule): array
    {
        if (! $schedule->one_time_departure_at || $schedule->one_time_departure_at->isPast()) {
            return [];
        }

        if ($this->tripAlreadyExists($schedule, $schedule->one_time_departure_at)) {
            return [];
        }

        return [$this->createTrip($schedule, $schedule->one_time_departure_at)];
    }

    /**
     * @return array<int, Trip>
     */
    private function generateDailyTrips(TripSchedule $schedule, Carbon $fromDate, Carbon $toDate): array
    {
        $created = [];
        $currentDate = $fromDate->copy()->max($schedule->start_date);
        $endDate = $schedule->end_date ? $toDate->copy()->min($schedule->end_date) : $toDate->copy();

        while ($currentDate->lte($endDate)) {
            foreach ($schedule->departure_times as $time) {
                $departureAt = $this->buildDepartureDateTime($currentDate, $time);

                if ($departureAt->isPast()) {
                    continue;
                }

                if ($this->tripAlreadyExists($schedule, $departureAt)) {
                    continue;
                }

                $created[] = $this->createTrip($schedule, $departureAt);
            }

            $currentDate->addDay();
        }

        return $created;
    }

    /**
     * @return array<int, Trip>
     */
    private function generateWeeklyTrips(TripSchedule $schedule, Carbon $fromDate, Carbon $toDate): array
    {
        $created = [];
        $daysOfWeek = $schedule->days_of_week ?? [];

        if ($daysOfWeek === []) {
            return [];
        }

        $currentDate = $fromDate->copy()->max($schedule->start_date);
        $endDate = $schedule->end_date ? $toDate->copy()->min($schedule->end_date) : $toDate->copy();

        while ($currentDate->lte($endDate)) {
            $dayOfWeek = (int) $currentDate->dayOfWeekIso; // 1=Monday..7=Sunday

            if (in_array($dayOfWeek, $daysOfWeek, true)) {
                foreach ($schedule->departure_times as $time) {
                    $departureAt = $this->buildDepartureDateTime($currentDate, $time);

                    if ($departureAt->isPast()) {
                        continue;
                    }

                    if ($this->tripAlreadyExists($schedule, $departureAt)) {
                        continue;
                    }

                    $created[] = $this->createTrip($schedule, $departureAt);
                }
            }

            $currentDate->addDay();
        }

        return $created;
    }

    private function buildDepartureDateTime(Carbon $date, string $time): Carbon
    {
        [$hour, $minute] = explode(':', $time);

        return $date->copy()->setHour((int) $hour)->setMinute((int) $minute)->setSecond(0);
    }

    private function tripAlreadyExists(TripSchedule $schedule, Carbon $departureAt): bool
    {
        return Trip::query()
            ->where('trip_schedule_id', $schedule->id)
            ->where('departure_at', $departureAt)
            ->exists();
    }

    private function createTrip(TripSchedule $schedule, Carbon $departureAt): Trip
    {
        $estimatedArrival = $schedule->estimated_duration_minutes
            ? $departureAt->copy()->addMinutes($schedule->estimated_duration_minutes)
            : null;

        $bus = Bus::query()->find($schedule->bus_id);

        return Trip::query()->create([
            'trip_schedule_id' => $schedule->id,
            'company_id' => $schedule->company_id,
            'route_id' => $schedule->route_id,
            'bus_id' => $schedule->bus_id,
            'driver_id' => $schedule->driver_id,
            'departure_station_id' => $schedule->departure_station_id,
            'arrival_station_id' => $schedule->arrival_station_id,
            'departure_at' => $departureAt,
            'estimated_arrival_at' => $estimatedArrival,
            'price' => $schedule->price,
            'available_seats' => $bus?->total_seats ?? 0,
            'status' => TripStatus::Scheduled,
            'notes' => $schedule->notes,
            'is_active' => true,
        ]);
    }
}
