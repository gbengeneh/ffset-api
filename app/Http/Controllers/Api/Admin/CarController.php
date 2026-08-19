<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCarRequest;
use App\Http\Requests\UploadCarImageRequest;
use App\Http\Resources\CarResource;
use App\Models\Car;
use App\Models\CarImage;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CarController extends Controller
{
    public function index(Request $request)
    {
        $cars = Car::query()
            ->with('images')
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->get();

        return CarResource::collection($cars);
    }

    public function show(Car $car)
    {
        return new CarResource($car->load('images'));
    }

    public function store(StoreCarRequest $request)
    {
        $validated = $request->validated();
        $car = Car::create($validated);
        $this->syncDepositProduct($car);

        return (new CarResource($car->fresh('images')))->response()->setStatusCode(201);
    }

    public function update(StoreCarRequest $request, Car $car)
    {
        $car->update($request->validated());
        $this->syncDepositProduct($car);

        return new CarResource($car->fresh('images'));
    }

    public function destroy(Car $car)
    {
        $car->delete();

        return response()->json(null, 204);
    }

    public function updateStatus(Request $request, Car $car)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:available,reserved,sold'],
        ]);

        $car->update($validated);

        return new CarResource($car->fresh('images'));
    }

    public function uploadImage(UploadCarImageRequest $request, Car $car)
    {
        $path = $request->file('image')->store('cars', 'public');
        $nextSortOrder = ((int) $car->images()->max('sort_order')) + 1;

        CarImage::create([
            'car_id' => $car->id,
            'image_url' => url(Storage::url($path)),
            'sort_order' => $nextSortOrder,
        ]);

        return new CarResource($car->fresh('images'));
    }

    public function deleteImage(Car $car, CarImage $image)
    {
        if ($image->car_id !== $car->id) {
            return response()->json(['message' => 'Image does not belong to this car.'], 404);
        }

        $storagePath = parse_url($image->image_url, PHP_URL_PATH);
        if ($storagePath && Str::startsWith($storagePath, '/storage/')) {
            Storage::disk('public')->delete(Str::after($storagePath, '/storage/'));
        }

        $image->delete();

        return new CarResource($car->fresh('images'));
    }

    /**
     * Keep the hidden "deposit" service Product in sync with the car's
     * current deposit amount, mirroring how Competition.entry_fee_product_id
     * links a competition to the product SaleService uses to build a
     * SaleItem — see app/Http/Controllers/Api/CompetitionController.php.
     */
    private function syncDepositProduct(Car $car): void
    {
        $name = "Deposit — {$car->year} {$car->make} {$car->model}";

        if ($car->deposit_product_id) {
            Product::whereKey($car->deposit_product_id)->update([
                'name' => $name,
                'price' => $car->deposit_amount,
            ]);

            return;
        }

        $product = Product::create([
            'name' => $name,
            'type' => 'service',
            'price' => $car->deposit_amount,
            'is_stocked' => false,
            'status' => 'active',
        ]);

        $car->update(['deposit_product_id' => $product->id]);
    }
}
