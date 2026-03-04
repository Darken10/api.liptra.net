<?php

declare(strict_types=1);

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
});
