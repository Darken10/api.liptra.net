<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\TripStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property string $route_id
 * @property string $bus_id
 * @property string $driver_id
 * @property string $departure_station_id
 * @property string $arrival_station_id
 * @property string $departure_at
 * @property string|null $estimated_arrival_at
 * @property int $price
 * @property int $available_seats
 * @property string $status
 */
final class StoreTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'route_id' => ['required', 'uuid', 'exists:routes,id'],
            'bus_id' => ['required', 'uuid', 'exists:buses,id'],
            'driver_id' => ['required', 'uuid', 'exists:drivers,id'],
            'departure_station_id' => ['required', 'uuid', 'exists:stations,id'],
            'arrival_station_id' => ['required', 'uuid', 'exists:stations,id'],
            'departure_at' => ['required', 'date', 'after:now'],
            'estimated_arrival_at' => ['nullable', 'date', 'after:departure_at'],
            'price' => ['required', 'integer', 'min:500'],
            'available_seats' => ['required', 'integer', 'min:1'],
            'status' => ['sometimes', 'string', Rule::in(array_column(TripStatus::cases(), 'value'))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'price.min' => 'Le prix minimum est de 500 FCFA.',
            'departure_at.after' => 'La date de départ doit être dans le futur.',
            'available_seats.min' => 'Au moins 1 siège doit être disponible.',
        ];
    }
}
