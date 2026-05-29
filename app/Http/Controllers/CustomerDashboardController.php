<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Review;
use App\Models\Product;
use Illuminate\Http\Request;

class CustomerDashboardController extends Controller
{
    public function overview(Request $request)
    {
        $user = $request->user();
        $userId = $user->id;

        $totalOrders = Order::where('user_id', $userId)->count();
        $pendingDeliveries = Order::where('user_id', $userId)->whereIn('status', ['pending', 'processing', 'shipped'])->count();
        $reviewsGiven = Review::where('user_id', $userId)->count();
        $totalSpent = Order::where('user_id', $userId)->where('payment_status', 'paid')->sum('total_amount');

        $recentOrders = Order::with('items.product')
            ->where('user_id', $userId)
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'total_amount' => (float) $order->total_amount,
                    'status' => $order->status,
                    'item_count' => $order->items->sum('quantity'),
                    'created_at' => $order->created_at->diffForHumans(),
                ];
            });

        $upcomingDeliveries = Order::where('user_id', $userId)
            ->whereIn('status', ['processing', 'shipped'])
            ->whereNotNull('estimated_delivery')
            ->latest('estimated_delivery')
            ->limit(5)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'scheduled_date' => $order->estimated_delivery?->toISOString(),
                    'items_summary' => $order->items->pluck('product_name')->implode(', '),
                    'status' => $order->status,
                ];
            });

        $recommendedProducts = Product::where('is_active', true)
            ->where('is_featured', true)
            ->inRandomOrder()
            ->limit(5)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'image_url' => $product->image_url,
                    'price' => (float) $product->price,
                    'discount_percent' => $product->discountPercentage(),
                ];
            });

        return $this->successResponse([
            'total_orders' => $totalOrders,
            'pending_deliveries' => $pendingDeliveries,
            'reviews_given' => $reviewsGiven,
            'total_spent' => (float) $totalSpent,
            'recent_orders' => $recentOrders,
            'upcoming_deliveries' => $upcomingDeliveries,
            'recommended_products' => $recommendedProducts,
        ], 'Dashboard overview retrieved');
    }
}
