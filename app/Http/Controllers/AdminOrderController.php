<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use OpenApi\Attributes\Get;
use OpenApi\Attributes\Put;
use OpenApi\Attributes\Tag;
use OpenApi\Attributes\Parameter;
use OpenApi\Attributes\RequestBody;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Response;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Security;

/**
 * @OA\Tag(
 *     name="Admin Orders",
 *     description="Admin order management and status updates"
 * )
 */
class AdminOrderController extends Controller
{
    #[Get(
        path: "/api/admin/orders",
        tags: ["Admin Orders"],
        summary: "List all orders",
        security: [["sanctum" => []]],
        parameters: [
            new Parameter(parameter: "page", name: "page", in: "query", required: false, schema: new \OpenApi\Attributes\Schema(type: "integer", example: 1)),
            new Parameter(parameter: "status", name: "status", in: "query", required: false, schema: new \OpenApi\Attributes\Schema(type: "string", example: "pending")),
            new Parameter(parameter: "payment_status", name: "payment_status", in: "query", required: false, schema: new \OpenApi\Attributes\Schema(type: "string")),
            new Parameter(parameter: "search", name: "search", in: "query", required: false, schema: new \OpenApi\Attributes\Schema(type: "string")),
        ],
        responses: [
            new Response(response: 200, description: "Orders retrieved", content: new JsonContent(properties: [
                new Property(property: "success", type: "boolean", example: true),
                new Property(property: "data", type: "object"),
                new Property(property: "message", type: "string", example: "Orders retrieved"),
            ])),
            new Response(response: 401, description: "Unauthorized"),
            new Response(response: 403, description: "Forbidden"),
        ]
    )]
    public function index(Request $request)
    {
        $query = Order::with(['user', 'items.product']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = $request->input('per_page', 15);
        $orders = $query->latest()->paginate($perPage);

        return $this->paginateResponse($orders, 'Orders retrieved');
    }

    #[Get(
        path: "/api/admin/orders/{id}",
        tags: ["Admin Orders"],
        summary: "Get order details",
        security: [["sanctum" => []]],
        parameters: [
            new Parameter(parameter: "id", name: "id", in: "path", required: true, schema: new \OpenApi\Attributes\Schema(type: "integer")),
        ],
        responses: [
            new Response(response: 200, description: "Order details", content: new JsonContent(properties: [
                new Property(property: "success", type: "boolean", example: true),
                new Property(property: "data", type: "object"),
                new Property(property: "message", type: "string", example: "Order details retrieved"),
            ])),
            new Response(response: 401, description: "Unauthorized"),
            new Response(response: 403, description: "Forbidden"),
            new Response(response: 404, description: "Order not found"),
        ]
    )]
    public function show($id)
    {
        $order = Order::with(['user', 'items.product'])->find($id);

        if (!$order) {
            return $this->errorResponse('Order not found', 404);
        }

        return $this->successResponse($order, 'Order details retrieved');
    }

    #[Put(
        path: "/api/admin/orders/{id}/status",
        tags: ["Admin Orders"],
        summary: "Update order status",
        security: [["sanctum" => []]],
        parameters: [
            new Parameter(parameter: "id", name: "id", in: "path", required: true, schema: new \OpenApi\Attributes\Schema(type: "integer")),
        ],
        requestBody: new RequestBody(required: true, content: new JsonContent(required: ["status"], properties: [
            new Property(property: "status", type: "string", example: "processing", enum: ["pending", "processing", "shipped", "delivered", "cancelled"]),
            new Property(property: "notes", type: "string", example: "Order is being prepared"),
        ])),
        responses: [
            new Response(response: 200, description: "Status updated", content: new JsonContent(properties: [
                new Property(property: "success", type: "boolean", example: true),
                new Property(property: "data", type: "object"),
                new Property(property: "message", type: "string", example: "Order status updated"),
            ])),
            new Response(response: 401, description: "Unauthorized"),
            new Response(response: 403, description: "Forbidden"),
            new Response(response: 404, description: "Order not found"),
            new Response(response: 422, description: "Validation error"),
        ]
    )]
    public function updateStatus(Request $request, $id)
    {
        $order = Order::find($id);

        if (!$order) {
            return $this->errorResponse('Order not found', 404);
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled',
            'notes' => 'nullable|string',
        ]);

        $order->status = $validated['status'];

        if (isset($validated['notes'])) {
            $order->notes = $validated['notes'];
        }

        $now = now();

        switch ($validated['status']) {
            case 'processing':
                $order->paid_at = $order->paid_at ?? $now;
                break;
            case 'shipped':
                $order->shipped_at = $now;
                break;
            case 'delivered':
                $order->delivered_at = $now;
                break;
            case 'cancelled':
                $order->cancelled_at = $now;
                break;
        }

        $order->save();

        return $this->successResponse($order->load(['user', 'items.product']), 'Order status updated');
    }

    public function refund(Request $request, $id)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string',
        ]);

        $order = Order::findOrFail($id);

        if ($order->payment_status !== 'paid') {
            return $this->errorResponse('Order has not been paid', 400);
        }

        if ($validated['amount'] > $order->total_amount) {
            return $this->errorResponse('Refund amount cannot exceed order total', 400);
        }

        $order->update([
            'payment_status' => 'refunded',
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => 'Refund: ' . $validated['reason'],
        ]);

        foreach ($order->items as $item) {
            $item->product?->increment('stock_quantity', $item->quantity);
        }

        return $this->successResponse($order->load('items.product'), 'Refund processed');
    }
}
