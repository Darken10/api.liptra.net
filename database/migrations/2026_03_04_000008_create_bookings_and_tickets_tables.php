<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('trip_id');
            $table->string('booking_reference', 10)->unique();
            $table->unsignedInteger('total_amount');
            $table->string('payment_status');
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('trip_id')->references('id')->on('trips');
            $table->index('booking_reference');
        });

        Schema::create('tickets', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('booking_id');
            $table->uuid('trip_id');
            $table->string('ticket_number', 10)->unique();
            $table->string('validation_code', 6);
            $table->string('qr_code_data');
            $table->string('seat_number')->nullable();
            $table->string('passenger_firstname');
            $table->string('passenger_lastname');
            $table->string('passenger_phone');
            $table->string('passenger_email')->nullable();
            $table->string('passenger_relation');
            $table->string('status');
            $table->uuid('validated_by')->nullable();
            $table->dateTime('validated_at')->nullable();
            $table->uuid('boarded_by')->nullable();
            $table->dateTime('boarded_at')->nullable();
            $table->boolean('baggage_checked')->default(false);
            $table->uuid('baggage_checked_by')->nullable();
            $table->dateTime('baggage_checked_at')->nullable();
            $table->timestamps();

            $table->foreign('booking_id')->references('id')->on('bookings')->cascadeOnDelete();
            $table->foreign('trip_id')->references('id')->on('trips');
            $table->foreign('validated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('boarded_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('baggage_checked_by')->references('id')->on('users')->nullOnDelete();
            $table->index('validation_code');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('bookings');
    }
};
