<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadMarketplaceListingImageRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return ['image' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'], 'alt_text' => ['nullable', 'string', 'max:180']];
    }
}
