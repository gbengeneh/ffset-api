<?php

use App\Http\Controllers\Api\Admin\AuthController as AdminAuthController;
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
use App\Http\Controllers\Api\Admin\StockMovementController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\CompetitionController;
use App\Http\Controllers\Api\ContactMessageController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\GalleryController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Public
Route::get('/products', [ProductController::class, 'index']);
Route::get('/events', [EventController::class, 'index']);
Route::get('/competitions', [CompetitionController::class, 'index']);
Route::get('/competitions/{competition}', [CompetitionController::class, 'show']);
Route::post('/competitions/{competition}/register', [CompetitionController::class, 'register']);
Route::get('/gallery', [GalleryController::class, 'index']);
Route::post('/bookings', [BookingController::class, 'store']);
Route::post('/contact', [ContactMessageController::class, 'store']);

Route::post('/admin/login', [AdminAuthController::class, 'login']);

// Admin
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::post('/logout', [AdminAuthController::class, 'logout']);

    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/reports/sales', [SalesReportController::class, 'sales']);

    Route::apiResource('products', AdminProductController::class)->except(['show']);
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

    Route::get('/sales', [SaleController::class, 'index']);
    Route::post('/sales', [SaleController::class, 'store']);
    Route::get('/sales/{sale}', [SaleController::class, 'show']);
    Route::patch('/sales/{sale}/status', [SaleController::class, 'updateStatus']);
});
