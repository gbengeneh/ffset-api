<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CompetitionRegistration;
use App\Models\ContactMessage;
use App\Models\Event;
use App\Models\Product;
use App\Models\Sale;

class DashboardController extends Controller
{
    public function stats()
    {
        return response()->json([
            'total_registrations' => CompetitionRegistration::count(),
            'total_bookings' => Booking::count(),
            'upcoming_events' => Event::count(),
            'available_products' => Product::where('status', 'active')->count(),
            'low_stock_products' => Product::whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                ->where('is_stocked', true)
                ->count(),
            'recent_messages' => ContactMessage::where('status', 'new')->count(),
            'revenue_today' => (float) Sale::where('status', 'completed')
                ->whereDate('created_at', today())
                ->sum('total'),
            'revenue_this_month' => (float) Sale::where('status', 'completed')
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->sum('total'),
        ]);
    }
}
