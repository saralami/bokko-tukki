<?php

namespace App\Http\Requests;

use App\Enums\DestinationStatus;
use App\Models\Trip;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTripRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Trip::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $transporterId = $this->user()->transporter?->id;

        return [
            'vehicle_id' => ['required', Rule::exists('vehicles', 'id')->where('transporter_id', $transporterId)],
            'driver_id' => ['nullable', Rule::exists('drivers', 'id')->where('transporter_id', $transporterId)],
            'departure_destination_id' => ['required', Rule::exists('destinations', 'id')->where('status', DestinationStatus::Active->value)],
            'arrival_destination_id' => [
                'required',
                'different:departure_destination_id',
                Rule::exists('destinations', 'id')->where('status', DestinationStatus::Active->value),
            ],
            'departure_date' => ['required', 'date', 'after_or_equal:today'],
            'departure_time' => ['required', 'date_format:H:i'],
            'price_per_seat' => ['required', 'integer', 'min:0'],
        ];
    }
}
