<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlayerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user()->id)],
            'phone' => ['required', 'string', 'max:30'],
            'delivery_address' => ['nullable', 'string', 'max:2000'],
            'preferred_fulfillment_type' => ['required', Rule::in(['pickup', 'delivery'])],
            'preferred_delivery_zone_id' => ['nullable', 'integer', 'exists:marketplace_delivery_zones,id'],
            'whatsapp_opt_in' => ['required', 'boolean'],
        ];
    }
}
