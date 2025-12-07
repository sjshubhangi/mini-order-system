<?php
/**
 * Senior note:
 * - Group by auth and role.
 * - Apply login limiter on /login to mitigate brute force.
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;

Route::post('/register', [AuthController::class, 'register']);
Route::middleware('throttle:login')->post('/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);

    // Products
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/popular', [ProductController::class, 'popular']);
    Route::get('/products/{product}', [ProductController::class, 'show']);
    Route::get('/products/{product}/image-url', [ProductController::class, 'imageSignedUrl']);

    Route::middleware('role:vendor,admin')->group(function () {
        Route::post('/products', [ProductController::class, 'store']);
        Route::patch('/products/{product}', [ProductController::class, 'update']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);
    });

    // Orders
    Route::middleware('role:customer')->post('/orders', [OrderController::class, 'store']);
    Route::middleware('role:vendor,admin')->get('/orders', [OrderController::class, 'index']);
    Route::middleware('role:vendor,admin')->get('/orders/{order}', [OrderController::class, 'show']);
    Route::middleware('role:vendor,admin')->patch('/orders/{order}/status', [OrderController::class, 'updateStatus']);
});
