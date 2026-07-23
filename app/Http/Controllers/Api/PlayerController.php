<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CompetitionRegistrationResource;
use App\Models\Booking;
use App\Models\CompetitionRegistration;
use Illuminate\Http\Request;

class PlayerController extends Controller
{
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
}
