<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Earning;
use App\Models\Order;
use App\Models\Payout;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::whereIn('payment_status', ['paid', 'refunded']);

        if ($request->has('status')) {
            $query->where('payment_status', $request->status);
        }
        if ($request->has('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->has('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $payments = $query->with('user')
            ->latest()
            ->paginate($request->input('per_page', 15));

        $payments->getCollection()->transform(function ($payment) {
            return [
                'id' => $payment->id,
                'order_number' => $payment->order_number,
                'customer_name' => $payment->user?->name,
                'amount' => (float) $payment->total_amount,
                'payment_method' => $payment->payment_method,
                'payment_status' => $payment->payment_status,
                'created_at' => $payment->created_at->toISOString(),
            ];
        });

        return $this->paginateResponse($payments, 'Payments retrieved');
    }

    public function show($id)
    {
        $order = Order::with('user')->findOrFail($id);

        return $this->successResponse([
            'id' => $order->id,
            'order_number' => $order->order_number,
            'customer_name' => $order->user?->name,
            'subtotal' => (float) $order->subtotal,
            'tax_amount' => (float) $order->tax_amount,
            'shipping_cost' => (float) $order->shipping_cost,
            'discount_amount' => (float) $order->discount_amount,
            'total_amount' => (float) $order->total_amount,
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'paid_at' => $order->paid_at?->toISOString(),
            'created_at' => $order->created_at->toISOString(),
        ], 'Payment details retrieved');
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:paid,failed,refunded',
        ]);

        $order = Order::findOrFail($id);
        $order->update([
            'payment_status' => $validated['status'],
            'paid_at' => $validated['status'] === 'paid' ? now() : $order->paid_at,
        ]);

        return $this->successResponse($order, 'Payment status updated');
    }
}
