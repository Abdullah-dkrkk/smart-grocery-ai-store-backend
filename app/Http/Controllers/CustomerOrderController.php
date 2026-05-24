<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenApi\Attributes\Get;
use OpenApi\Attributes\Post;
use OpenApi\Attributes\Tag;
use OpenApi\Attributes\Parameter;
use OpenApi\Attributes\RequestBody;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Response;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Security;

/**
 * @OA\Tag(
 *     name="Customer Orders",
 *     description="Order checkout and order history"
 * )
 */
class CustomerOrderController extends Controller
{
    #[Post(
        path: "/api/customer/orders/checkout",
        tags: ["Customer Orders"],
        summary: "Checkout and place an order",
        security: [["sanctum" => []]],
        requestBody: new RequestBody(required: true, content: new JsonContent(required: ["shipping_address", "shipping_phone", "payment_method"], properties: [
            new Property(property: "shipping_address", type: "string", example: "123 Main St, City, State"),
            new Property(property: "billing_address", type: "string", example: "123 Main St, City, State"),
            new Property(property: "shipping_phone", type: "string", example: "+1234567890"),
            new Property(property: "payment_method", type: "string", example: "credit_card", enum: ["credit_card", "debit_card", "cash_on_delivery"]),
            new Property(property: "notes", type: "string", example: "Please deliver after 5 PM"),
            new Property(property: "discount_code", type: "string", example: "SAVE10"),
        ])),
        responses: [
            new Response(response: 201, description: "Order placed", content: new JsonContent(properties: [
                new Property(property: "success", type: "boolean", example: true),
                new Property(property: "data", type: "object"),
                new Property(property: "message", type: "string", example: "Order placed successfully"),
            ])),
            new Response(response: 401, description: "Unauthorized"),
            new Response(response: 400, description: "Cart is empty"),
            new Response(response: 422, description: "Validation error"),
        ]
    )]
    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'shipping_address' => 'required|string',
            'billing_address' => 'nullable|string',
            'shipping_phone' => 'required|string',
            'payment_method' => 'required|in:credit_card,debit_card,cash_on_delivery',
            'notes' => 'nullable|string',
            'discount_code' => 'nullable|string',
        ]);

        $cartItems = CartItem::with('product')
            ->where('user_id', $request->user()->id)
            ->get();

        if ($cartItems->isEmpty()) {
            return $this->errorResponse('Cart is empty', 400);
        }

        DB::beginTransaction();

        try {
            $subtotal = 0;
            $orderItems = [];

            foreach ($cartItems as $cartItem) {
                if (!$cartItem->product->isInStock()) {
                    throw new \Exception("Product '{$cartItem->product->name}' is out of stock");
                }

                $lineTotal = (float) $cartItem->product->price * $cartItem->quantity;
                $subtotal += $lineTotal;

                $orderItems[] = [
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->product->price,
                    'subtotal' => $lineTotal,
                ];

                $cartItem->product->decrement('stock_quantity', $cartItem->quantity);
            }

            $taxAmount = round($subtotal * 0.1, 2);
            $shippingCost = $subtotal > 50 ? 0 : 5.99;
            $discountAmount = 0;
            $totalAmount = round($subtotal + $taxAmount + $shippingCost - $discountAmount, 2);

            $order = Order::create([
                'user_id' => $request->user()->id,
                'order_number' => 'ORD-' . strtoupper(Str::random(8)),
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'shipping_cost' => $shippingCost,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'payment_method' => $validated['payment_method'],
                'payment_status' => 'pending',
                'shipping_address' => $validated['shipping_address'],
                'billing_address' => $validated['billing_address'] ?? $validated['shipping_address'],
                'shipping_phone' => $validated['shipping_phone'],
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($orderItems as $item) {
                $order->items()->create($item);
            }

            CartItem::where('user_id', $request->user()->id)->delete();

            DB::commit();

            $order->load(['items.product']);

            return $this->successResponse($order->load(['items.product']), 'Order placed successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();

            \Illuminate\Support\Facades\Log::error('Checkout failed', [
                'user_id' => $request->user()->id,
                'message' => $e->getMessage(),
            ]);

            $message = str_contains($e->getMessage(), 'out of stock')
                ? $e->getMessage()
                : 'Checkout failed. Please try again.';

            return $this->errorResponse($message, 400);
        }
    }

    #[Get(
        path: "/api/customer/orders",
        tags: ["Customer Orders"],
        summary: "Get order history",
        security: [["sanctum" => []]],
        parameters: [
            new \OpenApi\Attributes\Parameter(parameter: "status", name: "status", in: "query", required: false, schema: new \OpenApi\Attributes\Schema(type: "string")),
        ],
        responses: [
            new Response(response: 200, description: "Orders retrieved", content: new JsonContent(properties: [
                new Property(property: "success", type: "boolean", example: true),
                new Property(property: "data", type: "object"),
                new Property(property: "message", type: "string", example: "Orders retrieved"),
            ])),
            new Response(response: 401, description: "Unauthorized"),
        ]
    )]
    public function index(Request $request)
    {
        $query = Order::with(['items.product'])
            ->where('user_id', $request->user()->id);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->latest()->paginate(15);

        return $this->paginateResponse($orders, 'Orders retrieved');
    }

    #[Get(
        path: "/api/customer/orders/{id}",
        tags: ["Customer Orders"],
        summary: "Get order details",
        security: [["sanctum" => []]],
        parameters: [
            new \OpenApi\Attributes\Parameter(parameter: "id", name: "id", in: "path", required: true, schema: new \OpenApi\Attributes\Schema(type: "integer")),
        ],
        responses: [
            new Response(response: 200, description: "Order details", content: new JsonContent(properties: [
                new Property(property: "success", type: "boolean", example: true),
                new Property(property: "data", type: "object"),
                new Property(property: "message", type: "string", example: "Order details retrieved"),
            ])),
            new Response(response: 401, description: "Unauthorized"),
            new Response(response: 404, description: "Order not found"),
        ]
    )]
    public function show(Request $request, $id)
    {
        $order = Order::with(['items.product'])
            ->where('user_id', $request->user()->id)
            ->find($id);

        if (!$order) {
            return $this->errorResponse('Order not found', 404);
        }

        return $this->successResponse($order, 'Order details retrieved');
    }

    public function cancel(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            return $this->errorResponse('Order not found.', 404);
        }

        if (!in_array($order->status, ['pending', 'processing'])) {
            return $this->errorResponse('Order cannot be cancelled in its current status.', 400);
        }

        $order->status = 'cancelled';
        $order->cancelled_at = now();
        $order->save();

        foreach ($order->items as $item) {
            $item->product->increment('stock_quantity', $item->quantity);
        }

        return $this->successResponse($order->load('items.product'), 'Order cancelled successfully.');
    }

    public function track(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            return $this->errorResponse('Order not found.', 404);
        }

        $timeline = [];

        $timeline[] = [
            'status' => 'pending',
            'timestamp' => $order->created_at->toISOString(),
            'note' => 'Order placed successfully.',
        ];

        if ($order->paid_at) {
            $timeline[] = [
                'status' => 'paid',
                'timestamp' => $order->paid_at->toISOString(),
                'note' => 'Payment confirmed.',
            ];
        }

        if ($order->status === 'processing' || in_array($order->status, ['shipped', 'delivered'])) {
            $timeline[] = [
                'status' => 'processing',
                'timestamp' => $order->updated_at->toISOString(),
                'note' => 'Order is being prepared.',
            ];
        }

        if ($order->shipped_at) {
            $timeline[] = [
                'status' => 'shipped',
                'timestamp' => $order->shipped_at->toISOString(),
                'note' => 'Package has been shipped.',
            ];
        }

        if ($order->delivered_at) {
            $timeline[] = [
                'status' => 'delivered',
                'timestamp' => $order->delivered_at->toISOString(),
                'note' => 'Package delivered successfully.',
            ];
        }

        if ($order->cancelled_at) {
            $timeline[] = [
                'status' => 'cancelled',
                'timestamp' => $order->cancelled_at->toISOString(),
                'note' => $order->cancellation_reason ?? 'Order cancelled.',
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'current_status' => $order->status,
                'timeline' => $timeline,
            ],
        ]);
    }
}
