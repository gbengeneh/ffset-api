<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:255'],
            'fulfillment_type' => ['required', 'in:pickup,delivery'],
            'delivery_address' => ['required_if:fulfillment_type,delivery', 'nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $items = $this->input('items', []);

            if (! is_array($items)) {
                return;
            }

            $productIds = collect($items)->pluck('product_id')->filter()->unique();
            $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

            foreach ($items as $index => $item) {
                $product = $products->get($item['product_id'] ?? null);

                if (! $product) {
                    continue;
                }

                if ($product->status !== 'active' || $product->type === 'service') {
                    $validator->errors()->add(
                        "items.{$index}.product_id",
                        "\"{$product->name}\" is not available for online order."
                    );

                    continue;
                }

                $quantity = (int) ($item['quantity'] ?? 0);

                if ($product->is_stocked && $product->stock_quantity !== null && $quantity > $product->stock_quantity) {
                    $validator->errors()->add(
                        "items.{$index}.quantity",
                        "Only {$product->stock_quantity} of \"{$product->name}\" left in stock."
                    );
                }
            }
        });
    }
}
