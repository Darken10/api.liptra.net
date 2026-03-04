<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('departure_city_id');
            $table->uuid('arrival_city_id');
            $table->integer('distance_km')->nullable();
            $table->integer('estimated_duration_minutes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('departure_city_id')->references('id')->on('cities');
            $table->foreign('arrival_city_id')->references('id')->on('cities');
            $table->unique(['company_id', 'departure_city_id', 'arrival_city_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routes');
    }
};
