<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\ValidateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TicketController extends ApiController
{
    public function __construct(
        private TicketValidationService $ticketValidationService,
    ) {}

    public function myTickets(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $tickets = Ticket::query()
            ->whereHas('booking', fn ($q) => $q->where('user_id', $user->id))
            ->with(['trip.route.departureCity', 'trip.route.arrivalCity', 'trip.company', 'booking'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return $this->success(TicketResource::collection($tickets)->response()->getData(true));
    }

    public function show(string $id, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $ticket = Ticket::query()
            ->whereHas('booking', fn ($q) => $q->where('user_id', $user->id))
            ->with(['trip.route.departureCity', 'trip.route.arrivalCity', 'trip.company', 'trip.bus', 'trip.driver', 'booking'])
            ->find($id);

        if (! $ticket) {
            return $this->notFound('Ticket non trouvé');
        }

        return $this->success(new TicketResource($ticket));
    }

    public function validate(ValidateTicketRequest $request): JsonResponse
    {
        /** @var User $agent */
        $agent = $request->user();
        $validated = $request->validated();

        $ticket = null;

        if (! empty($validated['qr_code_data'])) {
            $ticket = $this->ticketValidationService->validateByQrCode($validated['qr_code_data'], $agent);
        } elseif (! empty($validated['validation_code']) && ! empty($validated['phone'])) {
            $ticket = $this->ticketValidationService->validateByCode($validated['validation_code'], $validated['phone'], $agent);
        }

        if (! $ticket) {
            return $this->notFound('Ticket non trouvé ou déjà validé');
        }

        return $this->success(
            new TicketResource($ticket->load(['trip.route.departureCity', 'trip.route.arrivalCity', 'booking'])),
            'Ticket validé avec succès'
        );
    }

    public function board(string $id, Request $request): JsonResponse
    {
        /** @var User $agent */
        $agent = $request->user();

        $ticket = Ticket::query()->find($id);

        if (! $ticket) {
            return $this->notFound('Ticket non trouvé');
        }

        if ($ticket->status->value !== 'validated') {
            return $this->error('Le ticket doit être validé avant l\'embarquement', 400);
        }

        $ticket = $this->ticketValidationService->boardPassenger($ticket, $agent);

        return $this->success(new TicketResource($ticket), 'Passager embarqué');
    }

    public function checkBaggage(string $id, Request $request): JsonResponse
    {
        /** @var User $bagagiste */
        $bagagiste = $request->user();

        $ticket = Ticket::query()->find($id);

        if (! $ticket) {
            return $this->notFound('Ticket non trouvé');
        }

        $ticket = $this->ticketValidationService->checkBaggage($ticket, $bagagiste);

        return $this->success(new TicketResource($ticket), 'Bagage enregistré');
    }

    public function findByNumber(string $ticketNumber): JsonResponse
    {
        $ticket = $this->ticketValidationService->findByTicketNumber($ticketNumber);

        if (! $ticket) {
            return $this->notFound('Ticket non trouvé');
        }

        return $this->success(new TicketResource($ticket));
    }
}
