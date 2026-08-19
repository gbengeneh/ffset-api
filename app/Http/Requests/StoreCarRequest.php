<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'make' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'year' => ['required', 'integer', 'min:1980', 'max:'.(date('Y') + 1)],
            'price' => ['required', 'numeric', 'min:0'],
            'deposit_amount' => ['required', 'numeric', 'min:0'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'condition' => ['required', 'in:new,used,certified_pre_owned'],
            'transmission' => ['required', 'in:automatic,manual'],
            'fuel_type' => ['required', 'in:petrol,diesel,hybrid,electric'],
            'color' => ['nullable', 'string', 'max:100'],
            'vin' => [
                'nullable', 'string', 'max:64',
                Rule::unique('cars', 'vin')->ignore($this->route('car')),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:120'],
            'status' => ['required', 'in:available,reserved,sold'],
        ];
    }
}
