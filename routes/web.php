<?php

use Illuminate\Support\Facades\Route;
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

Route::get('/admin/{any?}', function () {
    return view('admin');
})->where('any', '.*');
