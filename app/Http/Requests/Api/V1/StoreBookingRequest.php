<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\PassengerRelation;
use App\Enums\PaymentMethod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property string $trip_id
 * @property array<int, array<string, mixed>> $passengers
 * @property string $payment_method
 */
final class StoreBookingRequest extends FormRequest
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
            'trip_id' => ['required', 'uuid', 'exists:trips,id'],
            'passengers' => ['required', 'array', 'min:1', 'max:10'],
            'passengers.*.passenger_firstname' => ['required', 'string', 'max:255'],
            'passengers.*.passenger_lastname' => ['required', 'string', 'max:255'],
            'passengers.*.passenger_phone' => ['required', 'string', 'max:20'],
            'passengers.*.passenger_email' => ['nullable', 'email', 'max:255'],
            'passengers.*.passenger_relation' => ['required', 'string', Rule::in(array_column(PassengerRelation::cases(), 'value'))],
            'passengers.*.seat_number' => ['nullable', 'string', 'max:10'],
            'payment_method' => ['required', 'string', Rule::in(array_column(PaymentMethod::cases(), 'value'))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'passengers.required' => 'Au moins un passager est requis.',
            'passengers.*.passenger_firstname.required' => 'Le prénom du passager est obligatoire.',
            'passengers.*.passenger_lastname.required' => 'Le nom du passager est obligatoire.',
            'passengers.*.passenger_phone.required' => 'Le numéro de téléphone du passager est obligatoire.',
            'payment_method.required' => 'Le moyen de paiement est obligatoire.',
        ];
    }
}
