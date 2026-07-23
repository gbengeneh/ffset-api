<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Competition;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function topProducts(Request $request)
    {
        $from = $request->query('from', now()->subDays(30)->toDateString());
        $to = $request->query('to', now()->toDateString());
        $limit = (int) $request->query('limit', 5);

        $rows = SaleItem::query()
            ->selectRaw('product_id, SUM(quantity) as total_quantity, SUM(line_total) as total_revenue')
            ->whereHas('sale', function ($query) use ($from, $to) {
                $query->where('status', 'completed')
                    ->whereDate('created_at', '>=', $from)
                    ->whereDate('created_at', '<=', $to);
            })
            ->with('product:id,name,type')
            ->groupBy('product_id')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get();

        return response()->json(['from' => $from, 'to' => $to, 'rows' => $rows]);
    }

    public function competitions()
    {
        $competitions = Competition::query()
            ->withCount([
                'registrations',
                'registrations as paid_registrations_count' => fn ($query) => $query->where('payment_status', 'paid'),
                'registrations as pending_registrations_count' => fn ($query) => $query->where('payment_status', 'pending'),
            ])
            ->get();

        $rows = $competitions->map(fn ($competition) => [
            'id' => $competition->id,
            'title' => $competition->title,
            'status' => $competition->status,
            'entry_fee' => $competition->entry_fee,
            'total_registrations' => $competition->registrations_count,
            'paid_registrations' => $competition->paid_registrations_count,
            'pending_registrations' => $competition->pending_registrations_count,
            'revenue' => $competition->paid_registrations_count * (float) $competition->entry_fee,
        ]);

        return response()->json($rows);
    }

    public function bookings(Request $request)
    {
        $from = $request->query('from', now()->subDays(30)->toDateString());
        $to = $request->query('to', now()->toDateString());

        $byStatus = Booking::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        $trend = Booking::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        return response()->json(['by_status' => $byStatus, 'from' => $from, 'to' => $to, 'trend' => $trend]);
    }

    public function players(Request $request)
    {
        $from = $request->query('from', now()->subDays(30)->toDateString());
        $to = $request->query('to', now()->toDateString());

        $totalPlayers = User::where('role', User::ROLE_PLAYER)->count();

        $trend = User::query()
            ->where('role', User::ROLE_PLAYER)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        return response()->json(['total_players' => $totalPlayers, 'from' => $from, 'to' => $to, 'trend' => $trend]);
    }
}
