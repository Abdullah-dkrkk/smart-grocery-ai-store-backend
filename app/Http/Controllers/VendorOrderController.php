<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class VendorOrderController extends Controller
{
    public function index(Request $request)
    {
        $vendorId = $request->user()->id;

        $orderIds = OrderItem::whereHas('product', function ($q) use ($vendorId) {
            $q->where('vendor_id', $vendorId);
        })->pluck('order_id');

        $query = Order::with(['user', 'items.product'])
            ->whereIn('id', $orderIds);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->latest()->paginate($request->input('per_page', 15));

        $orders->getCollection()->transform(function ($order) use ($vendorId) {
            $vendorItems = $order->items->filter(function ($item) use ($vendorId) {
                return $item->product && $item->product->vendor_id === $vendorId;
            })->values();

            $order->setRelation('items', $vendorItems);

            return $order;
        });

        return $this->paginateResponse($orders, 'Orders retrieved');
    }

    public function show(Request $request, $id)
    {
        $vendorId = $request->user()->id;

        $order = Order::with(['user', 'items.product'])
            ->whereHas('items.product', function ($q) use ($vendorId) {
                $q->where('vendor_id', $vendorId);
            })
            ->findOrFail($id);

        $order->items = $order->items->filter(function ($item) use ($vendorId) {
            return $item->product && $item->product->vendor_id === $vendorId;
        })->values();

        return $this->successResponse($order, 'Order details retrieved');
    }

    public function updateItemStatus(Request $request, $orderId, $itemId)
    {
        $vendorId = $request->user()->id;

        $validated = $request->validate([
            'status' => 'required|in:processing,shipped',
        ]);

        $item = OrderItem::with('product')
            ->where('id', $itemId)
            ->where('order_id', $orderId)
            ->whereHas('product', function ($q) use ($vendorId) {
                $q->where('vendor_id', $vendorId);
            })
            ->firstOrFail();

        $item->update(['status' => $validated['status']]);

        $order = Order::find($orderId);
        $allItems = OrderItem::where('order_id', $orderId)->get();
        $allShipped = $allItems->every(fn($i) => $i->status === 'shipped');
        $anyProcessing = $allItems->contains(fn($i) => $i->status === 'processing');

        if ($allShipped) {
            $order->update(['status' => 'shipped', 'shipped_at' => now()]);
        } elseif ($anyProcessing && $order->status === 'pending') {
            $order->update(['status' => 'processing']);
        }

        return $this->successResponse($item, 'Item status updated');
    }
}
