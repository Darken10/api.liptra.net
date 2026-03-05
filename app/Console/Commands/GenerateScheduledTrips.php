<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\TripSchedule;
use App\Services\TripScheduleService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

final class GenerateScheduledTrips extends Command
{
    protected $signature = 'trips:generate {--days=7 : Number of days ahead to generate}';

    protected $description = 'Generate trips from active schedules for upcoming days';

    public function handle(TripScheduleService $service): int
    {
        $daysAhead = (int) $this->option('days');
        $toDate = today()->addDays($daysAhead);
        $totalGenerated = 0;

        $schedules = TripSchedule::query()
            ->where('is_active', true)
            ->where(function ($q): void {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', today());
            })
            ->get();

        $this->info("Processing {$schedules->count()} active schedule(s)...");

        foreach ($schedules as $schedule) {
            $trips = $service->generateTrips($schedule, today(), $toDate);
            $totalGenerated += count($trips);

            if (count($trips) > 0) {
                $this->line("  → {$schedule->route->departureCity->name} → {$schedule->route->arrivalCity->name}: ".count($trips).' trip(s)');
            }
        }

        $this->info("Done. {$totalGenerated} trip(s) generated.");

        return self::SUCCESS;
    }
}
