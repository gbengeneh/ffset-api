<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CompetitionRegistration;
use App\Models\ContactMessage;
use App\Models\Event;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function cashier(Request $request)
    {
        $user = $request->user();

        $ownSales = Sale::where('status', 'completed')
            ->whereDate('created_at', today())
            ->when($user->role === User::ROLE_CASHIER, fn ($query) => $query->where('staff_id', $user->id));

        return response()->json([
            'sales_today_count' => (clone $ownSales)->count(),
            'sales_today_total' => (float) (clone $ownSales)->sum('total'),
            'low_stock_products' => Product::where('is_stocked', true)
                ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                ->get(['id', 'name', 'stock_quantity', 'low_stock_threshold']),
        ]);
    }

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
