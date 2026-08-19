<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('staff'))],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['sometimes', 'in:admin,cashier,inventory'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
