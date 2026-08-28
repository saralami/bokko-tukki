<?php

namespace App\Http\Requests;

use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Vehicle::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'registration' => ['required', 'string', 'max:255', Rule::unique('vehicles', 'registration')],
            'brand' => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1', 'max:120'],
            'status' => ['nullable', Rule::enum(VehicleStatus::class)],
            'driver_id' => [
                'nullable',
                Rule::exists('drivers', 'id')->where('transporter_id', $this->user()->transporter?->id),
            ],
        ];
    }
}
