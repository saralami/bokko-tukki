<?php

namespace App\Http\Requests\Driver;

use App\Enums\IncidentCategory;
use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIncidentRequest extends FormRequest
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
            'trip_id' => ['required', 'integer', 'exists:trips,id'],
            'category' => ['required', Rule::enum(IncidentCategory::class)],
            'message' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }
}
