<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Services\TelegramNotifier;

class BookingController extends Controller
{
    public function __construct(private TelegramNotifier $telegram) {}

    public function store(StoreBookingRequest $request)
    {
        $booking = Booking::create($request->validated());

        $this->telegram->send('Table Booking', [
            'Full Name' => $booking->name,
            'Phone Number' => $booking->phone,
            'Date' => $booking->date->toDateString(),
            'Time' => $booking->time,
            'Number of Guests' => (string) $booking->guests,
            'Occasion' => $booking->occasion ?? '',
            'Special Request' => $booking->special_request ?? '',
        ]);

        return response()->json($booking, 201);
    }
}
