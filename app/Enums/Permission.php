<?php

declare(strict_types=1);

namespace App\Enums;

enum Permission: string
{
    // Company management
    case ManageCompanies = 'manage-companies';
    case ViewCompanies = 'view-companies';

    // Trip management
    case ManageTrips = 'manage-trips';
    case ViewTrips = 'view-trips';

    // Bus management
    case ManageBuses = 'manage-buses';
    case ViewBuses = 'view-buses';

    // Driver management
    case ManageDrivers = 'manage-drivers';
    case ViewDrivers = 'view-drivers';

    // Announcement/blog management
    case ManageAnnouncements = 'manage-announcements';
    case ViewAnnouncements = 'view-announcements';

    // Ticket management
    case ManageTickets = 'manage-tickets';
    case ViewTickets = 'view-tickets';
    case ScanTickets = 'scan-tickets';
    case ValidateTickets = 'validate-tickets';

    // Sales
    case ViewSales = 'view-sales';

    // Station management
    case ManageDepartures = 'manage-departures';
    case SuperviseAgents = 'supervise-agents';

    // Baggage
    case ManageBaggage = 'manage-baggage';

    // User management
    case ManageUsers = 'manage-users';
    case ViewUsers = 'view-users';

    // Role management
    case ManageRoles = 'manage-roles';

    // System settings
    case ManageSettings = 'manage-settings';

    // Purchase
    case PurchaseTickets = 'purchase-tickets';

    // Blog interactions
    case InteractPosts = 'interact-posts';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return array<self>
     */
    public static function forRole(\App\Enums\Role $role): array
    {
        return match ($role) {
            Role::SuperAdmin => self::cases(),
            Role::Admin => [
                self::ManageTrips, self::ViewTrips,
                self::ManageBuses, self::ViewBuses,
                self::ManageDrivers, self::ViewDrivers,
                self::ManageAnnouncements, self::ViewAnnouncements,
                self::ViewTickets, self::ViewSales,
                self::ManageUsers, self::ViewUsers,
                self::ViewCompanies,
            ],
            Role::ChefGare => [
                self::ManageDepartures, self::SuperviseAgents,
                self::ValidateTickets, self::ViewTickets,
                self::ViewTrips, self::ViewBuses, self::ViewDrivers,
            ],
            Role::Agent => [
                self::ScanTickets, self::ValidateTickets,
                self::ViewTickets, self::ViewTrips,
            ],
            Role::Bagagiste => [
                self::ManageBaggage, self::ViewTickets,
            ],
            Role::User => [
                self::PurchaseTickets, self::InteractPosts,
                self::ViewTrips, self::ViewAnnouncements,
                self::ViewTickets, self::ViewCompanies,
            ],
        };
    }
}
