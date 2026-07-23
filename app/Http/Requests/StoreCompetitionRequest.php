<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompetitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'entry_fee_product_id' => ['nullable', 'integer', 'exists:products,id'],
            'entry_fee' => ['required', 'numeric', 'min:0'],
            'first_prize' => ['required', 'numeric', 'min:0'],
            'second_prize' => ['required', 'numeric', 'min:0'],
            'third_prize' => ['required', 'numeric', 'min:0'],
            'rules' => ['nullable', 'array'],
            'rules.*' => ['string'],
            'status' => ['required', 'in:upcoming,open,closed,completed'],
        ];
    }
}
