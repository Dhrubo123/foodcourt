<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Public\CustomerAuthController;
use App\Http\Controllers\Public\SellerRegistrationController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('home');
});

Route::get('/register-seller', [SellerRegistrationController::class, 'show'])->name('seller.register');
Route::post('/register-seller', [SellerRegistrationController::class, 'store']);

Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return redirect()->route('customer.login');
    })->name('login');
    Route::get('/customer/register', [CustomerAuthController::class, 'showRegister'])->name('customer.register');
    Route::post('/customer/register', [CustomerAuthController::class, 'register']);
    Route::get('/customer/login', [CustomerAuthController::class, 'showLogin'])->name('customer.login');
    Route::post('/customer/login', [CustomerAuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::get('/customer/account', [CustomerAuthController::class, 'account'])->name('customer.account');
    Route::post('/customer/logout', [CustomerAuthController::class, 'logout'])->name('customer.logout');
});

Route::get('/admin/{any?}', function () {
    return view('admin');
})->where('any', '.*');

Route::get('/seller/{any?}', function () {
    return view('seller');
})->where('any', '.*');
