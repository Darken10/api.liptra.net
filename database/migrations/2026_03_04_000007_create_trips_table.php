<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('route_id');
            $table->uuid('bus_id');
            $table->uuid('driver_id');
            $table->uuid('departure_station_id');
            $table->uuid('arrival_station_id');
            $table->dateTime('departure_at');
            $table->dateTime('estimated_arrival_at')->nullable();
            $table->dateTime('actual_departure_at')->nullable();
            $table->dateTime('actual_arrival_at')->nullable();
            $table->unsignedInteger('price');
            $table->unsignedInteger('available_seats');
            $table->string('status');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('route_id')->references('id')->on('routes');
            $table->foreign('bus_id')->references('id')->on('buses');
            $table->foreign('driver_id')->references('id')->on('drivers');
            $table->foreign('departure_station_id')->references('id')->on('stations');
            $table->foreign('arrival_station_id')->references('id')->on('stations');
            $table->index('departure_at');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
