<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\TicketStatus;
use App\Models\Booking;
use App\Models\Ticket;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class BookingService
{
    /**
     * @param  array<int, array{
     *     passenger_firstname: string,
     *     passenger_lastname: string,
     *     passenger_phone: string,
     *     passenger_email?: string,
     *     passenger_relation: string,
     *     seat_number?: string
     * }>  $passengers
     */
    public function createBooking(User $user, Trip $trip, array $passengers): Booking
    {
        return DB::transaction(function () use ($user, $trip, $passengers): Booking {
            $totalAmount = $trip->price * count($passengers);

            $booking = Booking::query()->create([
                'user_id' => $user->id,
                'trip_id' => $trip->id,
                'booking_reference' => $this->generateBookingReference(),
                'total_amount' => $totalAmount,
                'payment_status' => PaymentStatus::Pending,
            ]);

            foreach ($passengers as $passenger) {
                Ticket::query()->create([
                    'booking_id' => $booking->id,
                    'trip_id' => $trip->id,
                    'ticket_number' => $this->generateTicketNumber(),
                    'validation_code' => $this->generateValidationCode(),
                    'qr_code_data' => $this->generateQrCodeData($booking->id),
                    'seat_number' => $passenger['seat_number'] ?? null,
                    'passenger_firstname' => $passenger['passenger_firstname'],
                    'passenger_lastname' => $passenger['passenger_lastname'],
                    'passenger_phone' => $passenger['passenger_phone'],
                    'passenger_email' => $passenger['passenger_email'] ?? null,
                    'passenger_relation' => $passenger['passenger_relation'],
                    'status' => TicketStatus::Pending,
                ]);
            }

            $trip->decrement('available_seats', count($passengers));

            return $booking->load('tickets');
        });
    }

    public function confirmPayment(Booking $booking, string $paymentMethod, string $paymentReference): Booking
    {
        return DB::transaction(function () use ($booking, $paymentMethod, $paymentReference): Booking {
            $booking->update([
                'payment_status' => PaymentStatus::Completed,
                'payment_method' => $paymentMethod,
                'payment_reference' => $paymentReference,
                'paid_at' => now(),
            ]);

            $booking->tickets()->update([
                'status' => TicketStatus::Paid,
            ]);

            return $booking->fresh(['tickets']) ?? $booking;
        });
    }

    public function cancelBooking(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking): Booking {
            $ticketCount = $booking->tickets()->count();

            $booking->update([
                'payment_status' => PaymentStatus::Refunded,
            ]);

            $booking->tickets()->update([
                'status' => TicketStatus::Cancelled,
            ]);

            $booking->trip->increment('available_seats', $ticketCount);

            return $booking->fresh(['tickets']) ?? $booking;
        });
    }

    private function generateBookingReference(): string
    {
        do {
            $reference = mb_strtoupper(Str::random(8));
        } while (Booking::query()->where('booking_reference', $reference)->exists());

        return $reference;
    }

    private function generateTicketNumber(): string
    {
        do {
            $number = 'TK' . mb_strtoupper(Str::random(6));
        } while (Ticket::query()->where('ticket_number', $number)->exists());

        return $number;
    }

    private function generateValidationCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function generateQrCodeData(string $bookingId): string
    {
        return Str::uuid()->toString() . ':' . $bookingId;
    }
}
