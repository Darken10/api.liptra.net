<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @property string|null $qr_code_data
 * @property string|null $validation_code
 * @property string|null $phone
 */
final class ValidateTicketRequest extends FormRequest
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
            'qr_code_data' => ['required_without:validation_code', 'nullable', 'string'],
            'validation_code' => ['required_without:qr_code_data', 'nullable', 'string', 'size:6'],
            'phone' => ['required_with:validation_code', 'nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'qr_code_data.required_without' => 'Le QR code ou le code de validation est requis.',
            'validation_code.required_without' => 'Le code de validation ou le QR code est requis.',
            'phone.required_with' => 'Le numéro de téléphone est requis avec le code de validation.',
            'validation_code.size' => 'Le code de validation doit contenir exactement 6 chiffres.',
        ];
    }
}
