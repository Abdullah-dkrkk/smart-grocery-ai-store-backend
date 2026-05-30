<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use OpenApi\Attributes\Get;
use OpenApi\Attributes\Post;
use OpenApi\Attributes\Delete;
use OpenApi\Attributes\Tag;
use OpenApi\Attributes\RequestBody;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Response;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Security;

/**
 * @OA\Tag(
 *     name="Customer Cart",
 *     description="Shopping cart management"
 * )
 */
class CustomerCartController extends Controller
{
    #[Get(
        path: "/api/customer/cart",
        tags: ["Customer Cart"],
        summary: "Get cart items",
        security: [["sanctum" => []]],
        responses: [
            new Response(response: 200, description: "Cart items retrieved", content: new JsonContent(properties: [
                new Property(property: "success", type: "boolean", example: true),
                new Property(property: "data", type: "object"),
                new Property(property: "message", type: "string", example: "Cart items retrieved"),
            ])),
            new Response(response: 401, description: "Unauthorized"),
        ]
    )]
    public function index(Request $request)
    {
        $items = CartItem::with('product')
            ->where('user_id', $request->user()->id)
            ->get();

        $subtotal = $items->sum(function ($item) {
            return (float) $item->product->price * $item->quantity;
        });

        return $this->successResponse([
            'items' => $items,
            'subtotal' => round($subtotal, 2),
            'item_count' => $items->count(),
        ], 'Cart items retrieved');
    }

    #[Post(
        path: "/api/customer/cart/add",
        tags: ["Customer Cart"],
        summary: "Add item to cart",
        security: [["sanctum" => []]],
        requestBody: new RequestBody(required: true, content: new JsonContent(required: ["product_id", "quantity"], properties: [
            new Property(property: "product_id", type: "integer", example: 1),
            new Property(property: "quantity", type: "integer", example: 2),
        ])),
        responses: [
            new Response(response: 200, description: "Item added to cart", content: new JsonContent(properties: [
                new Property(property: "success", type: "boolean", example: true),
                new Property(property: "data", type: "object"),
                new Property(property: "message", type: "string", example: "Item added to cart"),
            ])),
            new Response(response: 401, description: "Unauthorized"),
            new Response(response: 422, description: "Validation error"),
        ]
    )]
    public function add(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::find($validated['product_id']);

        if (!$product->isInStock()) {
            return $this->errorResponse('Product is out of stock', 400);
        }

        $cartItem = CartItem::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'product_id' => $validated['product_id'],
            ],
            [
                'quantity' => $validated['quantity'],
            ]
        );

        $cartItem->load('product');

        return $this->successResponse($cartItem->load('product'), 'Item added to cart');
    }

    public function clear(Request $request)
    {
        CartItem::where('user_id', $request->user()->id)->delete();

        return $this->successResponse(null, 'Cart cleared');
    }

    #[Delete(
        path: "/api/customer/cart/{id}",
        tags: ["Customer Cart"],
        summary: "Remove item from cart",
        security: [["sanctum" => []]],
        parameters: [
            new \OpenApi\Attributes\Parameter(parameter: "id", name: "id", in: "path", required: true, schema: new \OpenApi\Attributes\Schema(type: "integer")),
        ],
        responses: [
            new Response(response: 200, description: "Item removed", content: new JsonContent(properties: [
                new Property(property: "success", type: "boolean", example: true),
                new Property(property: "message", type: "string", example: "Item removed from cart"),
            ])),
            new Response(response: 401, description: "Unauthorized"),
            new Response(response: 404, description: "Cart item not found"),
        ]
    )]
    public function remove(Request $request, $id)
    {
        $cartItem = CartItem::where('user_id', $request->user()->id)->find($id);

        if (!$cartItem) {
            return $this->errorResponse('Cart item not found', 404);
        }

        $cartItem->delete();

        return $this->successResponse(null, 'Item removed from cart');
    }
}
