<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_schedules', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('route_id');
            $table->uuid('bus_id');
            $table->uuid('driver_id');
            $table->uuid('departure_station_id');
            $table->uuid('arrival_station_id');
            $table->string('schedule_type'); // one_time, daily, weekly
            $table->json('departure_times'); // ["08:00", "10:00", "12:00"]
            $table->json('days_of_week')->nullable(); // [1,2,3,4,5] (1=Monday..7=Sunday) for weekly
            $table->date('start_date');
            $table->date('end_date')->nullable(); // null = ongoing
            $table->dateTime('one_time_departure_at')->nullable(); // for one_time type
            $table->unsignedInteger('estimated_duration_minutes')->nullable();
            $table->unsignedInteger('price');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('route_id')->references('id')->on('routes');
            $table->foreign('bus_id')->references('id')->on('buses');
            $table->foreign('driver_id')->references('id')->on('drivers');
            $table->foreign('departure_station_id')->references('id')->on('stations');
            $table->foreign('arrival_station_id')->references('id')->on('stations');
            $table->index('schedule_type');
            $table->index('is_active');
        });

        Schema::table('trips', function (Blueprint $table): void {
            $table->uuid('trip_schedule_id')->nullable()->after('id');
            $table->foreign('trip_schedule_id')->references('id')->on('trip_schedules')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table): void {
            $table->dropForeign(['trip_schedule_id']);
            $table->dropColumn('trip_schedule_id');
        });

        Schema::dropIfExists('trip_schedules');
    }
};
