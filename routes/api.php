<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminOrderController;
use App\Http\Controllers\AdminProductController;
use App\Http\Controllers\AiAssistantController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerCartController;
use App\Http\Controllers\CustomerOrderController;
use App\Http\Controllers\HealthProfileController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\VendorProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/categories', [ProductController::class, 'categories']);
    Route::get('/search', [ProductController::class, 'search']);
    Route::get('/{id}', [ProductController::class, 'show']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('user')->group(function () {
        Route::get('/health-profile', [HealthProfileController::class, 'show']);
        Route::put('/health-profile', [HealthProfileController::class, 'update']);
    });

    Route::prefix('customer')->group(function () {
        Route::middleware('customer')->group(function () {
            Route::prefix('ai')->group(function () {
                Route::post('/ask', [AiAssistantController::class, 'ask']);
                Route::get('/suggestions', [AiAssistantController::class, 'suggestions']);
                Route::post('/identify', [AiAssistantController::class, 'identify']);
                Route::get('/diet-plan', [AiAssistantController::class, 'dietPlan']);
                Route::get('/chat/history', [AiAssistantController::class, 'history']);
            });

            Route::prefix('cart')->group(function () {
                Route::get('/', [CustomerCartController::class, 'index']);
                Route::post('/add', [CustomerCartController::class, 'add']);
                Route::delete('/{id}', [CustomerCartController::class, 'remove']);
            });

            Route::prefix('orders')->group(function () {
                Route::post('/checkout', [CustomerOrderController::class, 'checkout']);
                Route::get('/', [CustomerOrderController::class, 'index']);
                Route::get('/{id}', [CustomerOrderController::class, 'show']);
            });
        });
    });

    Route::prefix('admin')->group(function () {
        Route::middleware('admin')->group(function () {
            Route::prefix('products')->group(function () {
                Route::get('/', [AdminProductController::class, 'index']);
                Route::post('/', [AdminProductController::class, 'store']);
                Route::get('/{id}', [AdminProductController::class, 'show']);
                Route::put('/{id}', [AdminProductController::class, 'update']);
                Route::delete('/{id}', [AdminProductController::class, 'destroy']);
                Route::post('/upload-image', [AdminProductController::class, 'uploadImage']);
            });

            Route::prefix('orders')->group(function () {
                Route::get('/', [AdminOrderController::class, 'index']);
                Route::get('/{id}', [AdminOrderController::class, 'show']);
                Route::put('/{id}/status', [AdminOrderController::class, 'updateStatus']);
            });

            Route::prefix('dashboard')->group(function () {
                Route::get('/overview', [AdminDashboardController::class, 'overview']);
                Route::get('/trends', [AdminDashboardController::class, 'trends']);
            });
        });
    });

    Route::prefix('vendor')->group(function () {
        Route::middleware('vendor')->group(function () {
            Route::prefix('products')->group(function () {
                Route::get('/', [VendorProductController::class, 'index']);
                Route::post('/', [VendorProductController::class, 'store']);
                Route::get('/{id}', [VendorProductController::class, 'show']);
                Route::put('/{id}', [VendorProductController::class, 'update']);
                Route::delete('/{id}', [VendorProductController::class, 'destroy']);
                Route::post('/upload-image', [VendorProductController::class, 'uploadImage']);
                Route::get('/stats', [VendorProductController::class, 'stats']);
            });
        });
    });
});
