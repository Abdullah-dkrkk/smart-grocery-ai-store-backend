<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes\Get;
use OpenApi\Attributes\Tag;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Response;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Security;

/**
 * @OA\Tag(
 *     name="Admin Dashboard",
 *     description="Admin dashboard statistics and trend data"
 * )
 */
class AdminDashboardController extends Controller
{
    #[Get(
        path: "/api/admin/dashboard/overview",
        tags: ["Admin Dashboard"],
        summary: "Get dashboard overview statistics",
        security: [["sanctum" => []]],
        responses: [
            new Response(response: 200, description: "Dashboard overview", content: new JsonContent(properties: [
                new Property(property: "success", type: "boolean", example: true),
                new Property(property: "data", type: "object"),
                new Property(property: "message", type: "string", example: "Dashboard overview retrieved"),
            ])),
            new Response(response: 401, description: "Unauthorized"),
            new Response(response: 403, description: "Forbidden"),
        ]
    )]
    public function overview()
    {
        $totalRevenue = Order::where('payment_status', 'paid')->sum('total_amount');
        $totalOrders = Order::count();
        $totalProducts = Product::count();
        $totalCustomers = User::where('role', 'customer')->count();
        $totalVendors = User::where('role', 'vendor')->count();

        $ordersByStatus = Order::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        $recentOrders = Order::with(['user'])
            ->latest()
            ->limit(10)
            ->get();

        $lowStockProducts = Product::whereColumn('stock_quantity', '<=', 'min_stock_threshold')
            ->where('is_active', true)
            ->limit(10)
            ->get();

        $revenueByPeriod = [
            'today' => Order::where('payment_status', 'paid')->whereDate('created_at', today())->sum('total_amount'),
            'this_week' => Order::where('payment_status', 'paid')->whereBetween('created_at', [now()->startOfWeek(), now()])->sum('total_amount'),
            'this_month' => Order::where('payment_status', 'paid')->whereMonth('created_at', now()->month)->sum('total_amount'),
        ];

        return $this->successResponse([
            'total_revenue' => (float) $totalRevenue,
            'total_orders' => $totalOrders,
            'total_products' => $totalProducts,
            'total_customers' => $totalCustomers,
            'total_vendors' => $totalVendors,
            'orders_by_status' => $ordersByStatus,
            'revenue_by_period' => [
                'today' => (float) $revenueByPeriod['today'],
                'this_week' => (float) $revenueByPeriod['this_week'],
                'this_month' => (float) $revenueByPeriod['this_month'],
            ],
            'recent_orders' => $recentOrders,
            'low_stock_products' => $lowStockProducts,
        ], 'Dashboard overview retrieved');
    }

    #[Get(
        path: "/api/admin/dashboard/trends",
        tags: ["Admin Dashboard"],
        summary: "Get revenue and order trends",
        security: [["sanctum" => []]],
        parameters: [
            new \OpenApi\Attributes\Parameter(parameter: "period", name: "period", in: "query", required: false, schema: new \OpenApi\Attributes\Schema(type: "string", example: "30", enum: ["7", "30", "90"])),
        ],
        responses: [
            new Response(response: 200, description: "Trend data", content: new JsonContent(properties: [
                new Property(property: "success", type: "boolean", example: true),
                new Property(property: "data", type: "object"),
                new Property(property: "message", type: "string", example: "Trend data retrieved"),
            ])),
            new Response(response: 401, description: "Unauthorized"),
            new Response(response: 403, description: "Forbidden"),
        ]
    )]
    public function trends(Request $request)
    {
        $days = (int) $request->input('period', 30);
        $startDate = now()->subDays($days);

        $revenueTrend = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', $startDate)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('total', 'date');

        $ordersTrend = Order::where('created_at', '>=', $startDate)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('total', 'date');

        $topProducts = Order::select('products.name', DB::raw('SUM(order_items.quantity) as total_sold'), DB::raw('SUM(order_items.subtotal) as total_revenue'))
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.created_at', '>=', $startDate)
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_sold')
            ->limit(10)
            ->get();

        $topCustomers = User::select('users.name', 'users.email', DB::raw('COUNT(orders.id) as total_orders'), DB::raw('SUM(orders.total_amount) as total_spent'))
            ->join('orders', 'users.id', '=', 'orders.user_id')
            ->where('orders.created_at', '>=', $startDate)
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('total_spent')
            ->limit(10)
            ->get();

        return $this->successResponse([
            'period_days' => $days,
            'revenue_trend' => $revenueTrend,
            'orders_trend' => $ordersTrend,
            'top_products' => $topProducts,
            'top_customers' => $topCustomers,
        ], 'Trend data retrieved');
    }
}
