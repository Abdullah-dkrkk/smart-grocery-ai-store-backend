<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendorDashboardController extends Controller
{
    public function overview(Request $request)
    {
        $vendorId = $request->user()->id;

        $totalProducts = Product::where('vendor_id', $vendorId)->count();
        $activeProducts = Product::where('vendor_id', $vendorId)->where('is_active', true)->count();

        $orderStats = OrderItem::whereHas('product', function ($q) use ($vendorId) {
            $q->where('vendor_id', $vendorId);
        })->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->select(
                DB::raw('COUNT(DISTINCT orders.id) as total_orders'),
                DB::raw('SUM(CASE WHEN DATE(orders.created_at) = CURDATE() THEN 1 ELSE 0 END) as orders_today'),
                DB::raw('SUM(order_items.subtotal) as total_revenue')
            )->first();

        $revenueThisMonth = OrderItem::whereHas('product', function ($q) use ($vendorId) {
            $q->where('vendor_id', $vendorId);
        })->whereMonth('created_at', now()->month)
            ->sum('subtotal');

        $averageRating = Review::whereHas('product', function ($q) use ($vendorId) {
            $q->where('vendor_id', $vendorId);
        })->avg('rating');

        $lowStockProducts = Product::where('vendor_id', $vendorId)
            ->where('is_active', true)
            ->whereColumn('stock_quantity', '<=', 'min_stock_threshold')
            ->limit(10)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'stock_quantity' => $product->stock_quantity,
                    'min_stock_threshold' => $product->min_stock_threshold,
                ];
            });

        $recentOrderIds = OrderItem::whereHas('product', function ($q) use ($vendorId) {
            $q->where('vendor_id', $vendorId);
        })->pluck('order_id');

        $recentOrders = Order::with('user')
            ->whereIn('id', $recentOrderIds)
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($order) use ($vendorId) {
                $vendorItems = OrderItem::where('order_id', $order->id)
                    ->whereHas('product', function ($q) use ($vendorId) {
                        $q->where('vendor_id', $vendorId);
                    })->get();

                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->user?->name,
                    'total_amount' => (float) $vendorItems->sum('subtotal'),
                    'status' => $order->status,
                    'items_count' => $vendorItems->sum('quantity'),
                    'created_at' => $order->created_at->diffForHumans(),
                ];
            });

        $earningsTrend = OrderItem::whereHas('product', function ($q) use ($vendorId) {
            $q->where('vendor_id', $vendorId);
        })->where('created_at', '>=', now()->subDays(7))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(subtotal) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $trendData = collect(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'])->map(function ($day, $index) use ($earningsTrend) {
            $date = now()->subDays(6 - $index)->format('Y-m-d');
            $found = $earningsTrend->firstWhere('date', $date);

            return $found ? (float) $found->total : 0;
        });

        return $this->successResponse([
            'total_products' => $totalProducts,
            'active_products' => $activeProducts,
            'orders_received_today' => (int) ($orderStats->orders_today ?? 0),
            'orders_received_total' => (int) ($orderStats->total_orders ?? 0),
            'total_revenue' => (float) ($orderStats->total_revenue ?? 0),
            'revenue_this_month' => (float) $revenueThisMonth,
            'average_rating' => $averageRating ? round($averageRating, 1) : 0,
            'low_stock_products' => $lowStockProducts,
            'recent_orders' => $recentOrders,
            'earnings_trend' => [
                'last_7_days' => $trendData,
                'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            ],
        ], 'Dashboard overview retrieved');
    }
}
