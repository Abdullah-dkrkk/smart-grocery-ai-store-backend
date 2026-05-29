<?php

namespace App\Http\Controllers;

use App\Models\Earning;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendorEarningController extends Controller
{
    public function index(Request $request)
    {
        $vendorId = $request->user()->id;

        $period = $request->input('period', 30);
        $from = $request->input('from');
        $to = $request->input('to');

        $totalRevenue = OrderItem::whereHas('product', function ($q) use ($vendorId) {
            $q->where('vendor_id', $vendorId);
        })->whereHas('order', function ($q) {
            $q->where('payment_status', 'paid');
        })->sum('subtotal');

        $earningsQuery = Earning::where('vendor_id', $vendorId);

        $totalPaid = (clone $earningsQuery)->where('status', 'paid')->sum('net_amount');
        $totalPending = (clone $earningsQuery)->where('status', 'pending')->sum('net_amount');

        $transactionsQuery = Earning::with('order')
            ->where('vendor_id', $vendorId);

        if ($from) {
            $transactionsQuery->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $transactionsQuery->whereDate('created_at', '<=', $to);
        }
        if (!$from && !$to && $period) {
            $transactionsQuery->where('created_at', '>=', now()->subDays((int) $period));
        }

        $transactions = $transactionsQuery->latest()
            ->limit(50)
            ->get()
            ->map(function ($earning) {
                return [
                    'id' => $earning->id,
                    'amount' => (float) $earning->net_amount,
                    'type' => $earning->status === 'paid' ? 'payout' : 'earned',
                    'order_number' => $earning->order?->order_number,
                    'date' => $earning->created_at->toDateString(),
                ];
            });

        return $this->successResponse([
            'total_revenue' => (float) $totalRevenue,
            'total_paid' => (float) $totalPaid,
            'total_pending' => (float) $totalPending,
            'current_balance' => (float) $totalPending,
            'transactions' => $transactions,
        ], 'Earnings retrieved');
    }
}
