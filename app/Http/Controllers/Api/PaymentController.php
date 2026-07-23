<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompetitionRegistration;
use App\Models\Sale;
use App\Services\PaystackService;
use App\Services\SaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function __construct(
        private PaystackService $paystack,
        private SaleService $sales,
    ) {}

    public function initialize(Request $request, Sale $sale)
    {
        if ($sale->status !== 'pending') {
            return response()->json(['message' => 'This sale is not awaiting payment.'], 422);
        }

        $validated = $request->validate([
            'email' => ['nullable', 'email'],
        ]);

        $email = $validated['email'] ?? $sale->customer_email;

        if (! $email) {
            return response()->json(['message' => 'An email address is required to initialize payment.'], 422);
        }

        if (! $sale->payment_reference) {
            $sale->update([
                'payment_reference' => 'FFP-'.strtoupper(Str::random(12)),
                'customer_email' => $email,
            ]);
        }

        $data = $this->paystack->initialize([
            'email' => $email,
            'amount' => (int) round($sale->total * 100),
            'reference' => $sale->payment_reference,
            'callback_url' => config('app.frontend_url').'/payment/callback',
            'metadata' => ['sale_id' => $sale->id],
        ]);

        return response()->json($data);
    }

    public function verify(string $reference)
    {
        $sale = Sale::where('payment_reference', $reference)->firstOrFail();

        $data = $this->paystack->verify($reference);

        if (($data['status'] ?? null) === 'success') {
            $sale = $this->confirmPayment($sale);
        }

        return response()->json($sale->fresh('items.product'));
    }

    public function webhook(Request $request)
    {
        $signature = $request->header('X-Paystack-Signature');

        if (! $this->paystack->verifyWebhookSignature($request->getContent(), $signature)) {
            return response()->json(['message' => 'Invalid signature.'], 400);
        }

        $payload = $request->json()->all();

        if (($payload['event'] ?? null) === 'charge.success') {
            $reference = $payload['data']['reference'] ?? null;
            $sale = $reference ? Sale::where('payment_reference', $reference)->first() : null;

            if ($sale) {
                $this->confirmPayment($sale);
            }
        }

        return response()->json(['message' => 'ok']);
    }

    private function confirmPayment(Sale $sale): Sale
    {
        $sale = $this->sales->completeSale($sale);

        $registration = CompetitionRegistration::where('sale_id', $sale->id)->first();

        if ($registration && $registration->payment_status !== 'paid') {
            $registration->update(['payment_status' => 'paid']);
        }

        return $sale;
    }
}
