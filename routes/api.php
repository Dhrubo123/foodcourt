<?php

use App\Http\Controllers\Api\Admin\SellerController;
use App\Http\Controllers\Api\Admin\OrderController;
use App\Http\Controllers\Api\Admin\SubscriptionPlanController;
use App\Http\Controllers\Api\Public\HomeController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Public\SellerRegistrationController;
use App\Http\Controllers\Api\Admin\FoodCourtReportController;
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

    Route::get('/reports/food-courts', [FoodCourtReportController::class, 'index']);
});
