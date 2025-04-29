<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CakeController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;

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

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('/contact', [ContactController::class, 'submit'])->name('contact.submit');

// Cake routes
Route::get('/cakes', [CakeController::class, 'index'])->name('cakes.index');
Route::get('/cakes/{cake}', [CakeController::class, 'show'])->name('cakes.show');

// Cart routes
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add/{cake}', [CartController::class, 'addToCart'])->name('cart.add');
Route::patch('/cart/update/{index}', [CartController::class, 'updateCartItem'])->name('cart.update');
Route::delete('/cart/remove/{index}', [CartController::class, 'removeCartItem'])->name('cart.remove');
Route::delete('/cart/clear', [CartController::class, 'clearCart'])->name('cart.clear');

// Checkout routes
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout/process', [CheckoutController::class, 'process'])->name('checkout.process');
Route::get('/checkout/confirmation/{orderNumber}', [CheckoutController::class, 'confirmation'])->name('checkout.confirmation');

// Placeholder routes for other pages
Route::get('/menu', function () {
    return redirect()->route('cakes.index');
})->name('menu');

Route::get('/order', function () {
    return redirect()->route('cakes.index');
})->name('order');

Route::get('/team', function () {
    return view('coming-soon', ['page' => 'Team']);
})->name('team');
