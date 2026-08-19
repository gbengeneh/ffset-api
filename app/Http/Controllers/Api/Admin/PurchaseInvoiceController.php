<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePurchaseInvoiceRequest;
use App\Http\Requests\UpdatePurchaseInvoicePaymentStatusRequest;
use App\Models\PurchaseInvoice;
use App\Services\PurchaseService;
use Illuminate\Http\Request;

class PurchaseInvoiceController extends Controller
{
    public function __construct(private PurchaseService $purchases) {}

    public function index(Request $request)
    {
        $invoices = PurchaseInvoice::query()
            ->with('supplier:id,name')
            ->when($request->query('supplier_id'), fn ($query, $supplierId) => $query->where('supplier_id', $supplierId))
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->latest('invoice_date')
            ->paginate(20);

        return response()->json($invoices);
    }

    public function show(PurchaseInvoice $purchaseInvoice)
    {
        return response()->json($purchaseInvoice->load(['items.product', 'supplier']));
    }

    public function store(StorePurchaseInvoiceRequest $request)
    {
        $invoice = $this->purchases->createPurchaseInvoice($request->validated());

        if ($request->boolean('receive_immediately', true)) {
            $invoice = $this->purchases->receivePurchaseInvoice($invoice, $request->user()->id);
        }

        return response()->json($invoice, 201);
    }

    public function update(StorePurchaseInvoiceRequest $request, PurchaseInvoice $purchaseInvoice)
    {
        $invoice = $this->purchases->updatePurchaseInvoice($purchaseInvoice, $request->validated());

        if ($request->boolean('receive_immediately', true)) {
            $invoice = $this->purchases->receivePurchaseInvoice($invoice, $request->user()->id);
        }

        return response()->json($invoice);
    }

    public function destroy(PurchaseInvoice $purchaseInvoice)
    {
        if ($purchaseInvoice->status !== 'pending') {
            return response()->json(['message' => 'Only pending purchase invoices can be deleted.'], 422);
        }

        $purchaseInvoice->delete();

        return response()->json(null, 204);
    }

    public function updateStatus(Request $request, PurchaseInvoice $purchaseInvoice)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:received,cancelled'],
        ]);

        $invoice = $validated['status'] === 'received'
            ? $this->purchases->receivePurchaseInvoice($purchaseInvoice, $request->user()->id)
            : $this->purchases->cancelPurchaseInvoice($purchaseInvoice);

        return response()->json($invoice);
    }

    public function updatePaymentStatus(UpdatePurchaseInvoicePaymentStatusRequest $request, PurchaseInvoice $purchaseInvoice)
    {
        $invoice = $this->purchases->updatePaymentStatus($purchaseInvoice, $request->validated()['payment_status']);

        return response()->json($invoice);
    }
}
