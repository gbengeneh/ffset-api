<?php

use App\Http\Controllers\Api\Admin\AnalyticsController;
use App\Http\Controllers\Api\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Api\Admin\CarController as AdminCarController;
use App\Http\Controllers\Api\Admin\CarOrderController as AdminCarOrderController;
use App\Http\Controllers\Api\Admin\CashShiftController;
use App\Http\Controllers\Api\Admin\CompetitionController as AdminCompetitionController;
use App\Http\Controllers\Api\Admin\CompetitionRegistrationController;
use App\Http\Controllers\Api\Admin\ContactMessageController as AdminContactMessageController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\EventController as AdminEventController;
use App\Http\Controllers\Api\Admin\GalleryController as AdminGalleryController;
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Admin\PurchaseInvoiceController;
use App\Http\Controllers\Api\Admin\SaleController;
use App\Http\Controllers\Api\Admin\SalesReportController;
use App\Http\Controllers\Api\Admin\StaffController;
use App\Http\Controllers\Api\Admin\StockMovementController;
use App\Http\Controllers\Api\Admin\SupplierController;
use App\Http\Controllers\Api\Admin\MarketplaceCategoryController as AdminMarketplaceCategoryController;
use App\Http\Controllers\Api\Admin\MarketplaceListingController as AdminMarketplaceListingController;
use App\Http\Controllers\Api\Admin\MarketplaceOrderController as AdminMarketplaceOrderController;
use App\Http\Controllers\Api\Admin\MarketplaceVariantController as AdminMarketplaceVariantController;
use App\Http\Controllers\Api\Admin\MarketplaceDeliveryZoneController as AdminMarketplaceDeliveryZoneController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\CarController;
use App\Http\Controllers\Api\CarOrderController;
use App\Http\Controllers\Api\CompetitionController;
use App\Http\Controllers\Api\ContactMessageController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\GalleryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PlayerController;
use App\Http\Controllers\Api\MarketplaceController;
use App\Http\Controllers\Api\MarketplaceOrderController;
use App\Http\Controllers\Api\MarketplacePaymentController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HealthController;

Route::get('/health', HealthController::class)->middleware('throttle:60,1');

// Auth (shared across admin, cashier, and player accounts)
Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/email/resend', [AuthController::class, 'resendVerification'])->middleware('throttle:6,1');
});

Route::get('/auth/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::middleware('throttle:5,1')->group(function () {
    Route::post('/auth/password/forgot', [AuthController::class, 'forgotPassword']);
    Route::post('/auth/password/reset', [AuthController::class, 'resetPassword']);
});

// Payments (Paystack)
Route::post('/payments/sales/{sale}/initialize', [PaymentController::class, 'initialize'])->middleware('throttle:10,1');
Route::post('/payments/marketplace-orders/{order:reference_code}/initialize', [MarketplacePaymentController::class, 'initialize'])->middleware('throttle:10,1');
Route::get('/payments/marketplace/verify/{reference}', [MarketplacePaymentController::class, 'verify']);
Route::get('/payments/verify/{reference}', [PaymentController::class, 'verify']);
Route::post('/payments/webhook/paystack', [PaymentController::class, 'webhook']);

// Public catalog + submissions
Route::get('/products', [ProductController::class, 'index']);
Route::get('/events', [EventController::class, 'index']);
Route::get('/competitions', [CompetitionController::class, 'index']);
Route::get('/competitions/{competition}', [CompetitionController::class, 'show']);
Route::get('/gallery', [GalleryController::class, 'index']);
Route::get('/cars', [CarController::class, 'index']);
Route::get('/cars/{car}', [CarController::class, 'show']);
Route::get('/marketplace/categories', [MarketplaceController::class, 'categories']);
Route::get('/marketplace/delivery-zones', [MarketplaceController::class, 'deliveryZones']);
Route::get('/marketplace/listings', [MarketplaceController::class, 'listings']);
Route::get('/marketplace/listings/{listing:slug}', [MarketplaceController::class, 'show']);

Route::middleware('throttle:10,1')->group(function () {
    Route::post('/competitions/{competition}/register', [CompetitionController::class, 'register']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::post('/contact', [ContactMessageController::class, 'store']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::post('/cars/{car}/reserve', [CarOrderController::class, 'store']);
    Route::post('/marketplace/orders', [MarketplaceOrderController::class, 'store']);
});

// Player (self-serve accounts)
Route::middleware(['auth:sanctum', 'role:player'])->prefix('player')->group(function () {
    Route::get('/profile', [PlayerController::class, 'profile']);
    Route::put('/profile', [PlayerController::class, 'updateProfile']);
    Route::get('/marketplace-orders', [PlayerController::class, 'marketplaceOrders']);
    Route::get('/marketplace-orders/{order}', [PlayerController::class, 'marketplaceOrder']);
    Route::get('/registrations', [PlayerController::class, 'registrations']);
    Route::get('/bookings', [PlayerController::class, 'bookings']);
});

// Store-facing customer account routes. The role name remains internal for legacy compatibility.
Route::middleware(['auth:sanctum', 'role:player'])->prefix('customer')->group(function () {
    Route::get('/profile', [PlayerController::class, 'profile']);
    Route::put('/profile', [PlayerController::class, 'updateProfile']);
    Route::get('/orders', [PlayerController::class, 'marketplaceOrders']);
    Route::get('/orders/{order}', [PlayerController::class, 'marketplaceOrder']);
});

// Admin + cashier + inventory (staff accounts)
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    // Shared: read-only product catalog (everyone with a staff account needs to browse it)
    Route::middleware('role:admin,cashier,inventory')->group(function () {
        Route::get('/products', [AdminProductController::class, 'index']);
    });

    // Shared: POS operations and shift management
    Route::middleware('role:admin,cashier')->group(function () {
        Route::get('/sales', [SaleController::class, 'index']);
        Route::post('/sales', [SaleController::class, 'store']);
        Route::get('/sales/{sale}', [SaleController::class, 'show']);
        Route::get('/dashboard/cashier', [DashboardController::class, 'cashier']);

        Route::get('/shifts', [CashShiftController::class, 'index']);
        Route::get('/shifts/current', [CashShiftController::class, 'current']);
        Route::post('/shifts/open', [CashShiftController::class, 'open']);
        Route::post('/shifts/{cashShift}/close', [CashShiftController::class, 'close']);
    });

    // Shared: catalog management, purchasing, suppliers, and stock control
    Route::middleware('role:admin,inventory')->group(function () {
        Route::apiResource('products', AdminProductController::class)->only(['store', 'update', 'destroy']);
        Route::post('/products/{product}/restock', [AdminProductController::class, 'restock']);
        Route::post('/products/{product}/image', [AdminProductController::class, 'uploadImage']);
        Route::get('/stock-movements', [StockMovementController::class, 'index']);

        Route::apiResource('suppliers', SupplierController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::apiResource('purchase-invoices', PurchaseInvoiceController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
        Route::patch('/purchase-invoices/{purchaseInvoice}/status', [PurchaseInvoiceController::class, 'updateStatus']);
        Route::patch('/purchase-invoices/{purchaseInvoice}/payment-status', [PurchaseInvoiceController::class, 'updatePaymentStatus']);
    });

    // Admin-only: content management, financials, staff
    Route::middleware('role:admin')->group(function () {
        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
        Route::get('/reports/sales', [SalesReportController::class, 'sales']);
        Route::patch('/sales/{sale}/status', [SaleController::class, 'updateStatus']);

        Route::get('/analytics/top-products', [AnalyticsController::class, 'topProducts']);
        Route::get('/analytics/competitions', [AnalyticsController::class, 'competitions']);
        Route::get('/analytics/bookings', [AnalyticsController::class, 'bookings']);
        Route::get('/analytics/players', [AnalyticsController::class, 'players']);

        Route::apiResource('events', AdminEventController::class)->except(['show']);
        Route::apiResource('competitions', AdminCompetitionController::class)->except(['show']);
        Route::patch('/competitions/{competition}/status', [AdminCompetitionController::class, 'updateStatus']);
        Route::apiResource('gallery', AdminGalleryController::class)->except(['show']);

        Route::get('/bookings', [AdminBookingController::class, 'index']);
        Route::patch('/bookings/{booking}', [AdminBookingController::class, 'update']);

        Route::get('/competition-registrations', [CompetitionRegistrationController::class, 'index']);
        Route::patch('/competition-registrations/{competitionRegistration}', [CompetitionRegistrationController::class, 'update']);

        Route::get('/orders', [AdminOrderController::class, 'index']);
        Route::patch('/orders/{order}', [AdminOrderController::class, 'update']);

        Route::get('/messages', [AdminContactMessageController::class, 'index']);
        Route::patch('/messages/{contactMessage}', [AdminContactMessageController::class, 'update']);

        Route::apiResource('staff', StaffController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::apiResource('cars', AdminCarController::class)->except(['show']);
        Route::patch('/cars/{car}/status', [AdminCarController::class, 'updateStatus']);
        Route::post('/cars/{car}/image', [AdminCarController::class, 'uploadImage']);
        Route::delete('/cars/{car}/images/{image}', [AdminCarController::class, 'deleteImage']);

        Route::get('/car-orders', [AdminCarOrderController::class, 'index']);
        Route::patch('/car-orders/{carOrder}', [AdminCarOrderController::class, 'update']);

        Route::apiResource('marketplace/categories', AdminMarketplaceCategoryController::class)->except(['show']);
        Route::apiResource('marketplace/listings', AdminMarketplaceListingController::class)->except(['show']);
        Route::post('/marketplace/listings/{listing}/image', [AdminMarketplaceListingController::class, 'uploadImage']);
        Route::delete('/marketplace/listings/{listing}/images/{image}', [AdminMarketplaceListingController::class, 'deleteImage']);
        Route::get('/marketplace/orders', [AdminMarketplaceOrderController::class, 'index']);
        Route::get('/marketplace/orders/{order}', [AdminMarketplaceOrderController::class, 'show']);
        Route::patch('/marketplace/orders/{order}', [AdminMarketplaceOrderController::class, 'update']);
        Route::post('/marketplace/orders/{order}/refund', [AdminMarketplaceOrderController::class, 'refund']);
        Route::post('/marketplace/listings/{listing}/variants/generate', [AdminMarketplaceVariantController::class, 'generate']);
        Route::patch('/marketplace/listings/{listing}/variants/{variant}', [AdminMarketplaceVariantController::class, 'update']);
        Route::delete('/marketplace/listings/{listing}/variants/{variant}', [AdminMarketplaceVariantController::class, 'destroy']);
        Route::apiResource('marketplace/delivery-zones', AdminMarketplaceDeliveryZoneController::class)->except(['show']);
    });
});
