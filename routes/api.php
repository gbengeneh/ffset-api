<?php

use App\Http\Controllers\Api\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Api\Admin\CompetitionController as AdminCompetitionController;
use App\Http\Controllers\Api\Admin\CompetitionRegistrationController;
use App\Http\Controllers\Api\Admin\ContactMessageController as AdminContactMessageController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\EventController as AdminEventController;
use App\Http\Controllers\Api\Admin\GalleryController as AdminGalleryController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Admin\SaleController;
use App\Http\Controllers\Api\Admin\SalesReportController;
use App\Http\Controllers\Api\Admin\StaffController;
use App\Http\Controllers\Api\Admin\StockMovementController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\CompetitionController;
use App\Http\Controllers\Api\ContactMessageController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\GalleryController;
use App\Http\Controllers\Api\PlayerController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

// Auth (shared across admin, cashier, and player accounts)
Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
});

// Public catalog + submissions
Route::get('/products', [ProductController::class, 'index']);
Route::get('/events', [EventController::class, 'index']);
Route::get('/competitions', [CompetitionController::class, 'index']);
Route::get('/competitions/{competition}', [CompetitionController::class, 'show']);
Route::get('/gallery', [GalleryController::class, 'index']);

Route::middleware('throttle:10,1')->group(function () {
    Route::post('/competitions/{competition}/register', [CompetitionController::class, 'register']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::post('/contact', [ContactMessageController::class, 'store']);
});

// Player (self-serve accounts)
Route::middleware(['auth:sanctum', 'role:player'])->prefix('player')->group(function () {
    Route::get('/registrations', [PlayerController::class, 'registrations']);
    Route::get('/bookings', [PlayerController::class, 'bookings']);
});

// Admin + cashier (staff accounts)
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    // Shared: POS operations and read-only stock visibility
    Route::middleware('role:admin,cashier')->group(function () {
        Route::get('/products', [AdminProductController::class, 'index']);
        Route::get('/sales', [SaleController::class, 'index']);
        Route::post('/sales', [SaleController::class, 'store']);
        Route::get('/sales/{sale}', [SaleController::class, 'show']);
        Route::get('/dashboard/cashier', [DashboardController::class, 'cashier']);
    });

    // Admin-only: content management, financials, staff, and stock control
    Route::middleware('role:admin')->group(function () {
        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
        Route::get('/reports/sales', [SalesReportController::class, 'sales']);
        Route::patch('/sales/{sale}/status', [SaleController::class, 'updateStatus']);

        Route::apiResource('products', AdminProductController::class)->only(['store', 'update', 'destroy']);
        Route::post('/products/{product}/restock', [AdminProductController::class, 'restock']);
        Route::get('/stock-movements', [StockMovementController::class, 'index']);

        Route::apiResource('events', AdminEventController::class)->except(['show']);
        Route::apiResource('competitions', AdminCompetitionController::class)->except(['show']);
        Route::apiResource('gallery', AdminGalleryController::class)->except(['show']);

        Route::get('/bookings', [AdminBookingController::class, 'index']);
        Route::patch('/bookings/{booking}', [AdminBookingController::class, 'update']);

        Route::get('/competition-registrations', [CompetitionRegistrationController::class, 'index']);
        Route::patch('/competition-registrations/{competitionRegistration}', [CompetitionRegistrationController::class, 'update']);

        Route::get('/messages', [AdminContactMessageController::class, 'index']);
        Route::patch('/messages/{contactMessage}', [AdminContactMessageController::class, 'update']);

        Route::apiResource('staff', StaffController::class)->only(['index', 'store', 'update', 'destroy']);
    });
});
