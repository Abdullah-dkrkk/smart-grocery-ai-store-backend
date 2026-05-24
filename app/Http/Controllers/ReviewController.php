<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index($productId)
    {
        $product = Product::find($productId);

        if (!$product) {
            return $this->errorResponse('Product not found', 404);
        }

        $reviews = Review::where('product_id', $productId)
            ->with('user:id,name')
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $reviews->items(),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
                'last_page' => $reviews->lastPage(),
                'avg_rating' => (float) Review::where('product_id', $productId)->avg('rating'),
                'total_reviews' => Review::where('product_id', $productId)->count(),
            ],
        ]);
    }

    public function store(Request $request, $productId)
    {
        $product = Product::find($productId);

        if (!$product) {
            return $this->errorResponse('Product not found', 404);
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:2000',
        ]);

        $existing = Review::where('user_id', $request->user()->id)
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            return $this->errorResponse('You have already reviewed this product.', 409);
        }

        $review = Review::create([
            'user_id' => $request->user()->id,
            'product_id' => $productId,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
        ]);

        return $this->successResponse($review->load('user:id,name'), 'Review created successfully', 201);
    }

    public function update(Request $request, $id)
    {
        $review = Review::find($id);

        if (!$review) {
            return $this->errorResponse('Review not found', 404);
        }

        if ($review->user_id !== $request->user()->id) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $validated = $request->validate([
            'rating' => 'sometimes|integer|min:1|max:5',
            'comment' => 'nullable|string|max:2000',
        ]);

        $review->update($validated);

        return $this->successResponse($review->load('user:id,name'), 'Review updated successfully');
    }

    public function destroy(Request $request, $id)
    {
        $review = Review::find($id);

        if (!$review) {
            return $this->errorResponse('Review not found', 404);
        }

        if ($review->user_id !== $request->user()->id) {
            return $this->errorResponse('Unauthorized', 403);
        }

        $review->delete();

        return $this->successResponse(null, 'Review deleted successfully');
    }
}
