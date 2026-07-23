<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompetitionRegistration;
use App\Services\SaleService;
use Illuminate\Http\Request;

class CompetitionRegistrationController extends Controller
{
    public function __construct(private SaleService $sales) {}

    public function index(Request $request)
    {
        $registrations = CompetitionRegistration::query()
            ->with('competition')
            ->when($request->query('competition_id'), fn ($query, $id) => $query->where('competition_id', $id))
            ->latest()
            ->paginate(20);

        return response()->json($registrations);
    }

    public function update(Request $request, CompetitionRegistration $competitionRegistration)
    {
        $validated = $request->validate([
            'payment_status' => ['required', 'in:pending,paid'],
        ]);

        if ($validated['payment_status'] === 'paid' && $competitionRegistration->sale) {
            $this->sales->completeSale($competitionRegistration->sale, $request->user()->id);
        }

        $competitionRegistration->update($validated);

        return response()->json($competitionRegistration->fresh('sale'));
    }
}
