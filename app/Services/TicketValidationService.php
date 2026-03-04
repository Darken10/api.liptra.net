<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;

final class TicketValidationService
{
    public function validateByQrCode(string $qrCodeData, User $agent): ?Ticket
    {
        $ticket = Ticket::query()
            ->where('qr_code_data', $qrCodeData)
            ->where('status', TicketStatus::Paid)
            ->first();

        if (! $ticket) {
            return null;
        }

        $ticket->update([
            'status' => TicketStatus::Validated,
            'validated_by' => $agent->id,
            'validated_at' => now(),
        ]);

        return $ticket->fresh();
    }

    public function validateByCode(string $validationCode, string $phone, User $agent): ?Ticket
    {
        $ticket = Ticket::query()
            ->where('validation_code', $validationCode)
            ->where('passenger_phone', $phone)
            ->where('status', TicketStatus::Paid)
            ->first();

        if (! $ticket) {
            return null;
        }

        $ticket->update([
            'status' => TicketStatus::Validated,
            'validated_by' => $agent->id,
            'validated_at' => now(),
        ]);

        return $ticket->fresh();
    }

    public function boardPassenger(Ticket $ticket, User $agent): Ticket
    {
        $ticket->update([
            'status' => TicketStatus::Boarded,
            'boarded_by' => $agent->id,
            'boarded_at' => now(),
        ]);

        return $ticket->fresh() ?? $ticket;
    }

    public function checkBaggage(Ticket $ticket, User $bagagiste): Ticket
    {
        $ticket->update([
            'baggage_checked' => true,
            'baggage_checked_by' => $bagagiste->id,
            'baggage_checked_at' => now(),
        ]);

        return $ticket->fresh() ?? $ticket;
    }

    public function findByTicketNumber(string $ticketNumber): ?Ticket
    {
        return Ticket::query()
            ->with(['booking.user', 'trip.route.departureCity', 'trip.route.arrivalCity', 'trip.company'])
            ->where('ticket_number', $ticketNumber)
            ->first();
    }
}
