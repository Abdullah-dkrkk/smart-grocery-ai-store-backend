<?php

use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminOrderController;
use App\Http\Controllers\AdminProductController;
use App\Http\Controllers\AiAssistantController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerCartController;
use App\Http\Controllers\CustomerOrderController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\HealthProfileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\VendorProductController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:15,1');

    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me'])->middleware('throttle:30,1');
        Route::put('/change-password', [AuthController::class, 'changePassword'])->middleware('throttle:10,1');
    });
});

Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->middleware('throttle:60,1');
    Route::get('/featured', [ProductController::class, 'featured'])->middleware('throttle:60,1');
    Route::get('/categories', [ProductController::class, 'categories'])->middleware('throttle:60,1');
    Route::get('/search', [ProductController::class, 'search'])->middleware('throttle:30,1');
    Route::get('/bulk', [ProductController::class, 'bulk'])->middleware('throttle:60,1');
    Route::get('/nutrition', [ProductController::class, 'nutrition'])->middleware('throttle:60,1');
    Route::get('/{id}', [ProductController::class, 'show'])->middleware('throttle:60,1');
    Route::get('/{id}/reviews', [ReviewController::class, 'index'])->middleware('throttle:60,1');
    Route::post('/{id}/reviews', [ReviewController::class, 'store'])->middleware(['auth:sanctum', 'customer', 'throttle:10,1']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('user')->group(function () {
        Route::get('/health-profile', [HealthProfileController::class, 'show']);
        Route::put('/health-profile', [HealthProfileController::class, 'update']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
    });

    Route::prefix('customer')->group(function () {
        Route::middleware('customer')->group(function () {
            Route::prefix('ai')->group(function () {
                Route::post('/ask', [AiAssistantController::class, 'ask'])->middleware('throttle:20,1');
                Route::get('/suggestions', [AiAssistantController::class, 'suggestions'])->middleware('throttle:20,1');
                Route::post('/identify', [AiAssistantController::class, 'identify'])->middleware('throttle:10,1');
                Route::get('/diet-plan', [AiAssistantController::class, 'dietPlan'])->middleware('throttle:10,1');
                Route::get('/chat/history', [AiAssistantController::class, 'history'])->middleware('throttle:30,1');
                Route::get('/nutrition/{productId}', [AiAssistantController::class, 'nutritionBreakdown'])->middleware('throttle:30,1');
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
                Route::put('/{order}/cancel', [CustomerOrderController::class, 'cancel'])->middleware('throttle:10,1');
                Route::get('/{order}/track', [CustomerOrderController::class, 'track'])->middleware('throttle:30,1');
            });

            Route::prefix('wishlist')->group(function () {
                Route::get('/', [WishlistController::class, 'index']);
                Route::post('/{product}', [WishlistController::class, 'add']);
                Route::delete('/{product}', [WishlistController::class, 'remove']);
            });

            Route::post('/orders/apply-discount', [DiscountController::class, 'apply'])->middleware('throttle:10,1');
        });
    });

    Route::prefix('reviews')->group(function () {
        Route::put('/{id}', [ReviewController::class, 'update'])->middleware('throttle:10,1');
        Route::delete('/{id}', [ReviewController::class, 'destroy'])->middleware('throttle:10,1');
    });

    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::put('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::put('/read-all', [NotificationController::class, 'markAllAsRead']);
    });

    Route::prefix('admin')->group(function () {
        Route::middleware('admin')->group(function () {
            Route::prefix('products')->group(function () {
                Route::get('/', [AdminProductController::class, 'index']);
                Route::post('/', [AdminProductController::class, 'store']);
                Route::put('/bulk-update', [AdminProductController::class, 'bulkUpdate'])->middleware('throttle:30,1');
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

            Route::apiResource('categories', AdminCategoryController::class);
            Route::apiResource('users', AdminUserController::class);

            Route::prefix('discounts')->group(function () {
                Route::get('/', [DiscountController::class, 'index']);
                Route::post('/', [DiscountController::class, 'store']);
                Route::get('/{id}', [DiscountController::class, 'show']);
                Route::put('/{id}', [DiscountController::class, 'update']);
                Route::delete('/{id}', [DiscountController::class, 'destroy']);
            });

            Route::get('/settings', [AdminSettingController::class, 'index']);
            Route::put('/settings', [AdminSettingController::class, 'update']);
        });
    });

    Route::prefix('vendor')->group(function () {
        Route::middleware('vendor')->group(function () {
            Route::prefix('products')->group(function () {
                Route::get('/', [VendorProductController::class, 'index']);
                Route::post('/', [VendorProductController::class, 'store']);
                Route::get('/stats', [VendorProductController::class, 'stats']);
                Route::get('/{id}', [VendorProductController::class, 'show']);
                Route::put('/{id}', [VendorProductController::class, 'update']);
                Route::delete('/{id}', [VendorProductController::class, 'destroy']);
                Route::post('/upload-image', [VendorProductController::class, 'uploadImage']);
                Route::get('/{id}/analytics', [VendorProductController::class, 'analytics'])->middleware('throttle:30,1');
            });
        });
    });
});
