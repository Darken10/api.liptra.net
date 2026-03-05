<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Admin\AnnouncementController as AdminAnnouncementController;
use App\Http\Controllers\Api\V1\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Api\V1\Admin\BusController as AdminBusController;
use App\Http\Controllers\Api\V1\Admin\CityController as AdminCityController;
use App\Http\Controllers\Api\V1\Admin\CompanyController as AdminCompanyController;
use App\Http\Controllers\Api\V1\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\V1\Admin\TripScheduleController as AdminTripScheduleController;
use App\Http\Controllers\Api\V1\Admin\DriverController as AdminDriverController;
use App\Http\Controllers\Api\V1\Admin\RouteController as AdminRouteController;
use App\Http\Controllers\Api\V1\Admin\StationController as AdminStationController;
use App\Http\Controllers\Api\V1\Admin\TagController as AdminTagController;
use App\Http\Controllers\Api\V1\Admin\TicketController as AdminTicketController;
use App\Http\Controllers\Api\V1\Admin\TripController as AdminTripController;
use App\Http\Controllers\Api\V1\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\V1\AnnouncementController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\CityController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\TicketController;
use App\Http\Controllers\Api\V1\TripController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Routes
|--------------------------------------------------------------------------
|
| Routes for API version 1.
|
*/

// ─── Authentication ──────────────────────────────────────────────────
Route::middleware('throttle:auth')->group(function (): void {
    Route::post('register', [AuthController::class, 'register'])->name('api.v1.register');
    Route::post('login', [AuthController::class, 'login'])->name('api.v1.login');
});

Route::middleware('throttle:6,1')->group(function (): void {
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->name('password.email');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');
});

// ─── Public routes (throttle: api – 60/min) ─────────────────────────
Route::middleware('throttle:api')->group(function (): void {
    Route::get('cities', [CityController::class, 'index'])->name('api.v1.cities.index');
    Route::get('trips', [TripController::class, 'index'])->name('api.v1.trips.index');
    Route::get('trips/{trip}', [TripController::class, 'show'])->name('api.v1.trips.show');
    Route::get('companies', [CompanyController::class, 'index'])->name('api.v1.companies.index');
    Route::get('companies/{company}', [CompanyController::class, 'show'])->name('api.v1.companies.show');
    Route::get('announcements', [AnnouncementController::class, 'index'])->name('api.v1.announcements.index');
    Route::get('announcements/{announcement}', [AnnouncementController::class, 'show'])->name('api.v1.announcements.show');
});

// ─── Authenticated routes (throttle: authenticated – 120/min) ───────
Route::middleware(['auth:sanctum', 'throttle:authenticated'])->group(function (): void {

    // Auth
    Route::post('logout', [AuthController::class, 'logout'])->name('api.v1.logout');
    Route::get('me', [AuthController::class, 'me'])->name('api.v1.me');

    // Email verification
    Route::post('email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware('signed')
        ->name('verification.verify');
    Route::post('email/resend', [AuthController::class, 'resendVerificationEmail'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    // ── Bookings (any authenticated user) ────────────────────────────
    Route::get('bookings', [BookingController::class, 'index'])->name('api.v1.bookings.index');
    Route::get('bookings/{booking}', [BookingController::class, 'show'])->name('api.v1.bookings.show');
    Route::post('bookings', [BookingController::class, 'store'])->name('api.v1.bookings.store');
    Route::post('bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('api.v1.bookings.cancel');

    // ── Tickets (passenger side) ─────────────────────────────────────
    Route::get('tickets', [TicketController::class, 'myTickets'])->name('api.v1.tickets.index');
    Route::get('tickets/{ticket}', [TicketController::class, 'show'])->name('api.v1.tickets.show');

    // ── Announcements interactions ───────────────────────────────────
    Route::post('announcements/{announcement}/comments', [AnnouncementController::class, 'comment'])
        ->name('api.v1.announcements.comment');
    Route::post('announcements/{announcement}/reactions', [AnnouncementController::class, 'react'])
        ->name('api.v1.announcements.react');

    // ── Admin / Company management ───────────────────────────────────
    Route::middleware('permission:manage-companies')->group(function (): void {
        Route::post('companies', [CompanyController::class, 'store'])->name('api.v1.companies.store');
        Route::put('companies/{company}', [CompanyController::class, 'update'])->name('api.v1.companies.update');
        Route::delete('companies/{company}', [CompanyController::class, 'destroy'])->name('api.v1.companies.destroy');
    });

    // ── Trip management ──────────────────────────────────────────────
    Route::middleware('permission:manage-trips')->group(function (): void {
        Route::post('trips', [TripController::class, 'store'])->name('api.v1.trips.store');
        Route::put('trips/{trip}', [TripController::class, 'update'])->name('api.v1.trips.update');
        Route::post('trips/{trip}/cancel', [TripController::class, 'cancel'])->name('api.v1.trips.cancel');
    });

    // ── Announcement management ──────────────────────────────────────
    Route::middleware('permission:manage-announcements')->group(function (): void {
        Route::post('announcements', [AnnouncementController::class, 'store'])->name('api.v1.announcements.store');
        Route::put('announcements/{announcement}', [AnnouncementController::class, 'update'])->name('api.v1.announcements.update');
        Route::delete('announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('api.v1.announcements.destroy');
    });

    // ── Ticket operations (station staff) ────────────────────────────
    Route::middleware('permission:validate-tickets')->group(function (): void {
        Route::post('tickets/validate', [TicketController::class, 'validate'])->name('api.v1.tickets.validate');
        Route::post('tickets/{ticket}/board', [TicketController::class, 'board'])->name('api.v1.tickets.board');
        Route::get('tickets/find/{ticketNumber}', [TicketController::class, 'findByNumber'])->name('api.v1.tickets.find');
    });

    Route::middleware('permission:manage-baggage')->group(function (): void {
        Route::post('tickets/{ticket}/baggage', [TicketController::class, 'checkBaggage'])->name('api.v1.tickets.baggage');
    });

    // ══════════════════════════════════════════════════════════════════
    // ══  ADMIN PANEL ROUTES  ═════════════════════════════════════════
    // ══════════════════════════════════════════════════════════════════
    Route::prefix('admin')->group(function (): void {

        // ── Dashboard ────────────────────────────────────────────────
        Route::get('dashboard', AdminDashboardController::class)->name('api.v1.admin.dashboard');

        // ── Companies management ─────────────────────────────────────
        Route::middleware('permission:manage-companies|view-companies')->group(function (): void {
            Route::get('companies', [AdminCompanyController::class, 'index'])->name('api.v1.admin.companies.index');
            Route::get('companies/{company}', [AdminCompanyController::class, 'show'])->name('api.v1.admin.companies.show');
        });

        Route::middleware('permission:manage-companies')->group(function (): void {
            Route::post('companies', [AdminCompanyController::class, 'store'])->name('api.v1.admin.companies.store');
            Route::put('companies/{company}', [AdminCompanyController::class, 'update'])->name('api.v1.admin.companies.update');
            Route::delete('companies/{company}', [AdminCompanyController::class, 'destroy'])->name('api.v1.admin.companies.destroy');
        });

        // ── Users management ─────────────────────────────────────────
        Route::middleware('permission:manage-users|view-users')->group(function (): void {
            Route::get('users', [AdminUserController::class, 'index'])->name('api.v1.admin.users.index');
            Route::get('users/{user}', [AdminUserController::class, 'show'])->name('api.v1.admin.users.show');
        });

        Route::middleware('permission:manage-users')->group(function (): void {
            Route::post('users', [AdminUserController::class, 'store'])->name('api.v1.admin.users.store');
            Route::put('users/{user}', [AdminUserController::class, 'update'])->name('api.v1.admin.users.update');
            Route::delete('users/{user}', [AdminUserController::class, 'destroy'])->name('api.v1.admin.users.destroy');
            Route::post('users/{user}/role', [AdminUserController::class, 'assignRole'])->name('api.v1.admin.users.role');
        });

        // ── Cities management ────────────────────────────────────────
        Route::middleware('permission:manage-companies|view-companies')->group(function (): void {
            Route::get('cities', [AdminCityController::class, 'index'])->name('api.v1.admin.cities.index');
            Route::get('cities/{city}', [AdminCityController::class, 'show'])->name('api.v1.admin.cities.show');
            Route::post('cities', [AdminCityController::class, 'store'])->name('api.v1.admin.cities.store');
            Route::put('cities/{city}', [AdminCityController::class, 'update'])->name('api.v1.admin.cities.update');
            Route::delete('cities/{city}', [AdminCityController::class, 'destroy'])->name('api.v1.admin.cities.destroy');
        });

        // ── Stations management ──────────────────────────────────────
        Route::middleware('permission:manage-companies|view-companies')->group(function (): void {
            Route::get('stations', [AdminStationController::class, 'index'])->name('api.v1.admin.stations.index');
            Route::get('stations/{station}', [AdminStationController::class, 'show'])->name('api.v1.admin.stations.show');
            Route::post('stations', [AdminStationController::class, 'store'])->name('api.v1.admin.stations.store');
            Route::put('stations/{station}', [AdminStationController::class, 'update'])->name('api.v1.admin.stations.update');
            Route::delete('stations/{station}', [AdminStationController::class, 'destroy'])->name('api.v1.admin.stations.destroy');
        });

        // ── Buses management ─────────────────────────────────────────
        Route::middleware('permission:manage-buses|view-buses')->group(function (): void {
            Route::get('buses', [AdminBusController::class, 'index'])->name('api.v1.admin.buses.index');
            Route::get('buses/{bus}', [AdminBusController::class, 'show'])->name('api.v1.admin.buses.show');
        });

        Route::middleware('permission:manage-buses')->group(function (): void {
            Route::post('buses', [AdminBusController::class, 'store'])->name('api.v1.admin.buses.store');
            Route::put('buses/{bus}', [AdminBusController::class, 'update'])->name('api.v1.admin.buses.update');
            Route::delete('buses/{bus}', [AdminBusController::class, 'destroy'])->name('api.v1.admin.buses.destroy');
        });

        // ── Drivers management ───────────────────────────────────────
        Route::middleware('permission:manage-drivers|view-drivers')->group(function (): void {
            Route::get('drivers', [AdminDriverController::class, 'index'])->name('api.v1.admin.drivers.index');
            Route::get('drivers/{driver}', [AdminDriverController::class, 'show'])->name('api.v1.admin.drivers.show');
        });

        Route::middleware('permission:manage-drivers')->group(function (): void {
            Route::post('drivers', [AdminDriverController::class, 'store'])->name('api.v1.admin.drivers.store');
            Route::put('drivers/{driver}', [AdminDriverController::class, 'update'])->name('api.v1.admin.drivers.update');
            Route::delete('drivers/{driver}', [AdminDriverController::class, 'destroy'])->name('api.v1.admin.drivers.destroy');
        });

        // ── Routes management ────────────────────────────────────────
        Route::middleware('permission:manage-trips|view-trips')->group(function (): void {
            Route::get('routes', [AdminRouteController::class, 'index'])->name('api.v1.admin.routes.index');
            Route::get('routes/{route}', [AdminRouteController::class, 'show'])->name('api.v1.admin.routes.show');
        });

        Route::middleware('permission:manage-trips')->group(function (): void {
            Route::post('routes', [AdminRouteController::class, 'store'])->name('api.v1.admin.routes.store');
            Route::put('routes/{route}', [AdminRouteController::class, 'update'])->name('api.v1.admin.routes.update');
            Route::delete('routes/{route}', [AdminRouteController::class, 'destroy'])->name('api.v1.admin.routes.destroy');
        });

        // ── Trips management ─────────────────────────────────────────
        Route::middleware('permission:manage-trips|view-trips')->group(function (): void {
            Route::get('trips', [AdminTripController::class, 'index'])->name('api.v1.admin.trips.index');
            Route::get('trips/{trip}', [AdminTripController::class, 'show'])->name('api.v1.admin.trips.show');
        });

        Route::middleware('permission:manage-trips')->group(function (): void {
            Route::post('trips', [AdminTripController::class, 'store'])->name('api.v1.admin.trips.store');
            Route::put('trips/{trip}', [AdminTripController::class, 'update'])->name('api.v1.admin.trips.update');
            Route::post('trips/{trip}/cancel', [AdminTripController::class, 'cancel'])->name('api.v1.admin.trips.cancel');
        });

        // ── Trip Schedules management ─────────────────────────────────
        Route::middleware('permission:manage-trips|view-trips')->group(function (): void {
            Route::get('trip-schedules', [AdminTripScheduleController::class, 'index'])->name('api.v1.admin.trip-schedules.index');
            Route::get('trip-schedules/{tripSchedule}', [AdminTripScheduleController::class, 'show'])->name('api.v1.admin.trip-schedules.show');
        });

        Route::middleware('permission:manage-trips')->group(function (): void {
            Route::post('trip-schedules', [AdminTripScheduleController::class, 'store'])->name('api.v1.admin.trip-schedules.store');
            Route::put('trip-schedules/{tripSchedule}', [AdminTripScheduleController::class, 'update'])->name('api.v1.admin.trip-schedules.update');
            Route::delete('trip-schedules/{tripSchedule}', [AdminTripScheduleController::class, 'destroy'])->name('api.v1.admin.trip-schedules.destroy');
            Route::post('trip-schedules/{tripSchedule}/generate', [AdminTripScheduleController::class, 'generateTrips'])->name('api.v1.admin.trip-schedules.generate');
        });

        // ── Bookings management ──────────────────────────────────────
        Route::middleware('permission:view-sales|manage-tickets')->group(function (): void {
            Route::get('bookings', [AdminBookingController::class, 'index'])->name('api.v1.admin.bookings.index');
            Route::get('bookings/{booking}', [AdminBookingController::class, 'show'])->name('api.v1.admin.bookings.show');
        });

        // ── Tickets management ───────────────────────────────────────
        Route::middleware('permission:view-tickets|validate-tickets')->group(function (): void {
            Route::get('tickets', [AdminTicketController::class, 'index'])->name('api.v1.admin.tickets.index');
            Route::get('tickets/{ticket}', [AdminTicketController::class, 'show'])->name('api.v1.admin.tickets.show');
            Route::get('tickets/find/{ticketNumber}', [AdminTicketController::class, 'findByNumber'])->name('api.v1.admin.tickets.find');
        });

        Route::middleware('permission:validate-tickets')->group(function (): void {
            Route::post('tickets/validate', [AdminTicketController::class, 'validate'])->name('api.v1.admin.tickets.validate');
            Route::post('tickets/{ticket}/board', [AdminTicketController::class, 'board'])->name('api.v1.admin.tickets.board');
        });

        Route::middleware('permission:manage-baggage')->group(function (): void {
            Route::post('tickets/{ticket}/baggage', [AdminTicketController::class, 'checkBaggage'])->name('api.v1.admin.tickets.baggage');
        });

        // ── Announcements management ─────────────────────────────────
        Route::middleware('permission:manage-announcements|view-announcements')->group(function (): void {
            Route::get('announcements', [AdminAnnouncementController::class, 'index'])->name('api.v1.admin.announcements.index');
            Route::get('announcements/{announcement}', [AdminAnnouncementController::class, 'show'])->name('api.v1.admin.announcements.show');
            Route::get('tags', [AdminTagController::class, 'index'])->name('api.v1.admin.tags.index');
        });

        Route::middleware('permission:manage-announcements')->group(function (): void {
            Route::post('announcements', [AdminAnnouncementController::class, 'store'])->name('api.v1.admin.announcements.store');
            Route::put('announcements/{announcement}', [AdminAnnouncementController::class, 'update'])->name('api.v1.admin.announcements.update');
            Route::delete('announcements/{announcement}', [AdminAnnouncementController::class, 'destroy'])->name('api.v1.admin.announcements.destroy');
            Route::post('tags', [AdminTagController::class, 'store'])->name('api.v1.admin.tags.store');
            Route::delete('tags/{tag}', [AdminTagController::class, 'destroy'])->name('api.v1.admin.tags.destroy');
        });
    });
});
