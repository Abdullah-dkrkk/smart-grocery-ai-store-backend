<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->input('from', now()->subMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());
        $groupBy = $request->input('group_by', 'day');

        $dateFormat = match ($groupBy) {
            'week' => DB::raw('YEARWEEK(created_at) as date'),
            'month' => DB::raw("DATE_FORMAT(created_at, '%Y-%m') as date"),
            default => DB::raw('DATE(created_at) as date'),
        };

        $revenueOverTime = Order::where('payment_status', 'paid')
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->select($dateFormat, DB::raw('SUM(total_amount) as revenue'), DB::raw('COUNT(*) as orders'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $userGrowth = User::whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->select($dateFormat, DB::raw('COUNT(*) as new_users'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $topSellingProducts = Order::select('products.id', 'products.name', DB::raw('SUM(order_items.quantity) as total_sold'), DB::raw('SUM(order_items.subtotal) as revenue'))
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereDate('orders.created_at', '>=', $from)
            ->whereDate('orders.created_at', '<=', $to)
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_sold')
            ->limit(10)
            ->get();

        $ordersByStatus = Order::select('status', DB::raw('COUNT(*) as count'))
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        return $this->successResponse([
            'revenue_over_time' => $revenueOverTime,
            'user_growth' => $userGrowth,
            'top_selling_products' => $topSellingProducts,
            'orders_by_status' => $ordersByStatus,
        ], 'Analytics retrieved');
    }
}
