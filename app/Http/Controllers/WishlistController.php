<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request)
    {
        $items = Wishlist::where('user_id', $request->user()->id)
            ->with(['product.category', 'product.images'])
            ->latest()
            ->get();

        return $this->successResponse($items, 'Wishlist retrieved');
    }

    public function add(Request $request, $productId)
    {
        $product = Product::find($productId);

        if (!$product) {
            return $this->errorResponse('Product not found', 404);
        }

        $existing = Wishlist::where('user_id', $request->user()->id)
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            return $this->successResponse($existing->load(['product.category', 'product.images']), 'Product already in wishlist');
        }

        $item = Wishlist::create([
            'user_id' => $request->user()->id,
            'product_id' => $productId,
        ]);

        return $this->successResponse($item->load(['product.category', 'product.images']), 'Product added to wishlist', 201);
    }

    public function remove(Request $request, $productId)
    {
        $item = Wishlist::where('user_id', $request->user()->id)
            ->where('product_id', $productId)
            ->first();

        if (!$item) {
            return $this->errorResponse('Product not in wishlist', 404);
        }

        $item->delete();

        return $this->successResponse(null, 'Product removed from wishlist');
    }
}
