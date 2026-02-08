<?php

use App\Http\Controllers\Api\Admin\BannerController;
use App\Http\Controllers\Api\Admin\FoodCourtReportController;
use App\Http\Controllers\Api\Admin\SellerController;
use App\Http\Controllers\Api\Admin\OrderController;
use App\Http\Controllers\Api\Admin\ReviewController;
use App\Http\Controllers\Api\Admin\SubscriptionPlanController;
use App\Http\Controllers\Api\CustomerReviewController;
use App\Http\Controllers\Api\Public\HomeController;
use App\Http\Controllers\Api\Seller\ProductController as SellerProductController;
use App\Http\Controllers\Api\Seller\ReportController as SellerReportController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Public\SellerRegistrationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::post('/sellers/register', [SellerRegistrationController::class, 'apiStore']);
Route::get('/public/home', [HomeController::class, 'index']);

Route::middleware(['auth:sanctum', 'role:customer,sanctum'])->group(function () {
    Route::post('/reviews', [CustomerReviewController::class, 'store']);
});

Route::middleware(['auth:sanctum', 'role:super_admin,sanctum'])->prefix('admin')->group(function () {
    Route::get('/orders', [OrderController::class, 'index']);

    Route::get('/sellers', [SellerController::class, 'index']);
    Route::post('/sellers', [SellerController::class, 'store']);
    Route::patch('/sellers/{seller}/approve', [SellerController::class, 'approve']);
    Route::patch('/sellers/{seller}/block', [SellerController::class, 'block']);

    Route::get('/subscription-plans', [SubscriptionPlanController::class, 'index']);
    Route::post('/subscription-plans', [SubscriptionPlanController::class, 'store']);
    Route::patch('/subscription-plans/{subscriptionPlan}', [SubscriptionPlanController::class, 'update']);
    Route::delete('/subscription-plans/{subscriptionPlan}', [SubscriptionPlanController::class, 'destroy']);

    Route::get('/banners', [BannerController::class, 'index']);
    Route::post('/banners', [BannerController::class, 'store']);
    Route::patch('/banners/{banner}', [BannerController::class, 'update']);
    Route::delete('/banners/{banner}', [BannerController::class, 'destroy']);

    Route::get('/reviews', [ReviewController::class, 'index']);
    Route::patch('/reviews/{review}/visibility', [ReviewController::class, 'updateVisibility']);

    Route::get('/reports/food-courts', [FoodCourtReportController::class, 'index']);
});

Route::middleware(['auth:sanctum', 'role:seller_owner|vendor,sanctum'])->prefix('seller')->group(function () {
    Route::get('/products', [SellerProductController::class, 'index']);
    Route::post('/products', [SellerProductController::class, 'store']);
    Route::patch('/products/{product}', [SellerProductController::class, 'update']);
    Route::delete('/products/{product}', [SellerProductController::class, 'destroy']);

    Route::get('/reports/summary', [SellerReportController::class, 'summary']);
});
