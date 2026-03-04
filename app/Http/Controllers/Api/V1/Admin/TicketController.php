<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Services\TicketValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TicketController extends ApiController
{
    public function __construct(private TicketValidationService $validationService) {}

    public function index(Request $request): JsonResponse
    {
        $query = Ticket::query()
            ->with(['booking.user', 'trip.route.departureCity', 'trip.route.arrivalCity', 'trip.company']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('passenger_firstname', 'like', "%{$search}%")
                    ->orWhere('passenger_lastname', 'like', "%{$search}%")
                    ->orWhere('passenger_phone', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($tripId = $request->input('trip_id')) {
            $query->where('trip_id', $tripId);
        }

        $tickets = $query->latest()->paginate((int) $request->input('per_page', 15));

        return $this->success(TicketResource::collection($tickets)->response()->getData(true));
    }

    public function show(Ticket $ticket): JsonResponse
    {
        $ticket->load(['booking.user', 'trip.route.departureCity', 'trip.route.arrivalCity', 'trip.company', 'trip.bus', 'trip.driver']);

        return $this->success(new TicketResource($ticket));
    }

    public function validate(Request $request): JsonResponse
    {
        $request->validate([
            'qr_code_data' => ['required_without:validation_code', 'string'],
            'validation_code' => ['required_without:qr_code_data', 'string'],
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        if ($qrCode = $request->input('qr_code_data')) {
            $ticket = $this->validationService->validateByQrCode($qrCode, $user);
        } else {
            $ticket = $this->validationService->validateByCode($request->input('validation_code'), $user);
        }

        return $this->success(new TicketResource($ticket), 'Ticket validated successfully');
    }

    public function board(Ticket $ticket, Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $this->validationService->boardPassenger($ticket, $user);

        return $this->success(new TicketResource($ticket->fresh()), 'Passenger boarded successfully');
    }

    public function checkBaggage(Ticket $ticket, Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $this->validationService->checkBaggage($ticket, $user);

        return $this->success(new TicketResource($ticket->fresh()), 'Baggage checked successfully');
    }

    public function findByNumber(string $ticketNumber): JsonResponse
    {
        $ticket = $this->validationService->findByTicketNumber($ticketNumber);

        return $this->success(new TicketResource($ticket));
    }
}
