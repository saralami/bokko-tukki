<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Enums\TripStatus;
use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasUserRole(UserRole::Passenger);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'trip_id' => [
                'required',
                Rule::exists('trips', 'id')->where('status', TripStatus::Published->value),
            ],
            'seats' => ['required', 'integer', 'min:1', 'max:20'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'idempotency_key' => ['nullable', 'string', 'max:255'],
        ];
    }
}
