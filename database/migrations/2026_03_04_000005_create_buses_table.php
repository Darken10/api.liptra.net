<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buses', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('registration_number');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->integer('total_seats');
            $table->string('comfort_type');
            $table->integer('manufacture_year')->nullable();
            $table->string('color')->nullable();
            $table->boolean('has_air_conditioning')->default(false);
            $table->boolean('has_wifi')->default(false);
            $table->boolean('has_usb_charging')->default(false);
            $table->boolean('has_toilet')->default(false);
            $table->string('photo')->nullable();
            $table->integer('mileage')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->unique(['company_id', 'registration_number']);
        });

        Schema::create('bus_photos', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('bus_id');
            $table->string('path');
            $table->string('caption')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('bus_id')->references('id')->on('buses')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bus_photos');
        Schema::dropIfExists('buses');
    }
};
