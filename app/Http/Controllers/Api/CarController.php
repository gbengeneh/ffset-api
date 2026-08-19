<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CarResource;
use App\Models\Car;
use Illuminate\Http\Request;

class CarController extends Controller
{
    public function index(Request $request)
    {
        $cars = Car::query()
            ->with('images')
            ->when($request->query('make'), fn ($query, $make) => $query->where('make', $make))
            ->when($request->query('condition'), fn ($query, $condition) => $query->where('condition', $condition))
            ->when($request->query('year'), fn ($query, $year) => $query->where('year', $year))
            ->when($request->query('min_price'), fn ($query, $min) => $query->where('price', '>=', $min))
            ->when($request->query('max_price'), fn ($query, $max) => $query->where('price', '<=', $max))
            ->orderByDesc('created_at')
            ->get();

        return CarResource::collection($cars);
    }

    public function show(Car $car)
    {
        return new CarResource($car->load('images'));
    }
}
