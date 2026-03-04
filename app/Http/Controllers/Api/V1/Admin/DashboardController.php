<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\BookingResource;
use App\Http\Resources\TripResource;
use App\Models\Booking;
use App\Models\Company;
use App\Models\Ticket;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

final class DashboardController extends ApiController
{
    public function __invoke(): JsonResponse
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        $totalUsers = User::query()->count();
        $usersLastMonth = User::query()->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();
        $usersThisMonth = User::query()->where('created_at', '>=', $startOfMonth)->count();
        $usersTrend = $usersLastMonth > 0 ? round((($usersThisMonth - $usersLastMonth) / $usersLastMonth) * 100) : 0;

        $totalBookings = Booking::query()->count();
        $bookingsLastMonth = Booking::query()->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();
        $bookingsThisMonth = Booking::query()->where('created_at', '>=', $startOfMonth)->count();
        $bookingsTrend = $bookingsLastMonth > 0 ? round((($bookingsThisMonth - $bookingsLastMonth) / $bookingsLastMonth) * 100) : 0;

        $totalRevenue = Booking::query()->where('payment_status', 'completed')->sum('total_amount');
        $revenueLastMonth = Booking::query()->where('payment_status', 'completed')->whereBetween('paid_at', [$startOfLastMonth, $endOfLastMonth])->sum('total_amount');
        $revenueThisMonth = Booking::query()->where('payment_status', 'completed')->where('paid_at', '>=', $startOfMonth)->sum('total_amount');
        $revenueTrend = $revenueLastMonth > 0 ? round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100) : 0;

        $totalTrips = Trip::query()->count();
        $tripsLastMonth = Trip::query()->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();
        $tripsThisMonth = Trip::query()->where('created_at', '>=', $startOfMonth)->count();
        $tripsTrend = $tripsLastMonth > 0 ? round((($tripsThisMonth - $tripsLastMonth) / $tripsLastMonth) * 100) : 0;

        $recentBookings = Booking::query()
            ->with(['user', 'trip.route.departureCity', 'trip.route.arrivalCity', 'trip.company', 'tickets'])
            ->latest()
            ->limit(5)
            ->get();

        $upcomingTrips = Trip::query()
            ->with(['company', 'route.departureCity', 'route.arrivalCity', 'bus', 'driver', 'departureStation.city', 'arrivalStation.city'])
            ->upcoming()
            ->limit(5)
            ->get();

        return $this->success([
            'total_users' => $totalUsers,
            'total_companies' => Company::query()->count(),
            'total_trips' => $totalTrips,
            'total_bookings' => $totalBookings,
            'total_revenue' => $totalRevenue,
            'total_tickets' => Ticket::query()->count(),
            'recent_bookings' => BookingResource::collection($recentBookings),
            'upcoming_trips' => TripResource::collection($upcomingTrips),
            'users_trend' => (int) $usersTrend,
            'bookings_trend' => (int) $bookingsTrend,
            'revenue_trend' => (int) $revenueTrend,
            'trips_trend' => (int) $tripsTrend,
        ]);
    }
}
