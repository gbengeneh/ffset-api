<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompetitionRequest;
use App\Models\Competition;
use Illuminate\Http\Request;

class CompetitionController extends Controller
{
    public function index()
    {
        return response()->json(Competition::orderBy('id')->get());
    }

    public function store(StoreCompetitionRequest $request)
    {
        return response()->json(Competition::create($request->validated()), 201);
    }

    public function update(StoreCompetitionRequest $request, Competition $competition)
    {
        $competition->update($request->validated());

        return response()->json($competition);
    }

    public function destroy(Competition $competition)
    {
        $competition->delete();

        return response()->json(null, 204);
    }

    public function updateStatus(Request $request, Competition $competition)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:upcoming,open,closed,completed'],
        ]);

        $competition->update($validated);

        return response()->json($competition);
    }
}
