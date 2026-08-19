<?php

namespace App\Http\Requests;

use App\Models\MarketplaceCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMarketplaceListingRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $listing = $this->route('listing');
        $category = MarketplaceCategory::find($this->input('category_id'));
        $vehicleRules = $category?->slug === 'autos' ? ['required', 'array'] : ['nullable', 'array'];
        $fashionRules = $category?->slug === 'fashion' ? ['required', 'array'] : ['nullable', 'array'];
        $gadgetRules = $category?->slug === 'gadgets' ? ['required', 'array'] : ['nullable', 'array'];

        return [
            'category_id' => ['required', 'exists:marketplace_categories,id'],
            'name' => ['required', 'string', 'max:180'],
            'slug' => ['required', 'alpha_dash', 'max:200', Rule::unique('marketplace_listings')->ignore($listing)],
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('marketplace_listings')->ignore($listing)],
            'short_description' => ['nullable', 'string', 'max:500'], 'description' => ['nullable', 'string', 'max:10000'],
            'price' => ['required', 'numeric', 'min:0'], 'compare_at_price' => ['nullable', 'numeric', 'gte:price'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0', 'lte:price'],
            'condition' => ['required', 'in:new,used,refurbished,certified_pre_owned'],
            'status' => ['required', 'in:draft,active,reserved,sold,out_of_stock,archived'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'], 'is_featured' => ['boolean'],
            'attributes' => ['nullable', 'array'], 'attributes.*' => ['nullable'],
            'published_at' => ['nullable', 'date'], 'vehicle' => $vehicleRules,
            'vehicle.make' => ['required_with:vehicle', 'string', 'max:100'],
            'vehicle.model' => ['required_with:vehicle', 'string', 'max:100'],
            'vehicle.year' => ['required_with:vehicle', 'integer', 'min:1900', 'max:'.(date('Y') + 1)],
            'vehicle.mileage' => ['nullable', 'integer', 'min:0'], 'vehicle.transmission' => ['nullable', 'string', 'max:50'],
            'vehicle.fuel_type' => ['nullable', 'string', 'max:50'], 'vehicle.color' => ['nullable', 'string', 'max:100'],
            'vehicle.vin' => ['nullable', 'string', 'max:64', Rule::unique('marketplace_vehicle_details', 'vin')->ignore($listing?->vehicleDetails?->id)],
            'vehicle.features' => ['nullable', 'array'], 'vehicle.features.*' => ['string', 'max:120'],
            'fashion' => $fashionRules, 'fashion.brand' => ['nullable', 'string', 'max:100'],
            'fashion.gender' => ['nullable', 'in:men,women,unisex,kids'], 'fashion.material' => ['nullable', 'string', 'max:100'],
            'fashion.genders' => ['nullable', 'array'], 'fashion.genders.*' => ['distinct', 'in:men,women,unisex,kids'],
            'fashion.sizes' => ['nullable', 'array'], 'fashion.sizes.*' => ['string', 'max:30'],
            'fashion.colors' => ['nullable', 'array'], 'fashion.colors.*' => ['string', 'max:50'],
            'gadget' => $gadgetRules, 'gadget.brand' => ['nullable', 'string', 'max:100'],
            'gadget.model' => ['nullable', 'string', 'max:100'], 'gadget.storage' => ['nullable', 'string', 'max:50'],
            'gadget.memory' => ['nullable', 'string', 'max:50'], 'gadget.warranty' => ['nullable', 'string', 'max:100'],
            'gadget.specifications' => ['nullable', 'array'], 'gadget.specifications.*' => ['nullable'],
        ];
    }
}
