<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BookingController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Booking::query()
            ->with(['user', 'trip.route.departureCity', 'trip.route.arrivalCity', 'trip.company', 'tickets']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('booking_reference', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('payment_status')) {
            $query->where('payment_status', $status);
        }

        if ($tripId = $request->input('trip_id')) {
            $query->where('trip_id', $tripId);
        }

        $bookings = $query->latest()->paginate((int) $request->input('per_page', 15));

        return $this->success(BookingResource::collection($bookings)->response()->getData(true));
    }

    public function show(Booking $booking): JsonResponse
    {
        $booking->load(['user', 'trip.route.departureCity', 'trip.route.arrivalCity', 'trip.company', 'trip.bus', 'trip.driver', 'tickets']);

        return $this->success(new BookingResource($booking));
    }
}
