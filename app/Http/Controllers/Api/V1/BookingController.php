<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Trip;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BookingController extends ApiController
{
    public function __construct(
        private BookingService $bookingService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $bookings = Booking::query()
            ->where('user_id', $user->id)
            ->with(['trip.route.departureCity', 'trip.route.arrivalCity', 'trip.company', 'tickets'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return $this->success(BookingResource::collection($bookings)->response()->getData(true));
    }

    public function show(string $id, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $booking = Booking::query()
            ->where('user_id', $user->id)
            ->with(['trip.route.departureCity', 'trip.route.arrivalCity', 'trip.company', 'trip.bus', 'trip.driver', 'tickets'])
            ->find($id);

        if (! $booking) {
            return $this->notFound('Réservation non trouvée');
        }

        return $this->success(new BookingResource($booking));
    }

    public function store(StoreBookingRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        $trip = Trip::query()->find($validated['trip_id']);

        if (! $trip) {
            return $this->notFound('Voyage non trouvé');
        }

        if (! $trip->is_active) {
            return $this->error('Ce voyage n\'est plus disponible', 400);
        }

        $passengerCount = count($validated['passengers']);
        if ($trip->available_seats < $passengerCount) {
            return $this->error("Seulement {$trip->available_seats} siège(s) disponible(s)", 400);
        }

        $booking = $this->bookingService->createBooking($user, $trip, $validated['passengers']);

        $paymentReference = 'SIM-' . $booking->booking_reference;
        $booking = $this->bookingService->confirmPayment($booking, $validated['payment_method'], $paymentReference);

        return $this->created(
            new BookingResource($booking->load(['trip.route.departureCity', 'trip.route.arrivalCity', 'trip.company', 'tickets'])),
            'Réservation effectuée avec succès'
        );
    }

    public function cancel(string $id, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $booking = Booking::query()
            ->where('user_id', $user->id)
            ->with('tickets')
            ->find($id);

        if (! $booking) {
            return $this->notFound('Réservation non trouvée');
        }

        $booking = $this->bookingService->cancelBooking($booking);

        return $this->success(new BookingResource($booking), 'Réservation annulée');
    }
}
