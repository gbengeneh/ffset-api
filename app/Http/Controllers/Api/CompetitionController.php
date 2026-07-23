<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompetitionRegistrationRequest;
use App\Http\Resources\CompetitionRegistrationResource;
use App\Models\Competition;
use App\Models\CompetitionRegistration;
use App\Models\User;
use App\Services\SaleService;
use App\Services\TelegramNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CompetitionController extends Controller
{
    public function __construct(
        private SaleService $sales,
        private TelegramNotifier $telegram,
    ) {}

    public function index()
    {
        return response()->json(Competition::orderBy('id')->get());
    }

    public function show(Competition $competition)
    {
        return response()->json($competition);
    }

    public function register(StoreCompetitionRegistrationRequest $request, Competition $competition)
    {
        /** @var User|null $player */
        $player = $request->user('sanctum');
        $player = $player && $player->role === User::ROLE_PLAYER ? $player : null;

        $registration = DB::transaction(function () use ($request, $competition, $player) {
            $registration = CompetitionRegistration::create([
                ...$request->validated(),
                'competition_id' => $competition->id,
                'player_id' => $player?->id,
                'reference_code' => 'FFC-'.strtoupper(Str::random(8)),
                'payment_status' => 'pending',
            ]);

            if ($competition->entry_fee_product_id) {
                $sale = $this->sales->createSale([
                    'source' => 'competition_entry',
                    'reference_id' => $registration->id,
                    'status' => 'pending',
                    'customer_name' => $registration->name,
                    'items' => [
                        ['product_id' => $competition->entry_fee_product_id, 'quantity' => 1],
                    ],
                ]);

                $registration->update(['sale_id' => $sale->id]);
            }

            return $registration;
        });

        $this->telegram->send('Competition Registration', [
            'Registration ID' => $registration->reference_code,
            'Competition' => $competition->title,
            'Full Name' => $registration->name,
            'Phone Number' => $registration->phone,
            'Email' => $registration->email,
            'Gamertag' => $registration->gamertag,
            'Preferred Game' => $registration->game,
            'State' => $registration->state,
        ]);

        return (new CompetitionRegistrationResource($registration->fresh('sale')))
            ->response()
            ->setStatusCode(201);
    }
}
