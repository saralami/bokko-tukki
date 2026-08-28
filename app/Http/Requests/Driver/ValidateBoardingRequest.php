<?php

namespace App\Http\Requests\Driver;

use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ValidateBoardingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasUserRole(UserRole::Driver);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reference' => ['required', 'string', 'max:32'],
            'trip_id' => ['nullable', 'integer'],
        ];
    }

    /**
     * Normalize the scanned/typed reference (trim, uppercase, strip a scanned URL prefix).
     */
    protected function prepareForValidation(): void
    {
        $reference = trim((string) $this->input('reference', ''));

        // A scanned QR may encode a URL or "ref:AD-XXXX"; keep only the reference token.
        if (preg_match('/(AD-[A-Z0-9]{8})/i', $reference, $matches) === 1) {
            $reference = $matches[1];
        }

        $this->merge(['reference' => strtoupper($reference)]);
    }
}
