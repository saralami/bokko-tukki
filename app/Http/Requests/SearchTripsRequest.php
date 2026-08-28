<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchTripsRequest extends FormRequest
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
            'departure_destination_id' => ['nullable', 'integer', Rule::exists('destinations', 'id')],
            'arrival_destination_id' => ['nullable', 'integer', Rule::exists('destinations', 'id')],
            'date' => ['nullable', 'date'],
            'seats' => ['nullable', 'integer', 'min:1', 'max:100'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'radius' => ['nullable', 'numeric', 'min:1', 'max:2000'],
            'sort' => ['nullable', Rule::in(['relevance', 'price', 'date'])],
        ];
    }

    /**
     * Get the search criteria as a normalized array.
     *
     * @return array<string, mixed>
     */
    public function criteria(): array
    {
        return $this->validated();
    }
}
