<?php

use App\Http\Controllers\Api\Admin\BannerController;
use App\Http\Controllers\Api\Admin\CustomerController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\FoodCourtReportController;
use App\Http\Controllers\Api\Admin\OfferController;
use App\Http\Controllers\Api\Admin\SellerController;
use App\Http\Controllers\Api\Admin\SellerSettlementController;
use App\Http\Controllers\Api\Admin\SettingController;
use App\Http\Controllers\Api\Admin\OrderController;
use App\Http\Controllers\Api\Admin\ReportController;
use App\Http\Controllers\Api\Admin\ReviewController;
use App\Http\Controllers\Api\Admin\SubscriptionController;
use App\Http\Controllers\Api\Admin\SubscriptionPlanController;
use App\Http\Controllers\Api\CustomerReviewController;
use App\Http\Controllers\Api\Public\HomeController;
use App\Http\Controllers\Api\Seller\ProductController as SellerProductController;
use App\Http\Controllers\Api\Seller\CategoryController as SellerCategoryController;
use App\Http\Controllers\Api\Seller\DashboardController as SellerDashboardController;
use App\Http\Controllers\Api\Seller\OrderController as SellerOrderController;
use App\Http\Controllers\Api\Seller\ProfileController as SellerProfileController;
use App\Http\Controllers\Api\Seller\ReportController as SellerReportController;
use App\Http\Controllers\Api\Seller\ReviewController as SellerReviewController;
use App\Http\Controllers\Api\Seller\SubscriptionController as SellerSubscriptionController;
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
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus']);
    Route::patch('/orders/{order}/payment-status', [OrderController::class, 'updatePaymentStatus']);
    Route::patch('/seller-orders/{sellerOrder}/status', [OrderController::class, 'updateSellerOrderStatus']);

    Route::get('/sellers', [SellerController::class, 'index']);
    Route::post('/sellers', [SellerController::class, 'store']);
    Route::patch('/sellers/{seller}', [SellerController::class, 'update']);
    Route::patch('/sellers/{seller}/approve', [SellerController::class, 'approve']);
    Route::patch('/sellers/{seller}/block', [SellerController::class, 'block']);

    Route::get('/subscription-plans', [SubscriptionPlanController::class, 'index']);
    Route::post('/subscription-plans', [SubscriptionPlanController::class, 'store']);
    Route::patch('/subscription-plans/{subscriptionPlan}', [SubscriptionPlanController::class, 'update']);
    Route::delete('/subscription-plans/{subscriptionPlan}', [SubscriptionPlanController::class, 'destroy']);

    Route::get('/subscriptions', [SubscriptionController::class, 'index']);
    Route::post('/subscriptions', [SubscriptionController::class, 'store']);
    Route::patch('/subscriptions/{subscription}', [SubscriptionController::class, 'update']);
    Route::delete('/subscriptions/{subscription}', [SubscriptionController::class, 'destroy']);

    Route::get('/offers', [OfferController::class, 'index']);
    Route::post('/offers', [OfferController::class, 'store']);
    Route::patch('/offers/{offer}', [OfferController::class, 'update']);
    Route::delete('/offers/{offer}', [OfferController::class, 'destroy']);

    Route::get('/banners', [BannerController::class, 'index']);
    Route::post('/banners', [BannerController::class, 'store']);
    Route::patch('/banners/{banner}', [BannerController::class, 'update']);
    Route::delete('/banners/{banner}', [BannerController::class, 'destroy']);

    Route::get('/reviews', [ReviewController::class, 'index']);
    Route::patch('/reviews/{review}/visibility', [ReviewController::class, 'updateVisibility']);

    Route::get('/customers', [CustomerController::class, 'index']);
    Route::patch('/customers/{customer}/status', [CustomerController::class, 'updateStatus']);

    Route::get('/settings', [SettingController::class, 'index']);
    Route::put('/settings', [SettingController::class, 'update']);

    Route::get('/settlements', [SellerSettlementController::class, 'index']);
    Route::get('/settlements/summary', [SellerSettlementController::class, 'summary']);
    Route::post('/settlements/generate', [SellerSettlementController::class, 'generate']);
    Route::patch('/settlements/{sellerSettlement}/mark-paid', [SellerSettlementController::class, 'markPaid']);

    Route::get('/reports/food-courts', [FoodCourtReportController::class, 'index']);
    Route::get('/reports/food-court-queue', [FoodCourtReportController::class, 'queue']);
    Route::get('/reports/meta', [ReportController::class, 'meta']);
    Route::get('/reports/orders', [ReportController::class, 'orders']);
    Route::get('/reports/sales-summary', [ReportController::class, 'salesSummary']);
    Route::get('/reports/seller-settlements', [ReportController::class, 'sellerSettlements']);
    Route::get('/reports/top-items', [ReportController::class, 'topItems']);
    Route::get('/reports/cancelled-orders', [ReportController::class, 'cancelledOrders']);
});

Route::middleware(['auth:sanctum', 'role:seller_owner|vendor,sanctum'])->prefix('seller')->group(function () {
    Route::get('/dashboard', [SellerDashboardController::class, 'index']);

    Route::get('/orders', [SellerOrderController::class, 'index']);
    Route::patch('/orders/{sellerOrder}/status', [SellerOrderController::class, 'updateStatus']);

    Route::get('/categories', [SellerCategoryController::class, 'index']);
    Route::post('/categories', [SellerCategoryController::class, 'store']);
    Route::patch('/categories/{category}', [SellerCategoryController::class, 'update']);
    Route::delete('/categories/{category}', [SellerCategoryController::class, 'destroy']);

    Route::get('/products', [SellerProductController::class, 'index']);
    Route::post('/products', [SellerProductController::class, 'store']);
    Route::patch('/products/{product}', [SellerProductController::class, 'update']);
    Route::delete('/products/{product}', [SellerProductController::class, 'destroy']);

    Route::get('/reviews', [SellerReviewController::class, 'index']);
    Route::get('/subscription', [SellerSubscriptionController::class, 'show']);
    Route::post('/subscription/renew', [SellerSubscriptionController::class, 'renew']);

    Route::get('/profile', [SellerProfileController::class, 'show']);
    Route::post('/profile', [SellerProfileController::class, 'update']);

    Route::get('/reports/summary', [SellerReportController::class, 'summary']);
});
