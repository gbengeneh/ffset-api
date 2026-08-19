<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CompetitionRegistrationResource;
use App\Models\Booking;
use App\Models\CompetitionRegistration;
use Illuminate\Http\Request;
use App\Http\Requests\UpdatePlayerProfileRequest;
use App\Http\Resources\MarketplaceOrderResource;
use App\Models\MarketplaceOrder;

class PlayerController extends Controller
{
    public function profile(Request $request)
    {
        return response()->json($this->profileData($request->user()->load('preferredDeliveryZone')));
    }

    public function updateProfile(UpdatePlayerProfileRequest $request)
    {
        $user = $request->user();
        $emailChanged = $user->email !== $request->validated('email');
        $user->update($request->validated());

        if ($emailChanged) {
            $user->forceFill(['email_verified_at' => null])->save();
        }

        return response()->json($this->profileData($user->fresh('preferredDeliveryZone')));
    }

    public function marketplaceOrders(Request $request)
    {
        $orders = MarketplaceOrder::where('player_id', $request->user()->id)
            ->with(['items', 'deliveryZone'])
            ->latest()
            ->paginate(15);

        return MarketplaceOrderResource::collection($orders);
    }

    public function marketplaceOrder(Request $request, MarketplaceOrder $order)
    {
        abort_unless($order->player_id === $request->user()->id, 404);

        return new MarketplaceOrderResource($order->load(['items', 'deliveryZone']));
    }

    public function registrations(Request $request)
    {
        $registrations = CompetitionRegistration::where('player_id', $request->user()->id)
            ->with('competition')
            ->latest()
            ->get();

        return CompetitionRegistrationResource::collection($registrations);
    }

    public function bookings(Request $request)
    {
        $bookings = Booking::where('player_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json($bookings);
    }

    private function profileData($user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'delivery_address' => $user->delivery_address,
            'preferred_fulfillment_type' => $user->preferred_fulfillment_type,
            'preferred_delivery_zone_id' => $user->preferred_delivery_zone_id,
            'whatsapp_opt_in' => $user->whatsapp_opt_in,
            'email_verified' => $user->hasVerifiedEmail(),
            'preferred_delivery_zone' => $user->preferredDeliveryZone,
        ];
    }
}
