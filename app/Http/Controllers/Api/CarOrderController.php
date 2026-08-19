<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCarOrderRequest;
use App\Http\Resources\CarOrderResource;
use App\Models\Car;
use App\Models\CarOrder;
use App\Models\User;
use App\Services\SaleService;
use App\Services\TelegramNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CarOrderController extends Controller
{
    public function __construct(
        private SaleService $sales,
        private TelegramNotifier $telegram,
    ) {}

    public function store(StoreCarOrderRequest $request, Car $car)
    {
        if ($car->status !== 'available') {
            return response()->json(['message' => 'This car is no longer available for reservation.'], 422);
        }

        if (! $car->deposit_product_id) {
            return response()->json(['message' => 'This car is not currently open for reservation.'], 422);
        }

        /** @var User|null $player */
        $player = $request->user('sanctum');
        $player = $player && $player->role === User::ROLE_PLAYER ? $player : null;

        $validated = $request->validated();

        $carOrder = DB::transaction(function () use ($validated, $player, $car) {
            $carOrder = CarOrder::create([
                'car_id' => $car->id,
                'player_id' => $player?->id,
                'reference_code' => 'FFA-'.strtoupper(Str::random(8)),
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'],
                'notes' => $validated['notes'] ?? null,
                'status' => 'pending',
            ]);

            $sale = $this->sales->createSale([
                'source' => 'car_deposit',
                'reference_id' => $carOrder->id,
                'status' => 'pending',
                'customer_name' => $carOrder->name,
                'customer_email' => $carOrder->email,
                'items' => [
                    ['product_id' => $car->deposit_product_id, 'quantity' => 1],
                ],
            ]);

            $carOrder->update(['sale_id' => $sale->id]);
            $car->update(['status' => 'reserved']);

            return $carOrder;
        });

        $carOrder->load('sale.items.product', 'car');

        $this->telegram->send('Car Reservation', [
            'Reference' => $carOrder->reference_code,
            'Car' => "{$car->year} {$car->make} {$car->model}",
            'Full Name' => $carOrder->name,
            'Phone Number' => $carOrder->phone,
            'Email' => $carOrder->email,
            'Notes' => $carOrder->notes ?? '-',
            'Deposit' => '₦'.number_format((float) $car->deposit_amount, 2),
        ]);

        return (new CarOrderResource($carOrder))->response()->setStatusCode(201);
    }
}
