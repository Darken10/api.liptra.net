<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\ComfortType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property string|null $departure_city_id
 * @property string|null $arrival_city_id
 * @property string|null $date
 * @property string|null $company_id
 * @property string|null $comfort_type
 */
final class SearchTripsRequest extends FormRequest
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
            'departure_city_id' => ['sometimes', 'uuid', 'exists:cities,id'],
            'arrival_city_id' => ['sometimes', 'uuid', 'exists:cities,id'],
            'date' => ['sometimes', 'date', 'after_or_equal:today'],
            'company_id' => ['sometimes', 'uuid', 'exists:companies,id'],
            'comfort_type' => ['sometimes', 'string', Rule::in(array_column(ComfortType::cases(), 'value'))],
        ];
    }
}
