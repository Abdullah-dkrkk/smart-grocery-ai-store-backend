<?php

use App\Http\Controllers\Admin\AdminAnalyticsController;
use App\Http\Controllers\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\NutritionistController as AdminNutritionistController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\VendorController as AdminVendorController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminOrderController;
use App\Http\Controllers\AdminProductController;
use App\Http\Controllers\AiAssistantController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerAddressController;
use App\Http\Controllers\CustomerCartController;
use App\Http\Controllers\CustomerDashboardController;
use App\Http\Controllers\CustomerNutritionPlanController;
use App\Http\Controllers\CustomerOrderController;
use App\Http\Controllers\CustomerPaymentMethodController;
use App\Http\Controllers\CustomerReviewController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\HealthProfileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NutritionistAppointmentController;
use App\Http\Controllers\NutritionistArticleController;
use App\Http\Controllers\NutritionistClientController;
use App\Http\Controllers\NutritionistConsultationController;
use App\Http\Controllers\NutritionistDashboardController;
use App\Http\Controllers\NutritionistDietChartController;
use App\Http\Controllers\NutritionistMealPlanController;
use App\Http\Controllers\NutritionistProfileController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\VendorDashboardController;
use App\Http\Controllers\VendorEarningController;
use App\Http\Controllers\VendorInventoryController;
use App\Http\Controllers\VendorOrderController;
use App\Http\Controllers\VendorProductController;
use App\Http\Controllers\VendorReviewController;
use App\Http\Controllers\VendorStoreController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:15,1');

    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me'])->middleware('throttle:30,1');
        Route::put('/me', [AuthController::class, 'updateProfile']);
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

            Route::get('/dashboard/overview', [CustomerDashboardController::class, 'overview']);

            Route::apiResource('addresses', CustomerAddressController::class);
            Route::get('payment-methods', [CustomerPaymentMethodController::class, 'index']);
            Route::post('payment-methods', [CustomerPaymentMethodController::class, 'store']);
            Route::delete('payment-methods/{id}', [CustomerPaymentMethodController::class, 'destroy']);

            Route::prefix('nutrition-plans')->group(function () {
                Route::get('/', [CustomerNutritionPlanController::class, 'index']);
                Route::get('/{id}', [CustomerNutritionPlanController::class, 'show']);
            });

            Route::get('/reviews', [CustomerReviewController::class, 'index']);

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
                Route::post('/{id}/refund', [AdminOrderController::class, 'refund']);
            });

            Route::prefix('dashboard')->group(function () {
                Route::get('/overview', [AdminDashboardController::class, 'overview']);
                Route::get('/trends', [AdminDashboardController::class, 'trends']);
            });

            Route::apiResource('categories', AdminCategoryController::class);

            Route::prefix('users')->group(function () {
                Route::get('/', [AdminUserController::class, 'index']);
                Route::get('/{id}', [AdminUserController::class, 'show']);
                Route::put('/{id}', [AdminUserController::class, 'update']);
                Route::delete('/{id}', [AdminUserController::class, 'destroy']);
                Route::put('/{id}/approve', [AdminUserController::class, 'approve']);
                Route::put('/{id}/suspend', [AdminUserController::class, 'suspend']);
            });

            Route::prefix('discounts')->group(function () {
                Route::get('/', [DiscountController::class, 'index']);
                Route::post('/', [DiscountController::class, 'store']);
                Route::get('/{id}', [DiscountController::class, 'show']);
                Route::put('/{id}', [DiscountController::class, 'update']);
                Route::delete('/{id}', [DiscountController::class, 'destroy']);
            });

            Route::get('/settings', [AdminSettingController::class, 'index']);
            Route::put('/settings', [AdminSettingController::class, 'update']);

            Route::get('/analytics', [AdminAnalyticsController::class, 'index']);

            Route::prefix('payments')->group(function () {
                Route::get('/', [AdminPaymentController::class, 'index']);
                Route::get('/{id}', [AdminPaymentController::class, 'show']);
                Route::put('/{id}/status', [AdminPaymentController::class, 'updateStatus']);
            });

            Route::prefix('vendors')->group(function () {
                Route::get('/', [AdminVendorController::class, 'index']);
                Route::get('/{id}', [AdminVendorController::class, 'show']);
                Route::put('/{id}/approve', [AdminVendorController::class, 'approve']);
                Route::put('/{id}/suspend', [AdminVendorController::class, 'suspend']);
                Route::get('/{id}/products', [AdminVendorController::class, 'products']);
            });

            Route::prefix('nutritionists')->group(function () {
                Route::get('/', [AdminNutritionistController::class, 'index']);
                Route::get('/{id}', [AdminNutritionistController::class, 'show']);
                Route::put('/{id}/approve', [AdminNutritionistController::class, 'approve']);
                Route::put('/{id}/suspend', [AdminNutritionistController::class, 'suspend']);
            });

            Route::prefix('audit-logs')->group(function () {
                Route::get('/', [AdminAuditLogController::class, 'index']);
            });
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

            Route::get('/dashboard/overview', [VendorDashboardController::class, 'overview']);

            Route::prefix('orders')->group(function () {
                Route::get('/', [VendorOrderController::class, 'index']);
                Route::get('/{id}', [VendorOrderController::class, 'show']);
                Route::put('/{orderId}/items/{itemId}/status', [VendorOrderController::class, 'updateItemStatus']);
            });

            Route::prefix('inventory')->group(function () {
                Route::get('/', [VendorInventoryController::class, 'index']);
                Route::put('/{productId}', [VendorInventoryController::class, 'update']);
            });

            Route::get('/earnings', [VendorEarningController::class, 'index']);

            Route::prefix('store')->group(function () {
                Route::get('/', [VendorStoreController::class, 'show']);
                Route::put('/', [VendorStoreController::class, 'update']);
            });

            Route::prefix('reviews')->group(function () {
                Route::get('/', [VendorReviewController::class, 'index']);
                Route::post('/{id}/reply', [VendorReviewController::class, 'reply']);
            });
        });
    });

    Route::prefix('nutritionist')->group(function () {
        Route::middleware('nutritionist')->group(function () {
            Route::get('/dashboard/overview', [NutritionistDashboardController::class, 'overview']);

            Route::prefix('clients')->group(function () {
                Route::get('/', [NutritionistClientController::class, 'index']);
                Route::post('/', [NutritionistClientController::class, 'store']);
                Route::get('/{id}', [NutritionistClientController::class, 'show']);
            });

            Route::apiResource('meal-plans', NutritionistMealPlanController::class);
            Route::apiResource('diet-charts', NutritionistDietChartController::class);

            Route::prefix('appointments')->group(function () {
                Route::get('/', [NutritionistAppointmentController::class, 'index']);
                Route::post('/', [NutritionistAppointmentController::class, 'store']);
                Route::get('/{id}', [NutritionistAppointmentController::class, 'show']);
                Route::put('/{id}/status', [NutritionistAppointmentController::class, 'updateStatus']);
            });

            Route::prefix('consultations')->group(function () {
                Route::get('/', [NutritionistConsultationController::class, 'index']);
                Route::post('/', [NutritionistConsultationController::class, 'store']);
                Route::get('/{id}', [NutritionistConsultationController::class, 'show']);
                Route::put('/{id}', [NutritionistConsultationController::class, 'update']);
            });

            Route::apiResource('articles', NutritionistArticleController::class);

            Route::prefix('profile')->group(function () {
                Route::get('/', [NutritionistProfileController::class, 'show']);
                Route::put('/', [NutritionistProfileController::class, 'update']);
            });
        });
    });
});
